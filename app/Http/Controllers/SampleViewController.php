<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SampleViewController extends Controller
{
    public function signAsiceFile()
    {
        return view('add-signature');
    }

    public function loginWidget()
    {
        return view('login-widget');
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
        $files = [];

        foreach ($metadata as $meta) {
            $file = $meta;
            $file['fileContent'] = base64_encode(Storage::get("/unsigned/$fileId/" . $meta['fileName']));
            $files[] = $file;
        }

        $data = [
            'client_id' => config('eideasy.client_id'),
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
