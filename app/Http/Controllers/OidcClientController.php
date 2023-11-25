<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Service\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OidcClientController
{
    public function __construct(protected ClientInterface $client, protected AuthorizationService $authorizationService)
    {
    }

    public function startAuthentication(Request $request): mixed
    {
        $redirectAuthorizationUri = $this->authorizationService->getAuthorizationUri(
            $this->client,
            [
                'scope' => implode(' ', ['openid', 'profile']),
                'response_type' => 'code',
                'nonce' => Str::random(32),
                'state' => $state = Str::random(32),
            ]
        );

        // store state in session. We will need it later to validate state
        $request->session()->put('oidc_state', $state);

        Log::info('Redirecting to ' . $redirectAuthorizationUri);

        return redirect($redirectAuthorizationUri);
    }

    public function returnCallback(Request $request): \Illuminate\View\View
    {
        // Todo.update: validate state and nonce using the library

        // Let's validate state in session
        $state = $request->session()->get('oidc_state');

        if ($state !== $request->get('state')) {
            throw new \RuntimeException('Invalid state');
        }

        $tokenSet = $this->authorizationService->callback($this->client, $request->all());

        // Let's clean up session after we have validated state
        $request->session()->forget('oidc_state');

        $idToken = $tokenSet->getIdToken();

        if ($idToken) {
            $userData = $tokenSet->claims(); // IdToken claims
        } else {
            throw new \RuntimeException('Unauthorized');
        }

        return view('welcome', ['authorizeUri' => '', "userData" => $userData]);
    }
}
