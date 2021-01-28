<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JsSdkController extends Controller
{
    public function authorizeApiCall(Request $request)
    {
        $operation = $request->get('operation');
        if ($operation === "login-start") {
            // Examine user phone/idcode and decide if to proceed with asking user to login
        } elseif ($operation === "login-complete") {
            // Decide if we want to get user data
        } else {
            // Other operations not supported yet
            Log::error("Invalid operation, to authorizing");
            abort(403);
        }

        $dataArr              = $request->get('data');
        $dataArr['timestamp'] = round(microtime(true) * 1000);

        $secret = env('EID_SECRET');

        $data = json_encode($dataArr);
        $hmac = hash_hmac("SHA256", $data, $secret, true);
        return response()->json([
            'hmac'    => base64_encode($hmac),
            'payload' => $dataArr
        ]);
    }

    public function decryptUserData(Request $request)
    {

    }
}
