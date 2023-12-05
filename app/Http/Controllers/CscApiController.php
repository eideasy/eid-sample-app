<?php

namespace App\Http\Controllers;

use App\Services\CscApiService;
use App\Services\TempFileStorageService;
use App\Services\ZipService;
use EidEasy\Signatures\Pades;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CscApiController extends Controller
{
    protected $client;
    protected CscApiService $cscApiService;
    protected $pades;

    private const ALGO_PARAMS_BY_OID = [
        '1.2.840.113549.1.1.11' => [
            'name'            => 'sha256WithRSAEncryption',
            'encryption'      => 'RSA',
            'digestAlgorithm' => 'SHA256'
        ],
        '1.2.840.10045.4.3.4'   => [
            'name'            => 'ecdsa-with-SHA512',
            'encryption'      => 'EC',
            'digestAlgorithm' => 'SHA512'
        ],
        '1.2.840.10045.4.3.2'   => [
            'name'            => 'ecdsa-with-SHA256',
            'encryption'      => 'EC',
            'digestAlgorithm' => 'SHA256'
        ],
    ];

    public function __construct(
        protected TempFileStorageService $tempFileStorageService,
        protected ZipService $zipService,
        Client $client,
        Pades $pades
    ) {
        $this->client = $client;

        $pades->setGuzzle($client);
        $pades->setApiUrl(config('eideasy.pades_api_uri'));
        $this->pades = $pades;

        $this->cscApiService = new CscApiService(
            $pades,
            $client,
            config('eideasy.client_id'),
            config('eideasy.secret'),
            config('eideasy.api_url')
        );
    }

    public function startCscApiSignature(Request $request)
    {
        $request->validate([
            'unsigned_file' => 'required|array',
        ]);

        $fileInfo = $request->file('unsigned_file')[0];
        $processId = Str::random();
        $fileName = $fileInfo->getClientOriginalName();
        $mimeType = $fileInfo->getMimeType();

        $signatureTime = null;
        $rawDigests = [];
        $fileIndexes = [];
        foreach ($request->file('unsigned_file') as $fileIndex => $fileInfo) {
            $fileContent = file_get_contents($fileInfo->path());

            $padesResponse = $this->pades->getPadesDigest($fileContent);
            if (!isset($padesResponse['digest'])) {
                Log::error("Pades preparation failed", $padesResponse);
                return response("Pades preparation failed");
            }
            $rawDigests[$fileIndex] = $padesResponse['digest']; // Modified PDF digest will be signed.
            $signatureTime = $padesResponse['signatureTime'];

            $fileIndexes[] = $fileIndex;
            Storage::put("/unsigned/$processId/$fileIndex/$fileName", $fileContent);
        }

        Log::info('startCscApiSignature signatureTime', compact('signatureTime'));

        Cache::put("fileIndexes-$processId", $fileIndexes);
        Cache::put("rawDigests-$processId", $rawDigests);
        Cache::put("signatureTime-$processId", $signatureTime);
        Cache::put("fileName-$processId", $fileName);
        Cache::put("mimeType-$processId", $mimeType);

        $clientId = config('eideasy.client_id');
        $apiUrl = config('eideasy.api_url');
        $redirectBackUri = config('eideasy.redirect_uri') . '/csc-service-return';
        $accountToken = $this->cscApiService->createAccountToken();

        $parameters = [
            'scope'         => 'service',
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectBackUri,
            'account_token' => $accountToken,
            'state'         => $processId,
        ];

        $redirectUrl = $apiUrl . '/oauth2/authorize?' . http_build_query($parameters);

        return redirect($redirectUrl);
    }

    protected function credential(Request $request)
    {
        $processId = $request->input('state');
        $tokenFetchResult = $this->getOauthToken(
            $request->input('code'),
            config('eideasy.redirect_uri') . '/csc-service-return'
        );

        // We refresh the token here just to demo how it is done.
        // In real worlds scenarios you probably want to refresh the token when it's about to expire and your
        // user has not managed to complete the flow yet.
        $refreshResult = $this->getOauthTokenByRefreshToken($tokenFetchResult['refresh_token']);

        $accessToken = $refreshResult['access_token'];

        $fetchResult = $this->fetchCredentialsList($accessToken);
        if (!$fetchResult->json()) {
            return $fetchResult->body();
        }
        $credentialIDs = $fetchResult->json();
        $credentialID = $credentialIDs['credentialIDs'][0] ?? null;

        $credentialInfo = $this->getCredentialInfo($accessToken, $credentialID);
        info('CSC API credentialInfo', [
            'credentialInfo' => $credentialInfo
        ]);

        $certificate = $credentialInfo['cert']['certificates'][0];
        $rawDigests = Cache::get("rawDigests-$processId");

        $signingTime = null;
        $preparedDigests = [];
        foreach ($rawDigests as $fileIndex => $rawDigest) {
            $preparedContainerData = $this->prepareCadesContainer(
                $certificate,
                [
                    'mimeType' => Cache::get("mimeType-$processId"),
                    'hash' => $rawDigest,
                    'fileName' => Cache::get("fileName-$processId"),
                ]
            );
            $signingTime = $preparedContainerData['signingTime'];
            $preparedDigests[$fileIndex] = $preparedContainerData['signedInfoDigest'];
        }

        Cache::put("credentialID-$processId", $credentialID);
        Cache::put("signAlgo-$processId", $credentialInfo['key']['algo'][0]);
        Cache::put("accessToken-$processId", $accessToken);
        Cache::put("cscCertificate-$processId", $certificate);
        Cache::put("preparedDigests-$processId", $preparedDigests);
        Cache::put("preparedSigningTime-$processId", $signingTime);

        return redirect()->to($this->credentialUrl($credentialID, $processId));
    }

    protected function signature(Request $request)
    {
        $tokenFetchResult = $this->getOauthToken(
            $request->input('code'),
            config('eideasy.redirect_uri') . '/csc-signature'
        );

        $sadToken = $tokenFetchResult['access_token'];

        $state = $request->input('state');
        $processId = $state;
        $accessToken = Cache::pull("accessToken-$processId");
        $credentialID = Cache::pull("credentialID-$processId");
        $signAlgo = Cache::pull("signAlgo-$processId");
        $certificate = Cache::pull("cscCertificate-$processId");
        $digests = Cache::get("preparedDigests-$processId");

        $result = $this->signHash(
            $accessToken,
            $credentialID,
            array_values($digests),
            $sadToken,
            $signAlgo
        );
        $signatures = $result['signatures'] ?? null;

        if (!$signatures) {
            throw new \Exception('signHash result is missing signatures');
        }

        $rawDigests = Cache::get("rawDigests-$processId");
        $cadesSignatures = [];
        foreach ($signatures as $fileIndex => $signature) {
            $finalizedSignatureData = $this->finalizeCadesSignature(
                [
                    'fileName' => Cache::get("fileName-$processId"),
                    'fileContent' => $rawDigests[$fileIndex],
                    'mimeType' => Cache::get("mimeType-$processId"),
                ],
                $signature,
                $signAlgo,
                Cache::get("preparedSigningTime-$processId"),
                $certificate
            );
            $cadesSignatures[$fileIndex] = $finalizedSignatureData['fileContent'];
        }

        Cache::put("signatures-$processId", $cadesSignatures);

        return view('download-csc-api-signed-file', ['fileId' => $processId]);
    }

    protected function getOauthToken($code, $redirectUri)
    {
        $response = Http::post(config('eideasy.api_url') . '/oauth2/token', [
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_id'     => config('eideasy.client_id'),
            'client_secret' => config('eideasy.secret'),
            'redirect_uri'  => $redirectUri,
        ]);

        if (!isset($response['access_token'])) {
            return $response->body();
        }

        return [
            'access_token'  => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
        ];
    }

    protected function getOauthTokenByRefreshToken($refreshToken)
    {
        $response = Http::post(config('eideasy.api_url') . '/oauth2/token', [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
            'client_id'     => config('eideasy.client_id'),
            'client_secret' => config('eideasy.secret'),
        ]);

        if (!isset($response['access_token'])) {
            return $response->body();
        }

        return [
            'access_token'  => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
        ];
    }

    protected function signHash($accessToken, $credentialID, $hash, $sadToken, $signAlgo)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/signatures/signHash', [
            "credentialID" => $credentialID,
            "SAD"          => $sadToken,
            "hash"         => $hash,
            "hashAlgo"     => "2.16.840.1.101.3.4.2.1",
            "signAlgo"     => $signAlgo,
        ]);

        return $response->json();
    }

    protected function fetchCredentialsList($accesToken)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accesToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/credentials/list');

        return $response;
    }

    protected function getCredentialInfo($accesToken, $credentialID)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accesToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/credentials/info', [
            'credentialID' => $credentialID,
            'certificates' => 'chain',
            'certInfo'     => true,
            'authInfo'     => true,
        ]);

        return $response->json();
    }

    protected function credentialUrl($credentialID, $state)
    {
        $clientId = config('eideasy.client_id');
        $apiUrl = config('eideasy.api_url');
        $redirectBackUri = config('eideasy.redirect_uri') . '/csc-signature';

        $digests = Cache::get("preparedDigests-$state");
        $urlSafeDigests = [];
        foreach ($digests as $digest) {
            $urlSafeDigests[] = JWT::urlsafeB64Encode(base64_decode($digest));
        }

        $parameters = [
            'scope'         => 'credential',
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectBackUri,
            'credentialID'  => $credentialID,
            'state'         => $state,
        ];

        return $apiUrl . '/oauth2/authorize?' . http_build_query($parameters) . '&hash=' . implode(',', $urlSafeDigests);
    }

    private function prepareCadesContainer(string $certificate, array $file)
    {
        $body = [
            'certificate'       => $certificate,
            'files'             => [
                [
                    'fileName'    => $file['fileName'],
                    'mimeType'    => $file['mimeType'],
                    'fileContent' => $file['hash'],
                ]
            ],
            'baseline'          => 'B',
            'disableValidation' => true,
            'signingSessionId'  => 99999999,
        ];

        $response = $this->callDss('/signature/cades/prepare', $body);

        $signHash = $response['digest']; // dss-signutility sends hex digest.
        $signHashInBase64 = base64_encode(hex2bin($signHash));

        return [
            "signedInfoDigest" => $signHashInBase64,
            "hexDigest"        => $signHash,
            "signingTime"      => $response['signingTime'],
        ];
    }

    public function finalizeCadesSignature(
        array  $fileData,
        string $signature,
               $algorithm,
               $signingTime,
               $certificate
    )
    {
        $algoParams = self::ALGO_PARAMS_BY_OID[$algorithm];

        if ($algoParams['encryption'] === 'RSA') {
            $encoding = "none";
        } else {
            $encoding = "RS";
        }

        $body = [
            'signingTime'         => $signingTime,
            'signature'           => bin2hex(base64_decode($signature)),
            'signingSessionId'    => 999999,
            'certificate'         => $certificate,
            'baseline'            => 'B',
            'signatureParameters' => [
                'encoding'        => $encoding,
                'encryption'      => $algoParams['encryption'],
                'digestAlgorithm' => $algoParams['digestAlgorithm'],
            ],
            'files'               => [$fileData],
            'disableValidation'   => true,
        ];

        Log::info('finalizeCadesSignature', compact('body'));

        $finalizeData = $this->callDss('/signature/cades/complete', $body);

        return $finalizeData;
    }

    protected function callDss($url, $body)
    {
        $baseUrl = config('eideasy.dss_uri');
        $body['utilityKey'] = config('eideasy.sign_utility_key');

        $rawResponse = Http::post($baseUrl . $url, $body);
        $response = $rawResponse->json();

        info('callDss body', compact('body'));

        if ($response['status'] !== 'OK') {
            Log::error('Signutility response not OK', ['body' => $response->body()]);
            return [
                'data'      => [
                    "status"  => "error",
                    "message" => $data['reason'] ?? __('error')
                ], 'status' => 400
            ];
        }

        return $response;
    }

    public function downloadSignedFile(Request $request)
    {
        $processId = $request->input('file_id');
        $signatures = Cache::pull("signatures-$processId");
        // Assemble signed file and make sure its in binary form before downloading.

        $fileName = Cache::pull("fileName-$processId");
        $signatureTime = Cache::pull("signatureTime-$processId");
        $fileIndexes = Cache::pull("fileIndexes-$processId");

        $signedFilesContent = [];
        foreach ($fileIndexes as $fileIndex) {
            $unsignedFile = Storage::get("/unsigned/$processId/$fileIndex/$fileName");
            Log::info('downloadSignedFile signatureTime', compact('signatureTime'));
            $padesResponse = $this->pades->addSignaturePades($unsignedFile, $signatureTime, $signatures[$fileIndex], null);

            $signedFilesContent[] = base64_decode($padesResponse['signedFile']);
        }

        if (count($signedFilesContent) > 1) {
            $zipDto = $this->zipService->zipPdfs($fileName, $signedFilesContent);
            $absolutePath = $this->tempFileStorageService->absolutePath($zipDto->getFilePath());

            $downloadFileName = str_replace('.pdf', '', $fileName) . '.zip';
            $downloadFileName = str_replace(',', '', $downloadFileName);
            $downloadFileName = iconv('utf-8', 'ascii//TRANSLIT', $downloadFileName);

            return response()->download($absolutePath, $downloadFileName);
        }

        info("Signed file downloaded");

        $headers = [
            'Content-type'        => 'application/vnd.etsi.asic-e+zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return Response::make($signedFilesContent, 200, $headers);
    }
}
