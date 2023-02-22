<?php

namespace App\Http\Controllers;

use App\Services\CscApiService;
use EidEasy\Signatures\Pades;
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
    protected $cscApiService;
    protected $pades;

    public function __construct(Client $client, Pades $pades)
    {
        $this->client = $client;

        $this->cscApiService = new CscApiService(
            $client, config('eideasy.client_id'), config('eideasy.secret'), config('eideasy.api_url')
        );

        $pades->setGuzzle($client);
        $pades->setApiUrl(config('eideasy.pades_api_uri'));
        $this->pades = $pades;
    }

    public function startCscApiSignature(Request $request)
    {
        $request->validate([
            'unsigned_file' => 'required|file',
        ]);

        $fileInfo = $request->file('unsigned_file');
        $fileId = Str::random();
        $fileContent = file_get_contents($fileInfo->path());
        $fileName = $fileInfo->getClientOriginalName();
        $mimeType = $fileInfo->getMimeType();


        Storage::put("/unsigned/$fileId/$fileName", $fileContent);

        $preparedFile = [
            'fileName' => $fileName,
            'mimeType' => $mimeType,
        ];

        $padesResponse = $this->pades->getPadesDigest($fileContent);
        if (!isset($padesResponse['digest'])) {
            Log::error("Pades preparation failed", $padesResponse);
            return response("Pades preparation failed");
        }
        $preparedFile['hash'] = $padesResponse['digest']; // Modified PDF digest will be signed.
        $preparedFile['signatureTime'] = $padesResponse['signatureTime'];

        Cache::put("file-data-for-csc-api-$fileId", serialize($preparedFile));

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
            'state'         => $fileId,
        ];

        $redirectUrl = $apiUrl . '/oauth2/authorize?' . http_build_query($parameters);

        return redirect($redirectUrl);
    }

    protected function credential(Request $request)
    {
        $state = $request->input('state');
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

        Cache::put("credentialID-$state", $credentialID);
        Cache::put("signAlgo-$state", $credentialInfo['key']['algo'][0]);
        Cache::put("accessToken-$state", $accessToken);

        return redirect()->to($this->credentialUrl($credentialID, $request->input('state')));
    }

    protected function signature(Request $request)
    {
        $tokenFetchResult = $this->getOauthToken(
            $request->input('code'),
            config('eideasy.redirect_uri') . '/csc-signature'
        );

        $sadToken = $tokenFetchResult['access_token'];

        $state = $request->input('state');
        $accessToken = Cache::pull("accessToken-$state");
        $credentialID = Cache::pull("credentialID-$state");
        $signAlgo = Cache::pull("signAlgo-$state");
        $serializedFileData = Cache::get("file-data-for-csc-api-$state");
        $fileData = unserialize($serializedFileData);
        $result = $this->signHash($accessToken, $credentialID, $fileData['hash'], $sadToken, $signAlgo);
        $signature = $result['signatures'][0] ?? null;

        if (!$signature) {
            throw new \Exception('signHash result is missing signatures');
        }

        Cache::put("csc-api-signature-$state", $signature);

        return view('download-csc-api-signed-file', ['fileId' => $state]);
    }

    public function downloadSignedFile(Request $request)
    {
        $fileId = $request->input('file_id');
        $signature = Cache::get("csc-api-signature-$fileId");
        // Assemble signed file and make sure its in binary form before downloading.
        $serializedFileData = Cache::get("file-data-for-csc-api-$fileId");
        $fileData = unserialize($serializedFileData);

        $fileName = $fileData['fileName'];
        $signatureTime = $fileData['signatureTime'];
        /** @var array $padesDssData */

        $unsignedFile = Storage::get("/unsigned/$fileId/" . $fileName);
        $padesResponse = $this->pades->addSignaturePades($unsignedFile, $signatureTime, $signature, null);

        $signedFileContents = base64_decode($padesResponse['signedFile']);

        info("Signed file downloaded");

        $headers = [
            'Content-type'        => 'application/vnd.etsi.asic-e+zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return Response::make($signedFileContents, 200, $headers);
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
            "hash"         => [$hash],
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
            'certInfo' => true,
            'authInfo' => true,
        ]);

        return $response->json();
    }

    protected function credentialUrl($credentialID, $state)
    {

        $clientId = config('eideasy.client_id');
        $apiUrl = config('eideasy.api_url');
        $redirectBackUri = config('eideasy.redirect_uri') . '/csc-signature';

        $serializedFileData = Cache::get("file-data-for-csc-api-$state");
        $fileData = unserialize($serializedFileData);

        $parameters = [
            'scope'         => 'credential',
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectBackUri,
            'credentialID'  => $credentialID,
            'state'         => $state,
            'hash'          => $fileData['hash'],
        ];

        return $apiUrl . '/oauth2/authorize?' . http_build_query($parameters);
    }
}
