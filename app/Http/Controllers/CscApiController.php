<?php

namespace App\Http\Controllers;

use App\Services\CscApiService;
use App\Services\EidEasyApiService;
use EidEasy\Signatures\Pades;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $accesToken = $this->getAccessToken($request->input('code'));

        $credentialIDs = $this->getCredentialsList($accesToken);

        $credentialInfo = $this->getCredentialInfo($accesToken, $credentialIDs['credentialIDs'][0]);

        return redirect()->to($this->credentialUrl($credentialIDs['credentialIDs'][0]));
    }

    protected function getAccessToken($code) {
        $response = Http::post(config('eideasy.api_url') . '/oauth2/token', [
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_id'     => config('eideasy.client_id'),
            'client_secret' => config('eideasy.secret'),
            'redirect_uri'  => config('eideasy.redirect_uri') . '/csc-credential'
        ]);

        if (!isset($response['access_token'])) {
            return $response->body();
        }

        return $response['access_token'];
    }

    protected function getCredentialsList($accesToken) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accesToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/credentials/list');

        return $response;
    }

    protected function getCredentialInfo($accesToken, $credentialID) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accesToken,
        ])->post(config('eideasy.api_url') . '/csc/v1/credentials/info', [
            'credentialID' => $credentialID,
        ]);

        return $response;
    }

    protected function credentialUrl($credentialID) {

        $clientId = config('eideasy.client_id');
        $apiUrl = config('eideasy.api_url');
        $redirectBackUri = config('eideasy.redirect_uri') . '/csc-credential';
        $accountToken = $this->cscApiService->createAccountToken();

        $parameters = [
            'scope'         => 'credential',
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectBackUri,
            'account_token' => $accountToken,
            'credentialID' => $credentialID,
        ];

        return  $apiUrl . '/oauth2/authorize?' . http_build_query($parameters);
    }
}
