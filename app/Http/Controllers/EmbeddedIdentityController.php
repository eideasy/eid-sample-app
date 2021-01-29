<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;
use EidEasy\EidEasyParams;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class EmbeddedIdentityController extends Controller
{
    protected $eidEasyApi;

    public function __construct()
    {
        $api = app(EidEasyApi::class);
        $api->setClientId(env('EID_CLIENT_ID'));
        $api->setSecret(env('EID_SECRET'));
        $api->setApiUrl(env('EID_API_URL'));

        $this->eidEasyApi = $api;
    }

    public function startLogin(Request $request)
    {
        $method = $request->input('method');
        if ($method === "smartid") {
            return $this->startSmartIdLogin($request);
        } elseif (in_array($method, [EidEasyParams::EE_MOBILEID_LOGIN, EidEasyParams::LT_MOBILEID_LOGIN])) {
            return $this->startMobileidLogin($request);
        }

        abort(404, "Invalid method $method");
    }

    public function finishLogin(Request $request)
    {
        $method = $request->input('method');
        if ($method === "smartid") {
            return $this->finishSmartIdLogin($request);
        } elseif (in_array($method, [EidEasyParams::EE_MOBILEID_LOGIN, EidEasyParams::LT_MOBILEID_LOGIN])) {
            return $this->finishMobileidLogin($request);
        } elseif (in_array($method, [
            EidEasyParams::EE_IDCARD_LOGIN,
            EidEasyParams::LV_IDCARD_LOGIN,
            EidEasyParams::LT_IDCARD_LOGIN,
            EidEasyParams::BE_IDCARD_LOGIN,
            EidEasyParams::FI_IDCARD_LOGIN,
            EidEasyParams::PT_IDCARD_LOGIN,
            EidEasyParams::RS_IDCARD_LOGIN
        ])) {
            return $this->finishIdCardLogin($request);
        }

        abort(404, "Invalid method $method");
    }

    public function finishIdCardLogin(Request $request)
    {
        $data = $request->validate([
            'token'   => 'required',
            'country' => 'required',
            'method'  => 'required',
            'lang'    => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($data['method'], $data);

        $this->notifyLogin($responseData);
        unset($responseData['email']);

        return response()->json($responseData);
    }

    public function finishMobileidLogin(Request $request)
    {
        $data = $request->validate([
            'token'  => 'required',
            'method' => 'required',
            'lang'   => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($data['method'], $data);

        if ($responseData['status'] !== "OK") {
            return response()->json([
                'message' => $responseData['message'],
            ], 400);
        }
        $this->notifyLogin($responseData);
        unset($responseData['email']);

        return response()->json($responseData);
    }

    public function startMobileidLogin(Request $request)
    {
        $data = $request->validate([
            'idcode' => 'required|size:11',
            'phone'  => 'required|min:6|max:15|startsWith:+372,+370',
            'method' => 'required',
            'lang'   => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->startIdentification($data['method'], $data);
        Session::put('method-' . $responseData['token'], $data['method']);

        return response()->json([
            'challenge' => $responseData['challenge'],
            'token'     => $responseData['token'],
        ]);
    }

    public function finishSmartIdLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'lang'  => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification('smartid', $data);
        unset($responseData['email']);
        $this->notifyLogin($responseData);

        return response()->json($responseData);
    }

    public function startSmartIdLogin(Request $request)
    {
        $data = $request->validate([
            'country' => 'in:EE,LV,LT',
            'idcode'  => 'required',
            'method'  => 'required',
            'lang'    => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->startIdentification('smartid', $data);

        return response()->json($responseData);
    }

    protected function notifyLogin($responseData)
    {
        if (env('NOTIFY_EMAIL') && is_array($responseData)) {
            Mail::send([], [], function ($message) use ($responseData) {
                $responseData = Arr::only($responseData, ['idcode', 'firstname', 'lastname', 'country', 'current_login_method']);
                $message->to(env('NOTIFY_EMAIL'))
                        ->subject("New login from eID Easy demo app")
                        ->setBody("New user testing the service: " . json_encode($responseData));
            });
        }
    }
}
