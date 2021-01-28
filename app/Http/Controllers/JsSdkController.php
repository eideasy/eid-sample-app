<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JsSdkController extends Controller
{
    public function authorizeApiCall(Request $request)
    {
        $operation = $request->input('operation');
        if ($operation === "login-start") {
            // Examine user phone/idcode and decide if to proceed with asking user to login
        } elseif ($operation === "login-complete") {
            // Decide if we want to get user data
        } else {
            // Other operations not supported yet
            Log::error("Invalid operation, to authorizing");
            abort(403, "Invalid operation");
        }

        $dataArr              = $request->get('data');
        $dataArr['timestamp'] = round(microtime(true) * 1000);

        $secret = env('EID_SECRET');

        $data = json_encode($dataArr);
        $hmac = hash_hmac("SHA256", $data, $secret, true);
        return response()->json([
            'hmac'    => base64_encode($hmac),
            'payload' => $data
        ]);
    }

    public function decryptUserData(Request $request)
    {
        $hmac    = $request->input('hmac');
        $iv      = $request->input('iv');
        $payload = $request->input('payload');

        $secret = env('EID_SECRET');

        $verificationHmac = hash_hmac("SHA256", $payload, $secret, true);
        $verificationHmac = base64_encode($verificationHmac);
        if ($verificationHmac !== $hmac) {
            Log::error("Invalid HMAC $hmac vs $verificationHmac");
            abort(403, "Invalid HMAC");
        }

        $decryptedPayloadString = openssl_decrypt($payload, "AES-256-CBC", $secret, 0, base64_decode($iv));

        $data = json_decode($decryptedPayloadString);

        $millis      = $data->timestamp;
        $requestTime = Carbon::createFromTimestampMs($millis);
        $now         = now();
        if ($requestTime->diffInSeconds($now) > 60) {
            Log::error("Timestamp expired $requestTime, $now");
            abort(403, "Expired timestamp, Timestamp $requestTime, now $now");
        }

        unset($data->timestamp);
        unset($data->eid_nonce);

        return response()->json($data);
    }
}
