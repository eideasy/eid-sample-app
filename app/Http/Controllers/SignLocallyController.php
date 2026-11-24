<?php

namespace App\Http\Controllers;

use App\Services\EidEasyApiService;
use App\Http\Requests\SigningPlaygroundRequest;
use App\Services\TempFileStorageService;
use App\Services\ZipService;
use EidEasy\Signatures\Asice;
use EidEasy\Signatures\Pades;
use EidEasy\Signatures\SignatureParameters;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class SignLocallyController extends Controller
{
    protected $client;
    protected $eidEasyApi;
    protected $pades;

    public function __construct(
        protected TempFileStorageService $tempFileStorageService,
        protected ZipService $zipService,
        Client $client,
        Pades $pades,
    ) {
        $this->client = $client;

        $this->eidEasyApi = new EidEasyApiService(
            $client, config('eideasy.client_id'), config('eideasy.secret'), config('eideasy.api_url')
        );

        $pades->setGuzzle($client);
        $pades->setApiUrl(config('eideasy.pades_api_uri'));
        $this->pades = $pades;
    }

    public function downloadUnSignedFile(Request $request)
    {
        $query = $request->query();
        $fileName = $query['filename'] ?? null;
        $docId = $query['doc_id'] ?? null;
        abort_unless(is_string($fileName) && is_string($docId) && preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $docId), 404);

        $fileId = Cache::get("file_id-$docId");
        $metadata = Cache::get("prepared-files-$fileId", []);
        abort_unless($fileId && in_array($fileName, array_column($metadata, 'fileName'), true), 404);

        $path = "/unsigned/$fileId/$fileName";
        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, null, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function startSignCustomFile(SigningPlaygroundRequest $request)
    {
        $validated = $request->validated();

        // Use sandbox credentials for e-Seals for now
        if ($request->input('signType') === 'eseal') {
            $this->eidEasyApi->setApiUrl(config('eideasy.eid_test_api'));
            $this->eidEasyApi->setClientId(config('eideasy.eid_test_client_id'));
            $this->eidEasyApi->setSecret(config('eideasy.eid_test_secret'));
        }

        $containerType = $request->input('containerType');
        $signType      = $request->input('signType');

        info("Start preparing signing: $signType $containerType");

        // We need to know how to get current files before getting doc_id from eID Easy. We use temporary $fileId for that
        $fileId = Str::random();

        // Process and prepare uploaded files to API calls.
        $sourceFiles = [];
        foreach ($request->file('unsigned_file') as $fileInfo) {
            $fileContent = file_get_contents($fileInfo->path());
            $fileName    = $fileInfo->getClientOriginalName();
            if (in_array($fileName, ['', '.', '..'], true) || preg_match('/[\x00-\x1f\x7f]/', $fileName)) {
                return $this->preparationFailed($request, 'One of the files has an invalid name. Rename it and try again.');
            }
            $mimeType    = $containerType === 'enveloped-xades' ? 'application/xml' : $fileInfo->getMimeType();

            $sourceFiles[] = [
                'fileName'    => $fileName,
                'fileContent' => $fileContent,
                'mimeType'    => $mimeType,
            ];

            Storage::put("/unsigned/$fileId/$fileName", $fileContent);
            $metaData[] = [
                'fileName'    => $fileName,
                'mimeType'    => $mimeType,
                'fileContent' => base64_encode(hash('sha256', $fileContent, true))
            ];
        }

        // Handle digest based signature starting.
        $signatureContainer = $containerType;
        $additionalPrepareParams = [];
        if ($signType === "digest" && $containerType === "pdf") {
            $signatureContainer = 'cades';
            try {
                $padesResponse = $this->pades->getPadesDigest($sourceFiles[0]['fileContent']);
            } catch (GuzzleException | \TypeError) {
                $padesResponse = [];
            }
            if (!isset($padesResponse['digest'], $padesResponse['signatureTime'])) {
                return $this->preparationFailed($request, 'The PDF digest helper could not prepare this file. Try a different PDF or another signing flow.');
            }
            $metaData[0]['fileContent'] = $padesResponse['digest']; // Modified PDF digest will be signed.
            Cache::put("pades-signatureTime-$fileId", $padesResponse['signatureTime']);
        } elseif ($signType === "digest" && $containerType === "asice") {
            $signatureContainer = 'xades';
            $asice              = new Asice();
            $asiceContainer     = $asice->createAsiceContainer($sourceFiles);
            Storage::put("/unsigned/$fileId/container-$fileId.asice", $asiceContainer);
        } elseif ($containerType === 'asice-for-each-file') {
            $signatureContainer = 'asice';
            foreach ($sourceFiles as $key => $value) {
                [$fileName] = explode('.', $value['fileName']);
                $sourceFiles[$key]['containerFileName'] = $fileName . '_' . $key . '.asice';
            }
        } elseif ($containerType === 'enveloped-xades') {
            $signatureContainer = 'xades';
            $additionalPrepareParams['signature_packaging'] = 'ENVELOPED';
        }

        if ($signType === 'digest') {
            $sourceFiles = $metaData;
        } else {
            foreach ($sourceFiles as $key => $value) {
                $sourceFiles[$key]['fileContent'] = base64_encode($value['fileContent']);
            }
        }

        $prepareParams = [
            'signature_redirect' => $request->redirect_uri ?? url('/show-download-signed-file') . "?file_id=$fileId",
            'container_type'     => $signatureContainer,
            'files'              => $sourceFiles,
            'baseline'           => 'LT',
            'notification_state' => [
                'time' => now()->toIso8601String()
            ],
            'show_visual'        => $signType !== 'digest' && $containerType === 'pdf' && !$request->boolean('hide_pdf_visual'),
            'return_available_methods' => true,
        ] + $additionalPrepareParams;

        $signerContacts = [];
        if ($request->has('simple_email') && !empty($request->input('simple_email'))) {
            $signerContacts[] = (object)[
                'type'  => 'email',
                'value' => $request->input('simple_email'),
            ];
        }
        if ($request->has('simple_sms') && !empty($request->input('simple_sms'))) {
            $signerContacts[] = (object)[
                'type'  => 'sms',
                'value' => $request->input('simple_sms'),
            ];
        }

        if ($prepareParams['show_visual']) {
            $coordinates = [];
            foreach (['pdf_x' => 'x', 'pdf_y' => 'y', 'pdf_page' => 'page'] as $field => $coordinate) {
                if (isset($validated[$field])) {
                    $coordinates[$coordinate] = (int) $validated[$field];
                }
            }
            if ($coordinates !== []) {
                $prepareParams['visual_coordinates'] = (object) ($coordinates + ['page' => 0]);
            }
        }

        if (count($signerContacts) > 0) {
            $prepareParams['signer'] = [
                'send_now'   => true,
                'first_name' => $request->input('simple_firstname'),
                'last_name'  => $request->input('simple_lastname'),
                'contacts'   => $signerContacts
            ];
        }

        try {
            $data = $this->eidEasyApi->prepareFiles($sourceFiles, $prepareParams);
        } catch (GuzzleException | \TypeError) {
            $data = [];
        }

        $docId = $data['doc_id'] ?? null;
        if (($data['status'] ?? 'OK') !== 'OK' || !is_string($docId) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $docId)) {
            return $this->preparationFailed($request, 'The signing API could not prepare these files. Check the selected format, choose your files again and retry.');
        }

        // We need to use this later to assemble or get the signed file.
        Cache::put("doc_id-$fileId", $docId);
        Cache::put("file_id-$docId", $fileId);
        Cache::put("signType-$fileId", $signType);
        Cache::put("containerType-$fileId", $containerType);
        $availableMethods = $data['available_methods'] ?? [];
        Cache::put("available_methods-$docId", is_array($availableMethods)
            ? array_values(array_filter($availableMethods, 'is_string'))
            : []);

        info("File prepared for signing file_id=$fileId, doc_id=$docId");

        $clientId = config('eideasy.client_id');
        if ($signType === "external") {
            $baseUrl = rtrim(config('eideasy.api_url'), '/');
            $signingPage = $data['signing_page_url'] ?? null;
            if (is_string($signingPage) && str_starts_with($signingPage, $baseUrl . '/sign_contract_external?')) {
                return redirect()->away($signingPage);
            }

            return redirect()->away($baseUrl . '/sign_contract_external?' . http_build_query([
                'client_id' => $clientId, 'doc_id' => $docId,
            ]));
        } elseif ($signType === 'multisign') {
            $response = $this->eidEasyApi->createSigningQueue($docId, ['has_management_page' => true]);
            Cache::put("signing_queue_id_$fileId", $response['id']);
            Cache::put('signing_queue_secret_'. $response['id'], $response['signing_queue_secret']);

            return redirect()->to($response["management_page_url"]);
        } elseif ($signType === "eseal") {
            try {
                $esealResponse = $this->eidEasyApi->createEseal($docId);
            } catch (GuzzleException | \TypeError) {
                $esealResponse = [];
            }
            if (($esealResponse['status'] ?? null) !== 'OK') {
                return $this->preparationFailed($request, 'The sandbox could not create the e-Seal. Try again or choose another signing flow.');
            }
            Cache::put("issandbox-$fileId", true);
            return redirect()->to(url('/show-download-signed-file') . "?file_id=$fileId");
        } else {
            Cache::put("prepared-files-$fileId", $metaData);
            return redirect()->to("/sign-locally-sample?doc_id=$docId");
        }
    }

    private function preparationFailed(Request $request, string $message)
    {
        Log::warning('Demo signing preparation failed', ['flow' => $request->input('signType')]);

        return redirect('/sign-custom-file')
            ->withInput($request->except(['unsigned_file', '_token']))
            ->with('alert-class', 'alert-danger')
            ->with('message', $message);
    }

    public function downloadSignedFile(Request $request)
    {
        info("Start downloading signed file");

        $fileId = $request->query()['file_id'] ?? null;
        abort_unless(is_string($fileId) && preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $fileId), 404);

        $docId = Cache::get("doc_id-$fileId") ?? $request->session()->get("doc_id-$fileId");
        abort_unless(is_string($docId), 404);
        $signType      = Cache::get("signType-$fileId");
        $containerType = Cache::get("containerType-$fileId");
        $isSandbox     = Cache::get("issandbox-$fileId");

        if ($signingQueueId = Cache::get("signing_queue_id_$fileId")) {
            $response = $this->eidEasyApi->getSigningQueue(
                $signingQueueId,
                Cache::get("signing_queue_secret_$signingQueueId")
            );

            $docId = collect($response['signers'] ?? [])
                ->whereNotNull('doc_id')
                ->whereNotNull('signed_at')
                ->sortByDesc('signed_at')
                ->first()['doc_id'] ?? $docId;
        }

        if ($isSandbox) {
            $this->eidEasyApi->setApiUrl(config('eideasy.eid_test_api'));
            $this->eidEasyApi->setClientId(config('eideasy.eid_test_client_id'));
            $this->eidEasyApi->setSecret(config('eideasy.eid_test_secret'));
        }

        try {
            $data = $this->eidEasyApi->downloadSignedFile($docId);
        } catch (GuzzleException | \TypeError) {
            $data = [];
        }
        if (empty($data['signed_files'])) {
            return redirect(url()->query('/show-download-signed-file', ['file_id' => $fileId]))
                ->with('alert-class', 'alert-warning')
                ->with('message', 'The signed files are unavailable. Finish signing and try downloading again.');
        }

        $fileName = $data['filename'];
        $signedFilesContent = [];
        foreach ($data['signed_files'] as $file) {
            $signedFilesContent[] = base64_decode($file['contents']);
        }

        if (count($signedFilesContent) > 1) {
            $zipDto = $this->zipService->zipFiles($fileName, $signedFilesContent);
            $absolutePath = $this->tempFileStorageService->absolutePath($zipDto->getFilePath());

            $downloadFileName = $zipDto->getFileName();
            $downloadFileName = str_replace(',', '', $downloadFileName);
            $downloadFileName = iconv('utf-8', 'ascii//TRANSLIT', $downloadFileName);

            info("Signed file downloaded");
            return response()->download($absolutePath, $downloadFileName);
        }

        $signedFileContents = $data['signed_file_contents'];

        // Assemble signed file and make sure its in binary form before downloading.
        if ($signType === "digest" && $containerType === "pdf") {
            $metaData      = Cache::get("prepared-files-$fileId");
            $fileName      = $metaData[0]['fileName'];
            $signatureTime = Cache::get("pades-signatureTime-$fileId");
            /** @var array $padesDssData */
            $padesDssData = $data['pades_dss_data'] ?? null;

            $unsignedFile       = Storage::get("/unsigned/$fileId/" . $fileName);
            $padesResponse      = $this->pades->addSignaturePades($unsignedFile, $signatureTime, $signedFileContents, $padesDssData);
            $signedFileContents = base64_decode($padesResponse['signedFile']);
        } elseif ($signType === "digest" && $containerType === "asice") {
            $fileName           = "container-$fileId.asice";
            $asice              = new Asice();
            $unsignedFile       = Storage::get("/unsigned/$fileId/$fileName");
            $asiceContainer     = $asice->addSignatureAsice($unsignedFile, base64_decode($signedFileContents));
            $signedFileContents = $asiceContainer;
        } else {
            $signedFileContents = base64_decode($signedFileContents);
        }

        info("Signed file downloaded");

        $fileName = str_replace(['/', '\\'], '_', $fileName);
        $contentType = match ($containerType) {
            'pdf' => 'application/pdf',
            'enveloped-xades' => 'application/xml',
            default => 'application/vnd.etsi.asic-e+zip',
        };
        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $fileName, 'signed-file'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ];

        return Response::make($signedFileContents, 200, $headers);
    }

    private function getSampleSignatureParams(): SignatureParameters
    {
        $parameters = new SignatureParameters();
        $parameters->setReason("Signed in test app");
        $parameters->setLocation("At my desk");
        $parameters->setContactInfo("info@eideasy.com");
        $parameters->setSignerName("My Name");

        return $parameters;
    }
}
