<?php

namespace App\Services;

use EidEasy\Api\EidEasyApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class EidEasyApiService extends EidEasyApi
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

    public function getSigningQueue(int $signingQueueId, string $signingQueueSecret): array
    {
        $call = function () use ($signingQueueSecret, $signingQueueId) {
//            $data = [
//                'client_id' => $this->clientId,
//                'secret'    => $this->secret,
//            ];

            return $this->guzzle->get($this->apiUrl . "/api/signatures/signing-queues/$signingQueueId", [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer $signingQueueSecret",
                ],
            ]);
        };

        return $this->formatResponse($call);
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
