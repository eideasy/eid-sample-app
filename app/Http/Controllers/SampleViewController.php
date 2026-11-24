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
        $query = $request->query();
        $fileId = $query['file_id'] ?? null;
        $docId = $query['doc_id'] ?? null;
        if ($fileId === null && is_string($docId) && preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $docId)) {
            $fileId = Cache::get("file_id-$docId");
        }

        if (!is_string($fileId) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $fileId) || !(Cache::has("doc_id-$fileId") || $request->session()->has("doc_id-$fileId"))) {
            return redirect('/sign-custom-file')->with('message', 'This signing session is unavailable. Prepare the files again.');
        }

        return response()->view('download-signed-file', ['fileId' => $fileId])->header('Cache-Control', 'no-store');
    }

    public function signCustomFile()
    {
        return view('sign-custom-file');
    }

    public function signLocallySample(Request $request)
    {
        $docId = $request->query()['doc_id'] ?? null;
        $fileId = is_string($docId) && preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $docId)
            ? Cache::get("file_id-$docId")
            : null;
        $files = $fileId ? Cache::get("prepared-files-$fileId", []) : [];

        if ($files === []) {
            return redirect('/sign-custom-file')->with('message', 'This signing session is unavailable. Prepare the files again.');
        }

        foreach ($files as &$file) {
            $file['pdfPreview'] = null;
            if ($file['mimeType'] === 'application/pdf') {
                $content = Storage::get("/unsigned/$fileId/" . $file['fileName']);
                if (is_string($content) && str_starts_with($content, '%PDF-')) {
                    $file['pdfPreview'] = base64_encode($content);
                }
            }
        }
        unset($file);

        return response()->view('sign-locally-sample', [
            'base_url' => config('eideasy.api_url'),
            'client_id' => config('eideasy.client_id'),
            'available_methods' => Cache::get("available_methods-$docId", []),
            'doc_id' => $docId,
            'file_id' => $fileId,
            'files' => $files,
        ])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function signViaCscApiView()
    {
        return view('sign-via-csc-api');
    }
}
