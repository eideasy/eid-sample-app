<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class SampleViewController extends Controller
{

    public function getEmbeddedIdentification()
    {
        return view('embedded-identification');
    }

    public function signAsiceFile()
    {
        return view('add-signature');
    }

    public function showDownloadSignedFile(Request $request)
    {
        if ($request->has('file_id')) {
            $fileId = $request->input('file_id');
        } else {
            $fileId = Session::get("file_id-$request->doc_id");
        }

        return view('download-signed-file', ['fileId' => $fileId]);
    }

    public function signCustomFile()
    {
        return view('sign-custom-file');
    }

    public function signLocallySample(Request $request)
    {
        $fileId = Session::get("file_id-$request->doc_id");
        $metadata = Session::get("prepared-files-$fileId");
        $files = [];
        foreach ($metadata as $meta) {
            $file = $meta;
            $file['fileContent'] = base64_encode(Storage::get("/unsigned/$fileId/" . $meta['fileName']));
            $files[] = $file;
        }
        return view('sign-locally-sample', ['doc_id' => $request->doc_id, 'files' => $files]);
    }
}
