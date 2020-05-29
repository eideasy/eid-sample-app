<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SignPreparedDocumentController extends Controller
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function startSignCustomFile(Request $request)
    {
        $request->validate([
            'redirect_uri'  => 'nullable|url',
            'unsigned_file' => 'required|file'
        ]);
        info("Start preparing signing");

        $apiUrl = env('EID_API_URL') . "/api/v2/prepare_external_doc";

        $fileId = Str::random();
        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id'          => env('EID_CLIENT_ID'),
                    'secret'             => env('EID_SECRET'),
                    'signature_redirect' => $request->redirect_uri ?? url('/show-download-signed-file') . "?file_id=$fileId",
                    'filename'           => $request->file('unsigned_file')->getClientOriginalName(),
                    'file_content'       => base64_encode(file_get_contents($request->file('unsigned_file')->path()))
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Log::error("Container creation failed: $response");
            return $response;
        }

        $data = json_decode((string)$response->getBody());
        Cache::put($fileId, $data->doc_id); //Keep for later so we can download the file
        info("File prepared for signing file_id=$fileId, doc_id=$data->doc_id");

        $clientId = env('EID_CLIENT_ID');

        return redirect()->to(env('EID_API_URL') . "/sign_contract_external?client_id=$clientId&doc_id=$data->doc_id");
    }

    public function downloadSignedFile(Request $request)
    {
        info("Start downloading signed file");

        $apiUrl = env('EID_API_URL') . "/api/v2/download_external_signed_doc";

        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id' => env('EID_CLIENT_ID'),
                    'secret'    => env('EID_SECRET'),
                    'doc_id'    => Cache::get($request->file_id), //doc_id was saved to cache when preparing the file for download
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
