<?php

namespace App\Providers;

use App\Services\OidcClient\JwksProvider;
use Facile\JoseVerifier\JWK\CachedJwksProvider;
use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\JoseVerifier\JWK\MemoryJwksProvider;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Facile\OpenIDClient\Token\IdTokenVerifierBuilder;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Psr\SimpleCache\CacheInterface;

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

        $this->app->bind(AuthSessionInterface::class, function($app) {
            return new \App\Services\OidcClient\AuthenticationSession($app->make(Session::class));
        });

        $this->prepareOidcClient();

    }

    private function prepareOidcClient(): void
    {
        $this->app->bind(ClientInterface::class, function($app) {
            // Use a cache to avoid to fetch issuer configuration and keys on every request.
            /** @var CacheInterface|Repository $cache */
            $cache = $app->get(Repository::class); // get your simple-cache implementation
            $metadataProviderBuilder = (new MetadataProviderBuilder())
                ->setCache($cache)
                ->setCacheTtl(60 * 60 * 24); // Cache metadata for 1 day

            $jwksProviderBuilder = (new JwksProviderBuilder())
                ->setCache($cache)
                ->setCacheTtl(60 * 60); // Cache JWKS for 1 hour

            $issuer = (new IssuerBuilder())
                ->setMetadataProviderBuilder($metadataProviderBuilder)
                ->setJwksProviderBuilder($jwksProviderBuilder)
                ->build(config('eideasy.api_url') . '/.well-known/openid-configuration');

            $clientMetadata = ClientMetadata::fromArray([
                'client_id' => config('eideasy.client_id'),
                'client_secret' => config('eideasy.secret'),
                // the auth method for the token endpoint
                'token_endpoint_auth_method' => config('oidc-connection.token_endpoint_auth_method'),
                'redirect_uris' => [
                    URL::route('oidc.callback'),
                ],
            ]);

            $clientJwksProvider = new CachedJwksProvider(
                new JwksProvider(),
                $cache,
                'demo-client-jwks-1',
                60 * 60 // Cache JWKS for 1 hour.
            );

            $client = (new ClientBuilder())
                ->setIssuer($issuer)
                ->setClientMetadata($clientMetadata)
                ->setJwksProvider($clientJwksProvider)
                ->build();

            return $client;
        });

        $this->app->bind(AuthorizationService::class, function($app) {
            $tokenVerifier = new IdTokenVerifierBuilder();
            // Set leeway to 1 second because we might have a float timestamp instead of int.
            $tokenVerifier->setClockTolerance(1);

            $builder = new AuthorizationServiceBuilder();
            $authorizationService = $builder
                ->setIdTokenVerifierBuilder($tokenVerifier)
                ->build();
            return $authorizationService;
        });
    }
}
