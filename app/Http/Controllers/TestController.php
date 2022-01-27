<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;
use EidEasy\Signatures\SignatureParameters;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
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
        $data = $this->eidEasyApi->downloadSignedFile($docId);

        if(!isset($data['signed_file_contents'])){
            return response()->json([
                ["message" => "Missing signed file contents",],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $signerName = $request->get('signer_name');
        $signerIdCode = $request->get('signer_idcode');
        $signatureParameters = new SignatureParameters(
            null,
            $signerName,
            $signerIdCode
        );

        $padesResponse = $this->pades->getPadesDigest($data['signed_file_contents'], $signatureParameters);

        if(!isset($padesResponse['digest']) ){
            return response()->json([
                ["message" => "Missing digest",],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'digest' => $padesResponse['digest'],
        ]);
    }
}
