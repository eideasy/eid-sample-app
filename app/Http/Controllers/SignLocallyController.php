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

    public function startSigning(Request $request)
    {
        $this->validate($request, [
            [
                'client_id'   => 'required|exists:oauth_clients,id',
                'doc_id'      => 'required|exists:signing_sessions,external_doc_id',
                'sign_type'   => 'required|in:mobile-id,smart-id,id-card',
                'phone'       => ['nullable', 'required_if:sign_type,mobile-id', 'regex:/^(\+372|\+370)?[0-9]{3,12}$/'],
                'idcode'      => 'nullable|required_if:sign_type,mobile-id|required_if:sign_type,smart-id|min:5|max:15',
                'country'     => 'nullable|required_if:sign_type,mobile-id|required_if:sign_type,smart-id|in:EE,LV,LT',
                'certificate' => 'nullable|required_if:sign_type,id-card',
            ]
        ]);

        $signType = $request->sign_type;
        $apiUrl   = env('EID_API_URL') . "/api/signatures/start-signing";
        $data     = [
            'client_id' => env('EID_CLIENT_ID'),
            'secret'    => env('EID_SECRET'),
            'doc_id'    => $request->doc_id,
            'sign_type' => $request->sign_type,
        ];
        if ($signType === "id-card") {
            $data['certificate'] = $request->certificate;
        } elseif ($signType === "mobile-id") {
            $data['phone']   = $request->phone;
            $data['idcode']  = $request->idcode;
            $data['country'] = $request->country;
        } elseif ($signType === "smart-id") {
            $data['idcode']  = $request->idcode;
            $data['country'] = $request->country;
        }
        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => $data
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse()->getBody();
            Log::error("Signature finalization failed: $response");
            throw $e;
        }

        $data = json_decode((string)$response->getBody());
        return response()->json($data);
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
                    'baseline'  => 'LT'
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
