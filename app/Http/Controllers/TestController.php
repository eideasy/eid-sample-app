<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TestController extends Controller
{
    protected $client;
    protected $eidEasyApi;

    public function __construct(Client $client, EidEasyApi $eidEasyApi)
    {
        $this->client = $client;

        $eidEasyApi->setGuzzle($client);
        $eidEasyApi->setApiUrl(config('eideasy.api_url'));
        $eidEasyApi->setClientId(config('eideasy.client_id'));
        $eidEasyApi->setSecret(config('eideasy.secret'));
        $this->eidEasyApi = $eidEasyApi;
    }

    public function customCadesDigest(Request $request)
    {
        $docId = $request->get('doc_id');

        $data = $this->eidEasyApi->downloadSignedFile($docId);

        $dataArr              = $data;
        $dataArr['timestamp'] = round(microtime(true) * 1000);

        $secret = env('EID_SECRET');

        $data = json_encode($dataArr);
        $hmac = hash_hmac("SHA256", $data, $secret, true);

        return response()->json([
            'digest' => $hmac,
        ]);
    }
}
