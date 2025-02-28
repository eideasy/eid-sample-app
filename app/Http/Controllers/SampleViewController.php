<?php

namespace App\Http\Controllers;

use App\Services\EidEasyApiService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SampleViewController extends Controller
{
    protected EidEasyApiService $eidEasyApi;

    public function __construct(Client $client)
    {
        $this->eidEasyApi = new EidEasyApiService(
            $client,
            config('eideasy.client_id'),
            config('eideasy.secret'),
            config('eideasy.api_url')
        );
    }

    public function signAsiceFile()
    {
        return view('add-signature');
    }

    public function loginWidget()
    {
        $enabledIdentificationMethods = $this->eidEasyApi->getEnabledIdentificationMethods();

        return view('login-widget', ['enabledIdentificationMethods' => $enabledIdentificationMethods]);
    }

    public function showDownloadSignedFile(Request $request)
    {
        if ($request->has('file_id')) {
            $fileId = $request->input('file_id');
        } else {
            $fileId = Cache::get("file_id-$request->doc_id");
        }

        return view('download-signed-file', ['fileId' => $fileId]);
    }

    public function signCustomFile()
    {
        return view('sign-custom-file');
    }

    public function signLocallySample(Request $request)
    {
        $fileId = Cache::get("file_id-$request->doc_id");
        $metadata = Cache::get("prepared-files-$fileId", []);
        $availableMethods = Cache::get("available_methods-$request->doc_id", []);
        $files = [];

        foreach ($metadata as $meta) {
            $file = $meta;
            $file['fileContent'] = base64_encode(Storage::get("/unsigned/$fileId/" . $meta['fileName']));
            $files[] = $file;
        }

        $data = [
            'base_url' => config('eideasy.api_url'),
            'client_id' => config('eideasy.client_id'),
            'available_methods' => $availableMethods,
            'doc_id' => $request->doc_id,
            'file_id' => $fileId,
            'files' => $files
        ];

        return view('sign-locally-sample', $data);
    }

    public function signViaCscApiView()
    {
        return view('sign-via-csc-api');
    }
}
