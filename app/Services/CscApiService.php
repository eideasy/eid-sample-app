<?php

namespace App\Services;

use EidEasy\Api\EidEasyApi;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;

class CscApiService extends EidEasyApi
{
    private string $clientId;
    private string $apiUrl;
    private string $secret;
    private Client $guzzle;

    public function __construct(Client $guzzle = null, string $clientId = null, string $secret = null, string $apiUrl = "https://id.eideasy.eu")
    {
        parent::__construct($guzzle, $clientId, $secret, $apiUrl);

        $this->apiUrl = $apiUrl;
        $this->guzzle = $guzzle;
        $this->clientId = $clientId;
        $this->secret = $secret;
    }

    public function createAccountToken() {
        $tokenId = md5($this->clientId . ':' . Str::random() . ':' . round(microtime(true) * 1000));
        $adobeAccountId = '123456abcde';

        $accountToken = JWT::encode([
            'sub' => $adobeAccountId,
            'iat' => time(),
            'jti' => $tokenId,
            'iss' => 'ThisShouldBeSignatureApplicationName',
            'azp' => $this->clientId,
        ], hash('sha256', config('eideasy.secret'), true), 'HS256');

        return $accountToken;
    }

    private function formatResponse(\Closure $request)
    {
        try {
            $response = $request();
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if (!$response) {
                return [
                    'status' => 'error',
                    'message' => 'No response body: ' . $e->getMessage(),
                ];
            }
            $body = $response->getBody()->getContents();
            $jsonBody = json_decode($body, true);
            if (!$jsonBody) {
                return [
                    'status' => 'error',
                    'message' => 'Response not json: ' . $body,
                ];
            }

            if (!array_key_exists('status', $jsonBody)) {
                $jsonBody['status'] = 'error';
            }

            return $jsonBody;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
