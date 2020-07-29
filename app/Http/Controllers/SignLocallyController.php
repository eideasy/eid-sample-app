<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignLocallyController extends Controller
{

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createContainerForSigning(Request $request)
    {
        info("Preparing signing payment.xml with");
        $apiUrl = env('EID_API_URL') . "/api/signatures/create-container-for-signing";

        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id'       => env('EID_CLIENT_ID'),
                    'secret'          => env('EID_SECRET'),
                    'returnContainer' => false, //set true if you want to verify the container that will be signed
                    'files'           => [
                        [
                            'fileName'    => 'payment.xml',
                            'fileContent' => base64_encode(Storage::disk('samples')->get('payment.xml')),
                            'mimeType'    => 'application/xml'
                        ]
                    ],
                    'certificate'     => $request->certificate
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Log::error("Container creation failed: $response");
            return $response;
        }

        $data = json_decode((string)$response->getBody());

        info("Container prepared, hash to be signed is $data->signedInfoDigest, prepared container id=$data->doc_id");

        return response()->json([
            'hash'   => $data->signedInfoDigest,
            'doc_id' => $data->doc_id
        ]);
    }

    public function finalizeSignature(Request $request)
    {
        info("Preparing signature finalization");
        $apiUrl = env('EID_API_URL') . "/api/signatures/finalize-external-signature";

        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => [
                    'client_id' => env('EID_CLIENT_ID'),
                    'secret'    => env('EID_SECRET'),
                    'doc_id'    => $request->doc_id,
                    'signature' => $request->signature
                ]
            ]);
        } catch (ClientException $e) {
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
