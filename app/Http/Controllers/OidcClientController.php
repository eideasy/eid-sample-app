<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OidcClient\JwksService;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OidcClientController
{
    public function __construct(protected ClientInterface $client, protected AuthorizationService $authorizationService)
    {
    }

    public function startAuthentication(AuthSessionInterface $authSession): mixed
    {
        $authSession->setState($state = Str::random(32));
        $authSession->setNonce($nonce = Str::random(32));
        $redirectAuthorizationUri = $this->authorizationService->getAuthorizationUri(
            $this->client,
            [
                'scope' => implode(' ', ['openid', 'profile']),
                'response_type' => 'code',
                'nonce' => $nonce,
                'state' => $state,
            ]
        );

        Log::info('Redirecting to ' . $redirectAuthorizationUri);

        return redirect($redirectAuthorizationUri);
    }

    public function returnCallback(
        Request $request,
        AuthSessionInterface $authSession
    ): View {
        $tokenSet = $this->authorizationService->callback(
            $this->client,
            $request->all(),
            null,
            $authSession,
        );

        $idToken = $tokenSet->getIdToken();

        if ($idToken) {
            $userData = $tokenSet->claims(); // IdToken claims
        } else {
            throw new \RuntimeException('Unauthorized');
        }

        return view(
            'welcome', [
                'authorizeUri' => '',
                'userData' => Arr::except($userData, [
                    "iss", "sub", "aud", "exp", "iat", "jti", "auth_time", "nonce",
                ])
            ]);
    }

    public function getJwks(JwksService $jwksService): mixed
    {
        $jwks = $jwksService->getPublicJwks();

        Log::info('getJwks', [
            'jwks' => $jwks,
        ]);

        return response()->json($jwks, 200, [
            'Content-Type' => 'application/json',
            // Cache-Control header to cache the response for 1 hour.
            'Cache-Control' => 'max-age=3600',
        ]);
    }
}
