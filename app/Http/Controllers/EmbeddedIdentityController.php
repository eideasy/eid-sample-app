<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmbeddedIdentityController extends Controller
{
    public function finishIdCardLogin(Request $request)
    {
        $data = $request->validate([
            'token'   => 'required',
            'country' => 'required',
            'lang'    => 'nullable'
        ]);

        $url      = config('eideasy.api_url') . "/api/identity/" . config('eideasy.client_id') . "/id-card/complete";
        $response = Http::post($url, [
            'secret'  => config('eideasy.secret'),
            'token'   => $data['token'],
            'country' => $data['country'],
            'lang'    => $data['lang'] ?? 'en',
        ]);

        $responseData = $response->json();

        if (env('NOTIFY_EMAIL') && is_array($responseData)) {
            Mail::send([], [], function ($message) use ($responseData) {
                $responseData = Arr::only($responseData, ['idcode', 'firstname', 'lastname', 'country', 'current_login_method']);
                $message->to(env('NOTIFY_EMAIL'))
                        ->subject("New login")
                        ->setBody("New user testing the service: " . json_encode($responseData));
            });
        }

        return response()->json($responseData);
    }

    public function finishMobileidLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
        ]);

        $client = app(Client::class);
        try {
            $url      = config('eideasy.api_url') . "/api/identity/" . config('eideasy.client_id') . "/mobile-id/complete";
            $response = $client->post($url, [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'json'    => [
                        'secret' => config('eideasy.secret'),
                        'token'  => $data['token'],
                        'lang'   => 'en',
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
        if ($responseData->status !== "OK") {
            return response()->json([
                'message' => $responseData->message,
            ], 400);
        }

        if (env('NOTIFY_EMAIL') && is_array($responseData)) {
            Mail::send([], [], function ($message) use ($responseData) {
                $responseData = Arr::only($responseData, ['idcode', 'firstname', 'lastname', 'country', 'current_login_method']);
                $message->to(env('NOTIFY_EMAIL'))
                        ->subject("New login from eID Easy demo app")
                        ->setBody("New user testing the service: " . json_encode($responseData));
            });
        }

        return response()->json($responseData);
    }

    public function startMobileidLogin(Request $request)
    {
        $client = app(Client::class);

        $data = $request->validate([
            'idcode' => 'required|size:11',
            'phone'  => 'required|min:6|max:15|startsWith:+372,+370',
        ]);

        try {
            $url      = config('eideasy.api_url') . "/api/identity/" . config('eideasy.client_id') . "/mobile-id/start";
            $response = $client->post($url, [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'json'    => [
                        'secret' => config('eideasy.secret'),
                        'phone'  => $data['phone'],
                        'idcode' => $data['idcode'],
                        'lang'   => 'en',
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
            $url      = config('eideasy.api_url') . "/api/identity/" . config('eideasy.client_id') . "/smart-id/complete";
            $response = $client->post($url, [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'json'    => [
                        'secret' => config('eideasy.secret'),
                        'token'  => $data['token'],
                        'lang'   => 'en',
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

        if (env('NOTIFY_EMAIL') && is_array($responseData)) {
            Mail::send([], [], function ($message) use ($responseData) {
                $responseData = Arr::only($responseData, ['idcode', 'firstname', 'lastname', 'country', 'current_login_method']);
                $message->to(env('NOTIFY_EMAIL'))
                        ->subject("New login from eID Easy demo app")
                        ->setBody("New user testing the service: " . json_encode($responseData));
            });
        }

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
            $url      = config('eideasy.api_url') . "/api/identity/" . config('eideasy.client_id') . "/smart-id/start";
            $response = $client->post($url, [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'json'    => [
                        'secret'  => config('eideasy.secret'),
                        'country' => $data['country'],
                        'idcode'  => $data['idcode'],
                        'lang'    => 'en',
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
