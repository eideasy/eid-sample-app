<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $fileId = $request->file_id;
        return view('download-signed-file', ['fileId' => $fileId]);
    }

    public function signCustomFile()
    {
        return view('sign-custom-file');
    }

    public function signLocallySample()
    {
        $fileContent = Storage::disk('samples')->get('payment.xml');

        return view('sign-locally-sample', ["fileContent" => $fileContent]);
    }
}
