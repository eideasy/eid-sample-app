<?php

declare(strict_types=1);

namespace App\Services\OidcClient;

use Facile\OpenIDClient\Session\AuthSessionInterface;
use Illuminate\Contracts\Session\Session;
use function array_filter;

class AuthenticationSession implements AuthSessionInterface
{
    protected const OIDC_STATE_KEY = 'oidc_state';
    protected const OIDC_NONCE_KEY = 'oidc_nonce';

    public function __construct(protected Session $session)
    {
    }

    private ?string $codeVerifier = null;

    /** @var array<string, mixed> */
    private array $customs = [];

    public function getState(): ?string
    {
        return $this->session->get(self::OIDC_STATE_KEY);
    }

    public function getNonce(): ?string
    {
        return $this->session->get(self::OIDC_NONCE_KEY);
    }

    public function getCodeVerifier(): ?string
    {
        return $this->codeVerifier;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustoms(): array
    {
        return $this->customs;
    }

    public function setState(?string $state): void
    {
        $this->session->put(self::OIDC_STATE_KEY, $state);
    }

    public function setNonce(?string $nonce): void
    {
        $this->session->put(self::OIDC_NONCE_KEY, $nonce);
    }

    public function setCodeVerifier(?string $codeVerifier): void
    {
        $this->codeVerifier = $codeVerifier;
    }

    /**
     * @param array<string, mixed> $customs
     */
    public function setCustoms(array $customs): void
    {
        $this->customs = $customs;
    }

    public static function fromArray(array $array): AuthSessionInterface
    {
        $session = new AuthenticationSession(app()->make(Session::class));
        $session->setState($array['state'] ?? null);
        $session->setNonce($array['nonce'] ?? null);
        $session->setCodeVerifier($array['code_verifier'] ?? null);
        $session->setCustoms($array['customs'] ?? []);

        return $session;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'state' => $this->getState(),
            'nonce' => $this->getNonce(),
            'code_verifier' => $this->getCodeVerifier(),
            'customs' => $this->getCustoms(),
        ]);
    }
}
