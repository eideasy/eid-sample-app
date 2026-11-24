<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function startOAuth(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('demo.oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('eideasy.client_id'),
            'redirect_uri' => config('eideasy.redirect_uri'),
            'response_type' => 'code',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away(rtrim(config('eideasy.api_url'), '/') . '/oauth/authorize?' . $query);
    }

    public function getWelcome(Request $request)
    {
        $query = $request->query();
        if (!array_key_exists('code', $query) && !array_key_exists('error', $query)) {
            return view('welcome');
        }

        $state = $query['state'] ?? null;
        $expected = $request->session()->get('demo.oauth_state');
        if (!is_string($state) || !is_string($expected) || !hash_equals($expected, $state)) {
            return view('welcome', ['error' => 'Start a new OAuth login from this page.']);
        }

        $request->session()->forget('demo.oauth_state');
        $code = $query['code'] ?? null;
        if (array_key_exists('error', $query) || !is_string($code) || $code === '' || strlen($code) > 8192) {
            return view('welcome', ['error' => 'The login was cancelled or could not be completed.']);
        }

        try {
            $userData = $this->getUserData($code);
        } catch (ConnectionException) {
            $userData = null;
        }

        return response()->view('welcome', $userData
            ? ['userData' => $userData]
            : ['error' => 'eID Easy could not complete the login. Please try again.']
        )->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    protected function getUserData(string $code): ?array
    {
        // Step 1. Get access_token.
        $response = Http::post(config('eideasy.api_url') . '/oauth/access_token', [
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_id'     => config('eideasy.client_id'),
            'client_secret' => config('eideasy.secret'),
            'redirect_uri'  => config('eideasy.redirect_uri')
        ]);
        $accessToken = $response->json('access_token');
        if (!$response->successful() || !is_string($accessToken) || $accessToken === '') {
            return null;
        }

        // Step 2. Get user data with access_token.
        $response = Http::get(config('eideasy.api_url') . '/api/v2/user_data', [
            'access_token' => $accessToken,
        ]);

        $responseData = $response->json();
        if (!$response->successful() || !is_array($responseData)) {
            return null;
        }

        if (config('eideasy.notify_email')) {
            Mail::send([], [], function ($message) use ($responseData) {
                $responseData = Arr::only($responseData, ['idcode', 'firstname', 'lastname', 'country', 'current_login_method']);
                $message->to(config('eideasy.notify_email'))
                        ->subject("New login from eID Easy demo app")
                        ->html("New user testing the service: " . e(json_encode($responseData, JSON_INVALID_UTF8_SUBSTITUTE)));
            });
        }

        return $responseData;
    }
}
