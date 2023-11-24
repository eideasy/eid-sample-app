<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Token\IdTokenVerifierBuilder;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jumbojett\OpenIDConnectClient;

class OidcClientController
{
    public function __construct(private OpenIDConnectClient $oidc)
    {
    }

    public function startAuthentication(ClientInterface $client): mixed
    {
        // Redirection happens in the library.
//        $this->oidc->authenticate();
        $authorizationService = (new AuthorizationServiceBuilder())->build();
        $redirectAuthorizationUri = $authorizationService->getAuthorizationUri(
            $client,
            [
                'scope' => implode(' ', ['openid', 'profile']),
                'response_type' => 'code',
                'nonce' => '1234567890',
                'state' => '1234567890',
            ]
        );
        Log::info('Redirecting to ' . $redirectAuthorizationUri);

        return redirect($redirectAuthorizationUri);
    }

    public function returnCallback(Request $request, ClientInterface $client): \Illuminate\View\View
    {
        $tokenVerifier = new IdTokenVerifierBuilder();
        // Set leeway to 1 second because we might have a float timestamp instead of int.
        $tokenVerifier->setClockTolerance(1);

        $builder = new AuthorizationServiceBuilder();
        $authorizationService = $builder
            ->setIdTokenVerifierBuilder($tokenVerifier)
            ->build();

        $tokenSet = $authorizationService
            ->callback($client, $request->all());

        $idToken = $tokenSet->getIdToken();

        if ($idToken) {
            $userData = $tokenSet->claims(); // IdToken claims
        } else {
            throw new \RuntimeException('Unauthorized');
        }

        return view('welcome', ['authorizeUri' => '', "userData" => $userData]);
    }
}
