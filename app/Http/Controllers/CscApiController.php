<?php

namespace App\Http\Controllers;

use App\Services\CscApiService;
use App\Services\EidEasyApiService;
use EidEasy\Signatures\Pades;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CscApiController extends Controller
{
    protected $client;
    protected $cscApiService;
    protected $pades;

    public function __construct(Client $client, Pades $pades)
    {
        $this->client = $client;

        $this->cscApiService = new CscApiService(
            $client, config('eideasy.client_id'), config('eideasy.secret'), config('eideasy.api_url')
        );

        $pades->setGuzzle($client);
        $pades->setApiUrl(config('eideasy.pades_api_uri'));
        $this->pades = $pades;
    }

    protected function credential(Request $request)
    {
        // Step 1. Get access_token.
        $accesToken = $this->getAccessToken($request->input('code'), config('eideasy.redirect_uri') . '/csc-credential');

        $credentialIDs = $this->getCredentialsList($accesToken);
        $credentialID = $credentialIDs['credentialIDs'][0];

        $credentialInfo = $this->getCredentialInfo($accesToken, $credentialID);

        Cache::put('credentialID', $credentialID);

        return redirect()->to($this->credentialUrl($credentialID));
    }

    protected function signature(Request $request)
    {
        // Step 1. Get access_token.
        $accessToken = $this->getAccessToken($request->input('code'), config('eideasy.redirect_uri') . '/csc-signature');

        $credentialID = Cache::pull('credentialID');
        $result = $this->signHash($accessToken, $credentialID, 'jk+0rRBStHBkiytGUXOvqx9eHx1uK1mPi7z/up3k5JA=');
        ddd($result);
    }

    protected function getAccessToken($code, $redirectUri) {
        $response = Http::post(config('eideasy.api_url') . '/oauth2/token', [
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_id'     => config('eideasy.client_id'),
            'client_secret' => config('eideasy.secret'),
            'redirect_uri'  => $redirectUri,
        ]);

        if (!isset($response['access_token'])) {
            return $response->body();
        }

        return $response['access_token'];
    }

    protected function signHash($accessToken, $credentialID, $hash) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/signatures/signHash', [
            "credentialID" => $credentialID,
            "SAD" => "dummy",
            "hash" => [$hash],
            "hashAlgo" => "2.16.840.1.101.3.4.2.1",
            "signAlgo" => "1.2.840.113549.1.1.1",
        ]);

        return $response->json();
    }

    protected function getCredentialsList($accesToken) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accesToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/credentials/list');

        return $response->json();
    }

    protected function getCredentialInfo($accesToken, $credentialID) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accesToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/credentials/info', [
            'credentialID' => $credentialID,
        ]);

        return $response->json();
    }

    protected function credentialUrl($credentialID) {

        $clientId = config('eideasy.client_id');
        $apiUrl = config('eideasy.api_url');
        $redirectBackUri = config('eideasy.redirect_uri') . '/csc-signature';
        $accountToken = $this->cscApiService->createAccountToken();

        $parameters = [
            'scope'         => 'credential',
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectBackUri,
            'account_token' => $accountToken,
            'credentialID' => $credentialID,
            'hash' => 'jk+0rRBStHBkiytGUXOvqx9eHx1uK1mPi7z/up3k5JA=',
        ];

        return  $apiUrl . '/oauth2/authorize?' . http_build_query($parameters);
    }
}
