<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AddSignatureController extends Controller
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function startAddingSignature(Request $request)
    {
        $request->validate([
            'redirect_uri' => 'nullable|url',
            'signed_file'  => 'required|file'
        ]);
        info("Start preparing adding signature");

        $apiUrl = config('eideasy.api_url') . "/api/signatures/prepare-add-signature";

        $fileId = Str::random();
        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id'          => config('eideasy.client_id'),
                    'secret'             => config('eideasy.secret'),
                    'signature_redirect' => $request->redirect_uri ?? url('/show-download-signed-file') . "?file_id=$fileId",
                    'filename'           => $request->file('signed_file')->getClientOriginalName(),
                    'container'          => base64_encode(file_get_contents($request->file('signed_file')->path()))
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Log::error("Add signature preparation failed: $response");
            return $response;
        }

        $data = json_decode((string)$response->getBody());
        Session::put("doc_id-$fileId", $data->doc_id); //Keep for later so we can download the file
        info("Signed file prepared for adding signature: $fileId, doc_id=$data->doc_id");

        $clientId = config('eideasy.client_id');

        return redirect()->to(config('eideasy.api_url') . "/add-signature?client_id=$clientId&doc_id=$data->doc_id");
    }

    public function downloadSignedFile(Request $request)
    {
        info("Start downloading signed file");

        $apiUrl = config('eideasy.api_url') . "/api/signatures/download-signed-asice";

        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id' => config('eideasy.client_id'),
                    'secret'    => config('eideasy.secret'),
                    'doc_id'    => Session::get("doc_id-$request->file_id"), //doc_id was saved to session when preparing the file for download
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Log::error("Downloading signed file failed: $response");
            return $response;
        }

        $data = json_decode((string)$response->getBody());

        info("Signed file downloaded");

        $headers = [
            'Content-type'        => 'application/vnd.etsi.asic-e+zip',
            'Content-Disposition' => 'attachment; filename="' . $data->filename . '"',
        ];

        return Response::make(base64_decode($data->signed_file_contents), 200, $headers);
    }
}
