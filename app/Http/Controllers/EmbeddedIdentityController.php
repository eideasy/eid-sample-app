<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmbeddedIdentityController extends Controller
{
    public function finishMobileidLogin(Request $request)
    {
        $client = app(Client::class);

        $data = $request->validate([
            'token' => 'required',
        ]);

        try {
            $url      = env("EID_API_URL") . "/api/identity/" . env("EID_CLIENT_ID") . "/mobile-id/complete";
            $response = $client->post($url, [
                    'json' => [
                        'secret' => env('EID_SECRET'),
                        'token'  => $data['token'],
                    ]
                ]
            );
        } catch (ClientException $exception) {
            Log::error("Mobile-ID login complete failed", [$exception]);
            $response     = $exception->getResponse();
            $responseData = json_decode((string)$response->getBody());
            return response()->json([
                'message' => $responseData->message,
            ], $response->getStatusCode());
        }

        $responseData = json_decode((string)$response->getBody());
        unset($responseData->email);

        return response()->json($responseData);
    }

    public function startMobileidLogin(Request $request)
    {
        $client = app(Client::class);

        $data = $request->validate([
            'phone'  => 'required',
            'idcode' => 'required',
        ]);

        try {
            $url      = env("EID_API_URL") . "/api/identity/" . env("EID_CLIENT_ID") . "/mobile-id/start";
            $response = $client->post($url, [
                    'json' => [
                        'secret' => env('EID_SECRET'),
                        'phone'  => $data['phone'],
                        'idcode' => $data['idcode']
                    ]
                ]
            );
        } catch (ClientException $exception) {
            Log::error("Mobile-ID login start failed", [$exception]);
            $response     = $exception->getResponse();
            $responseData = json_decode((string)$response->getBody());
            return response()->json([
                'message' => $responseData->message,
            ], $response->getStatusCode());
        }

        $responseData = json_decode((string)$response->getBody());

        return response()->json([
            'challenge' => $responseData->challenge,
            'token'     => $responseData->token,
        ]);
    }

    public function finishSmartIdLogin(Request $request)
    {
        $client = app(Client::class);

        $data = $request->validate([
            'token' => 'required',
        ]);

        try {
            $url      = env("EID_API_URL") . "/api/identity/" . env("EID_CLIENT_ID") . "/smart-id/complete";
            $response = $client->post($url, [
                    'json' => [
                        'secret' => env('EID_SECRET'),
                        'token'  => $data['token'],
                    ]
                ]
            );
        } catch (ClientException $exception) {
            Log::error("Smart-ID login complete failed", [$exception]);
            $response     = $exception->getResponse();
            $responseData = json_decode((string)$response->getBody());
            return response()->json([
                'message' => $responseData->message,
            ], $response->getStatusCode());
        }

        $responseData = json_decode((string)$response->getBody());
        unset($responseData->email);

        return response()->json($responseData);
    }

    public function startSmartIdLogin(Request $request)
    {
        $client = app(Client::class);

        $data = $request->validate([
            'country' => 'in:EE,LV,LT',
            'idcode'  => 'required',
        ]);

        try {
            $url      = env("EID_API_URL") . "/api/identity/" . env("EID_CLIENT_ID") . "/smart-id/start";
            $response = $client->post($url, [
                    'json' => [
                        'secret'  => env('EID_SECRET'),
                        'country' => $data['country'],
                        'idcode'  => $data['idcode']
                    ]
                ]
            );
        } catch (ClientException $exception) {
            Log::error("Smart-ID login start failed", [$exception]);
            $response     = $exception->getResponse();
            $responseData = json_decode((string)$response->getBody());
            return response()->json([
                'message' => $responseData->message,
            ], $response->getStatusCode());
        }

        $responseData = json_decode((string)$response->getBody());

        return response()->json([
            'challenge' => $responseData->challenge,
            'token'     => $responseData->token,
        ]);
    }
}
