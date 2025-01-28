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
    protected EidEasyApi $eidEasyApi;

    public function __construct(EidEasyApi $api)
    {
        $api->setClientId(config('eideasy.client_id'));
        $api->setSecret(config('eideasy.secret'));
        $api->setApiUrl(config('eideasy.api_url'));

        $this->eidEasyApi = $api;
    }

    public function startLogin(Request $request)
    {
        $method = $request->input('method');
        info("Start login $method");
        if ($method === "smartid") {
            return $this->startSmartIdLogin($request);
        } elseif ($method === "freja-eid-login") {
            return $this->startFrejaIdLogin($request);
        } elseif (in_array($method, [EidEasyParams::EE_MOBILEID_LOGIN, EidEasyParams::LT_MOBILEID_LOGIN])) {
            return $this->startMobileidLogin($request);
        } elseif (str_contains($method, "web-eid-login")) {
            return $this->startWebEidLogin($request);
        }

        abort(404, "Invalid method $method");
    }

    public function finishLogin(Request $request)
    {
        $method = $request->input('method');
        info("Finishing login $method");
        if ($method === "smartid") {
            return $this->finishSmartIdLogin($request);
        } elseif ($method === EidEasyParams::ZEALID_LOGIN) {
            return $this->finishZealIdLogin($request);
        } elseif ($method === "freja-eid-login") {
            return $this->finishFrejaEidLogin($request);
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
        } elseif (str_contains($method, "web-eid-login")) {
            return $this->finishWebEidLogin($request);
        }

        abort(404, "Invalid method $method");
    }

    public function finishIdCardLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'country' => 'required',
            'method' => 'required',
            'lang' => 'size:2',
            'timeout' => 'int',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($data['method'], $data);

        $this->notifyLogin($responseData);
        unset($responseData['email']);

        return response()->json($responseData);
    }

    public function finishMobileidLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'method' => 'required',
            'lang' => 'size:2',
            'timeout' => 'int',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($data['method'], $data);
        $this->notifyLogin($responseData);
        unset($responseData['email']);

        return response()->json($responseData);
    }

    public function startMobileidLogin(Request $request)
    {
        $data = $request->validate([
            'idcode' => 'required|size:11',
            'phone' => 'required|min:6|max:15|startsWith:+372,+370',
            'method' => 'required',
            'lang' => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->startIdentification($data['method'], $data);
        Session::put('method-' . $responseData['token'], $data['method']);

        return response()->json([
            'challenge' => $responseData['challenge'],
            'token' => $responseData['token'],
        ]);
    }

    public function finishSmartIdLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'lang' => 'size:2',
            'timeout' => 'int',
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
            'idcode' => 'required',
            'method' => 'required',
            'lang' => 'size:2',
        ]);

        $responseData = $this->eidEasyApi->startIdentification('smartid', $data);

        return response()->json($responseData);
    }

    public function finishFrejaEidLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'lang' => 'size:2',
            'timeout' => 'int',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification('freja-eid-login', $data);
        unset($responseData['email']);
        $this->notifyLogin($responseData);

        return response()->json($responseData);
    }

    public function startFrejaIdLogin(Request $request)
    {
        $data = $request->validate([
            'idcode' => 'required|min:10|max:20',
            'country' => 'required|in:NO,SE,DK,FI'
        ]);

        $responseData = $this->eidEasyApi->startIdentification('freja-eid-login', $data);

        return response()->json($responseData);
    }

    public function finishZealIdLogin(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification(EidEasyParams::ZEALID_LOGIN, $data);
        unset($responseData['email']);
        $this->notifyLogin($responseData);

        return response()->json($responseData);
    }

    public function startWebEidLogin(Request $request)
    {
        $data = $request->validate([
            'country' => 'required|in:EE',
            'method' => 'required',
        ]);

        $responseData = $this->eidEasyApi->startIdentification($data['method'], $data);

        return response()->json($responseData);
    }

    public function finishWebEidLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'auth_token' => 'required',
            'method' => 'required',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($data['method'], $data);

        return response()->json($responseData);
    }

    protected function notifyLogin($responseData)
    {
        info("Notifying of new login");
        if (count($responseData) === 0) {
            info("Nothing to notify");
            return;
        }
        if (config('eideasy.notify_email') && is_array($responseData)) {
            Mail::send([], [], function ($message) use ($responseData) {
                $responseData = Arr::only($responseData, ['idcode', 'firstname', 'lastname', 'country', 'current_login_method']);
                $message->to(config('eideasy.notify_email'))
                    ->subject("New login from eID Easy demo app")
                    ->html("New user testing the service: " . json_encode($responseData));
            });
        }
    }
}
