<?php

declare(strict_types=1);

namespace App\Services\OidcClient;

use Jose\Component\KeyManagement\JWKFactory;

class JwksService
{
    public function getPublicJwks(): array
    {
        $jwk = JWKFactory::createFromKeyFile(
            base_path(config('oidc-connection.signing_key_public_path')),
            null,
            ['kid' => config('oidc-connection.signing_key_id'), 'use' => 'sig', 'alg' => 'RS256'],
        );

        return [
            'keys' => [
                $jwk->all(),
            ],
        ];
    }
}
