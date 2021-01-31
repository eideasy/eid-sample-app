<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Asice;
use EidEasy\Signatures\Pades;
use EidEasy\Signatures\SignatureParameters;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignLocallyController extends Controller
{
    protected $client;
    protected $eidEasyApi;
    protected $pades;

    public function __construct(Client $client, EidEasyApi $eidEasyApi, Pades $pades)
    {
        $this->client = $client;

        $eidEasyApi->setGuzzle($client);
        $eidEasyApi->setApiUrl(config('eideasy.api_url'));
        $eidEasyApi->setClientId(config('eideasy.client_id'));
        $eidEasyApi->setSecret(config('eideasy.secret'));
        $this->eidEasyApi = $eidEasyApi;

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
            'redirect_uri'    => 'nullable|url',
            'unsigned_file'   => 'required|array',
            'unsigned_file.*' => 'required|file',
            'signType'        => 'required|in:local,external,digest,eseal',
            'containerType'   => 'required|in:asice,pdf',
        ]);


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
            if ($containerType === "pdf") {
                break;
            }
        }

        // Handle digest based signature starting.
        $signatureContainer = $containerType;
        if ($signType === "digest" && $containerType === "pdf") {
            $signatureContainer = 'cades';
            $padesResponse      = $this->pades->getPadesDigest($sourceFiles[0]['fileContent'], $this->getSampleSignatureParams());
            if (!isset($padesResponse['digest'])) {
                Log::error("Pades preparation failed", $padesResponse);
                return response("Pades preparation failed");
            }
            $metaData[0]['fileContent'] = $padesResponse['digest']; // Modified PDF digest will be signed.
            Session::put("pades-signatureTime-$fileId", $padesResponse['signatureTime']);

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

        $data  = $this->eidEasyApi->prepareFiles($sourceFiles, [
            'signature_redirect' => $request->redirect_uri ?? url('/show-download-signed-file') . "?file_id=$fileId",
            'container_type'     => $signatureContainer,
            'files'              => $sourceFiles,
            'baseline'           => 'LT',
            'notification_state' => [
                'time' => now()->toIso8601String()
            ],
        ]);
        $docId = $data['doc_id'];

        // We need to use this later to assemble or get the signed file.
        Session::put("doc_id-$fileId", $docId);
        Session::put("file_id-$docId", $fileId);
        Session::put("signType-$fileId", $signType);
        Session::put("containerType-$fileId", $containerType);

        info("File prepared for signing file_id=$fileId, doc_id=$docId");

        $clientId = config('eideasy.client_id');
        if ($signType === "external") {
            return redirect()->to(config('eideasy.api_url') . "/sign_contract_external?client_id=$clientId&doc_id=$docId");
        } elseif ($signType === "eseal") {
            $esealResponse = $this->eidEasyApi->createEseal($docId);
            info("Eseal create response:", $esealResponse);
            return redirect()->to(url('/show-download-signed-file') . "?file_id=$fileId");
        } else {
            Session::put("prepared-files-$fileId", $metaData);
            return redirect()->to("/sign-locally-sample?doc_id=$docId");
        }
    }

    public function downloadSignedFile(Request $request)
    {
        info("Start downloading signed file");

        $fileId = $request->input("file_id");

        $docId         = Session::get("doc_id-$fileId");
        $signType      = Session::get("signType-$fileId");
        $containerType = Session::get("containerType-$fileId");

        $data = $this->eidEasyApi->downloadSignedFile($docId);

        $fileName           = $data['filename'];
        $signedFileContents = $data['signed_file_contents'];

        // Assemble signed file and make sure its in binary form before downloading.
        if ($signType === "digest" && $containerType === "pdf") {
            $metaData      = Session::get("prepared-files-$fileId");
            $fileName      = $metaData[0]['fileName'];
            $signatureTime = Session::get("pades-signatureTime-$fileId");

            $unsignedFile       = Storage::get("/unsigned/$fileId/" . $fileName);
            $padesResponse      = $this->pades->addSignaturePades($unsignedFile, $signatureTime, $signedFileContents, $this->getSampleSignatureParams());
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
