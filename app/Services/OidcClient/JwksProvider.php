<?php

namespace App\Services\OidcClient;

use Facile\JoseVerifier\JWK\JwksProviderInterface;
use Jose\Component\KeyManagement\JWKFactory;

class JwksProvider implements JwksProviderInterface
{
    public function getJwks(): array
    {
        $jwk = JWKFactory::createFromKeyFile(
            base_path(config('oidc-connection.signing_key_path')),
            null,
            ['kid' => config('oidc-connection.signing_key_id'), 'use' => 'sig', 'alg' => 'RS256'],
        );

        return [
            'keys' => [
                $jwk->all(),
            ],
        ];
    }

    public function reload(): JwksProviderInterface
    {
        // No need to reload, we are using a static key
        return $this;
    }
}
