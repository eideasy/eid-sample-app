<?php

namespace App\Http\Controllers;

use App\Services\EidEasyApiService;
use EidEasy\Signatures\Asice;
use EidEasy\Signatures\Pades;
use EidEasy\Signatures\SignatureParameters;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignLocallyController extends Controller
{
    protected $client;
    protected $eidEasyApi;
    protected $pades;

    public function __construct(Client $client, Pades $pades)
    {
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
        $fileName = $request->input('filename');
        $docId    = $request->input('doc_id');

        return Storage::download("/unsigned/$docId/$fileName");
    }

    public function startSignCustomFile(Request $request)
    {
        $request->validate([
            'redirect_uri'     => 'nullable|url',
            'unsigned_file'    => 'required|array',
            'unsigned_file.*'  => 'required|file',
            'signType'         => 'required|in:local,external,digest,eseal,multisign',
            'pdf_x'            => 'nullable|min:0',
            'pdf_y'            => 'nullable|min:0',
            'pdf_page'         => 'nullable|min:0',
            'containerType'    => 'required|in:asice,pdf',
            'simple_firstname' => 'nullable|string|max:255',
            'simple_lastname'  => 'nullable|string|max:255',
            'simple_email'     => 'nullable|email',
            'simple_sms'       => ['nullable', 'regex:/^\+\d{7,15}$/'],
        ]);

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
            $mimeType    = $fileInfo->getMimeType();

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
        if ($signType === "digest" && $containerType === "pdf") {
            $signatureContainer = 'cades';
            $padesResponse      = $this->pades->getPadesDigest($sourceFiles[0]['fileContent']);
            if (!isset($padesResponse['digest'])) {
                Log::error("Pades preparation failed", $padesResponse);
                return response("Pades preparation failed");
            }
            $metaData[0]['fileContent'] = $padesResponse['digest']; // Modified PDF digest will be signed.
            $sourceFilesCache           = $sourceFiles;
            Cache::put("pades-signatureTime-$fileId", $padesResponse['signatureTime']);
        } elseif ($signType === "digest" && $containerType === "asice") {
            $signatureContainer = 'xades';
            $asice              = new Asice();
            $asiceContainer     = $asice->createAsiceContainer($sourceFiles);
            Storage::put("/unsigned/$fileId/container-$fileId.asice", $asiceContainer);
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
            'show_visual'        => !$request->boolean('hide_pdf_visual'),
        ];

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

        if ($request->has('pdf_x') || $request->has('pdf_y') || $request->has('pdf_page')) {
            $prepareParams['visual_coordinates'] = (object)[
                'page' => $request->get('pdf_page'),
                'x'    => $request->get('pdf_x'),
                'y'    => $request->get('pdf_y'),
            ];
        }

        if (count($signerContacts) > 0) {
            $prepareParams['signer'] = [
                'send_now'   => true,
                'first_name' => $request->input('simple_firstname'),
                'last_name'  => $request->input('simple_lastname'),
                'contacts'   => $signerContacts
            ];
        }

        $data = $this->eidEasyApi->prepareFiles($sourceFiles, $prepareParams);
        if (isset($data['status']) && $data['status'] !== "OK") {
            if (isset($data['message']) && !empty($data['message'])) {
                session()->flash('message', $data['message']);
                session()->flash('alert-class', 'alert-danger');

                return redirect()->back();
            }
        }
        $docId = $data['doc_id'];

        // We need to use this later to assemble or get the signed file.
        Cache::put("doc_id-$fileId", $docId);
        Cache::put("file_id-$docId", $fileId);
        Cache::put("signType-$fileId", $signType);
        Cache::put("containerType-$fileId", $containerType);

        info("File prepared for signing file_id=$fileId, doc_id=$docId");

        $clientId = config('eideasy.client_id');
        if ($signType === "external") {
            return redirect()->to(config('eideasy.api_url') . "/sign_contract_external?client_id=$clientId&doc_id=$docId");
        } elseif ($signType === 'multisign') {
            $response = $this->eidEasyApi->createSigningQueue($docId, ['has_management_page' => true]);
            Cache::put("signing_queue_id_$fileId", $response['id']);
            Cache::put('signing_queue_secret_'. $response['id'], $response['signing_queue_secret']);

            return redirect()->to($response["management_page_url"]);
        } elseif ($signType === "eseal") {
            $esealResponse = $this->eidEasyApi->createEseal($docId);
            info("Eseal create response:", $esealResponse);
            Cache::put("issandbox-$fileId", true);
            return redirect()->to(url('/show-download-signed-file') . "?file_id=$fileId");
        } else {
            Cache::put("prepared-files-$fileId", $metaData);
            return redirect()->to("/sign-locally-sample?doc_id=$docId");
        }
    }

    public function downloadSignedFile(Request $request)
    {
        info("Start downloading signed file");

        $fileId = $request->input("file_id");

        $docId         = Cache::get("doc_id-$fileId");
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

        $data = $this->eidEasyApi->downloadSignedFile($docId);

        $fileName           = $data['filename'];
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

        $headers = [
            'Content-type'        => 'application/vnd.etsi.asic-e+zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
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
