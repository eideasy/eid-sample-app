<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;
use EidEasy\EidEasyParams;
use Illuminate\Http\JsonResponse;
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

    public function startLogin(Request $request): JsonResponse
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
        } elseif (str_contains($method, "google-wallet-login")) {
            return $this->startGoogleWalletLogin($request);
        } elseif (str_contains($method, "evrotrust-login")) {
            return $this->startEvrotrustLogin($request);
        } elseif (str_contains($method, "eudi-wallet-login")) {
            return $this->startEudiWalletLogin($request);
        }

        abort(404, "Invalid method $method");
    }

    public function finishLogin(Request $request): JsonResponse
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
        } elseif (str_contains($method, "google-wallet-login")) {
            return $this->finishGoogleWalletLogin($request);
        } elseif (str_contains($method, "evrotrust-login")) {
            return $this->finishEvrotrustLogin($request);
        } elseif (str_contains($method, "eudi-wallet-login")) {
            return $this->finishEudiWalletLogin($request);
        }

        abort(404, "Invalid method $method");
    }

    public function finishIdCardLogin(Request $request): JsonResponse
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

    public function finishMobileidLogin(Request $request): JsonResponse
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

    public function startMobileidLogin(Request $request): JsonResponse
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

    public function finishSmartIdLogin(Request $request): JsonResponse
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

    public function startSmartIdLogin(Request $request): JsonResponse
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

    public function finishFrejaEidLogin(Request $request): JsonResponse
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

    public function startFrejaIdLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idcode' => 'required_without:email|min:10|max:20',
            'email' => 'required_without:idcode|email',
            'country' => 'required|in:NO,SE,DK,FI'
        ]);

        $responseData = $this->eidEasyApi->startIdentification('freja-eid-login', $data);

        return response()->json($responseData);
    }

    public function startEvrotrustLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => 'required',
            'phone' => 'required',
            'country' => 'required'
        ]);

        $responseData = $this->eidEasyApi->startIdentification($request->get('method'), $data);

        return response()->json($responseData);
    }

    public function startEudiWalletLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => 'required',
            'country' => 'required',
            'document_type' => 'required'
        ]);

        $responseData = $this->eidEasyApi->startIdentification($request->get('method'), $data);

        return response()->json($responseData);
    }

    public function finishZealIdLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification(EidEasyParams::ZEALID_LOGIN, $data);
        unset($responseData['email']);
        $this->notifyLogin($responseData);

        return response()->json($responseData);
    }

    public function startWebEidLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country' => 'required|in:EE',
            'method' => 'required',
        ]);

        $responseData = $this->eidEasyApi->startIdentification($data['method'], $data);

        return response()->json($responseData);
    }

    public function finishWebEidLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required',
            'auth_token' => 'required',
            'method' => 'required',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($data['method'], $data);

        return response()->json($responseData);
    }

    public function finishEvrotrustLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => 'required',
            'token' => 'required',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($request->get('method'), $data);

        return response()->json($responseData);
    }

    public function finishEudiWalletLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => 'required',
            'token' => 'required',
        ]);

        $responseData = $this->eidEasyApi->completeIdentification($request->get('method'), $data);

        return response()->json($responseData);
    }

    protected function notifyLogin($responseData): void
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
