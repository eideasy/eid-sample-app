<?php

namespace App\Services;

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CscApiService extends EidEasyApi
{
    private string $clientId;
    private string $apiUrl;
    private string $secret;
    private Client $guzzle;
    private $pades;

    public function __construct(
        Pades $pades,
        Client $guzzle = null,
        string $clientId = null,
        string $secret = null,
        string $apiUrl = "https://id.eideasy.eu"
    ) {
        parent::__construct($guzzle, $clientId, $secret, $apiUrl);

        $this->apiUrl = $apiUrl;
        $this->guzzle = $guzzle;
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->pades = $pades;
    }

    public function createAccountToken() {
        $tokenId = md5($this->clientId . ':' . Str::random() . ':' . round(microtime(true) * 1000));

        $accountToken = JWT::encode([
            'sub' => config('eideasy.adobe_account_id'),
            'iat' => time(),
            'jti' => $tokenId,
            'iss' => 'eid-sample-app',
            'azp' => $this->clientId,
        ], hash('sha256', config('eideasy.secret'), true), 'HS256');

        return $accountToken;
    }
}
