<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SignLocallyController extends Controller
{

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function downloadUnSignedFile(Request $request)
    {
        $files   = session("prepared-files-$request->doc_id");
        $file    = $files[$request->file_id];
        $headers = [
            'Content-type'        => $file['mimeType'],
            'Content-Disposition' => 'attachment; filename="' . $file['fileName'] . '"',
        ];

        return response()->make(base64_decode($file['fileContent']), 200, $headers);
    }

    public function completeIdCardSignature(Request $request)
    {
        info("ID card signature finalization");
        $apiUrl = env('EID_API_URL') . "/api/signatures/id-card/complete";

        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id' => env('EID_CLIENT_ID'),
                    'secret'    => env('EID_SECRET'),
                    'doc_id'    => $request->doc_id,
                    'signature' => $request->signature,
                ]
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse()->getBody();
            Log::error("Signature finalization failed: $response");
            throw $e;
        }

        $data = json_decode((string)$response->getBody());

        Storage::put($data->fileName, base64_decode($data->container));

        return response()->json([
            'status'   => "OK",
            'fileName' => $data->fileName,
        ]);
    }
}
