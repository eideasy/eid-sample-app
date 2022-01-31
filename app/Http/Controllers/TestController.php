<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;
use EidEasy\Signatures\SignatureParameters;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    protected $client;
    protected $eidEasyApi;
    protected $pades;

    public function __construct(Client $client, EidEasyApi $eidEasyApi, Pades $pades)
    {
        $this->client = $client;

        $eidEasyApi->setGuzzle($client);
        $eidEasyApi->setApiUrl(config('eideasy.api_url'));
        $eidEasyApi->setClientId(config('eideasy.client_id'));
        $eidEasyApi->setSecret(config('eideasy.secret'));
        $this->eidEasyApi = $eidEasyApi;

        $pades->setGuzzle($client);
        $pades->setApiUrl(config('eideasy.pades_api_uri'));
        $this->pades = $pades;
    }

    public function customCadesDigest(Request $request)
    {
        $docId = $request->get('doc_id');
        $metadata = Cache::get("prepared-files-$docId", []);

        if (empty($metadata)) {
            return response()->json([
                [
                    "message" => "Missing signed file contents",
                    "doc_id" => $docId,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $signerName = $request->get('signer_name');
        $signerIdCode = $request->get('signer_idcode');
        $signatureParameters = new SignatureParameters(
            null,
            $signerName,
            $signerIdCode
        );

        $padesResponse = $this->pades->getPadesDigest($metadata[0]['fileContent']);

        if (!isset($padesResponse['digest'])) {
            return response()->json([
                [
                    "message" => "Missing digest",
                    "response" => json_encode($padesResponse),
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'digest' => $padesResponse['digest'],
        ], Response::HTTP_OK);
    }
}
