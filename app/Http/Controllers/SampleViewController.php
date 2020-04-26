<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class SampleViewController extends Controller
{
    public function signLocallySample()
    {
        $fileContent = Storage::disk('samples')->get('payment.xml');

        return view('sign-locally-sample', compact("fileContent"));
    }
}
