<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Jumbojett\OpenIDConnectClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::share([
            'client_id' => config('eideasy.client_id'),
            'card_domain' => config('eideasy.card_domain'),
            'api_url' => config('eideasy.api_url'),
        ]);
        $this->app->extend(OpenIDConnectClient::class, function ($service, $app) {
            // Fixes https://github.com/jumbojett/OpenID-Connect-PHP/pull/282 until its merged.
            return $this->extendOpenIdConnectClient();
        });

    }

    private function extendOpenIdConnectClient(): OpenIDConnectClient
    {
        return new class extends OpenIDConnectClient {
            public function __construct()
            {
                parent::__construct(
                    config('eideasy.api_url'),
                    config('eideasy.client_id'),
                    config('eideasy.secret'),
                );
                $this->setResponseTypes(['code']);
                $this->setAllowImplicitFlow(false);
                $this->setRedirectURL(route('oidc.callback'));
                // On non-production environments, we don't want to verify the SSL certificate.
                //$oidc->setVerifyHost(false);
                //$oidc->setVerifyPeer(false);
                $this->addScope(['openid', 'profile']);
                $this->setTokenEndpointAuthMethodsSupported([
                    // We'll switch to client_secret_jwt once its implemented.
                    //'client_secret_jwt',
                    'client_secret_post'
                ]);
            }

            // Fixes https://github.com/jumbojett/OpenID-Connect-PHP/pull/282 until its merged.
            protected function verifyJWTclaims($claims, $accessToken = null)
            {
                if (isset($claims->at_hash) && isset($accessToken)) {
                    if (isset($this->getIdTokenHeader()->alg) && $this->getIdTokenHeader()->alg !== 'none') {
                        $bit = substr($this->getIdTokenHeader()->alg, 2, 3);
                    } else {
                        // TODO: Error case. throw exception???
                        $bit = '256';
                    }
                    $len = ((int)$bit) / 16;
                    $expected_at_hash = $this->urlEncode(substr(hash('sha' . $bit, $accessToken, true), 0, $len));
                }
                return (($this->validateIssuer($claims->iss))
                    && (($claims->aud === $this->getClientID()) || in_array($this->getClientID(), $claims->aud, true))
                    && (!isset($claims->nonce) || $claims->nonce === $this->getNonce())
                    && (!isset($claims->exp) || (in_array(gettype($claims->exp), ['integer', 'double']) && ($claims->exp >= time() - $this->getLeeway())))
                    && (!isset($claims->nbf) || (in_array(gettype($claims->nbf), ['integer', 'double']) && ($claims->nbf <= time() + $this->getLeeway())))
                    && (!isset($claims->at_hash) || !isset($accessToken) || $claims->at_hash === $expected_at_hash)
                );
            }
        };
    }
}
