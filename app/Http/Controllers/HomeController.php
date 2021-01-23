<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function getWelcome(Request $request)
    {
        $authoriseUri = config('eideasy.api_url') . '/oauth/authorize?client_id=' . config('eideasy.client_id')
            . '&redirect_uri=' . config('eideasy.redirect_uri') . '&response_type=code';

        // If request has parameter code then there is OAuth 2.0 return and we get user data.
        if ($request->code !== null) {
            $userData = $this->getUserData($request->code);
            return view('welcome', ['authorizeUri' => $authoriseUri, "userData" => $userData]);
        }

        // Show list of sample apps.
        return view('welcome', ['authorizeUri' => $authoriseUri]);
    }

    protected function getUserData($code)
    {
        // Step 1. Get access_token.
        $response = Http::post(config('eideasy.api_url') . '/oauth/access_token', [
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_id'     => config('eideasy.client_id'),
            'client_secret' => config('eideasy.secret'),
            'redirect_uri'  => config('eideasy.redirect_uri')
        ]);
        if (!isset($response['access_token'])) {
            return $response->body();
        }
        $accesToken = $response['access_token'];

        // Step 2. Get user data with access_token.
        $response = Http::get(config('eideasy.api_url') . '/api/v2/user_data', [
            'access_token' => $accesToken,
        ]);

        $responseData = $response->json();

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
}
