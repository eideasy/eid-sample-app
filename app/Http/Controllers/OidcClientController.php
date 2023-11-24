<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use Jumbojett\OpenIDConnectClient;

class OidcClientController
{
    public function __construct(private OpenIDConnectClient $oidc)
    {
    }

    public function startAuthentication(): void
    {
        // Redirection happens in the library.
        $this->oidc->authenticate();
    }

    public function returnCallback(): \Illuminate\View\View
    {
        // authenticate() method is necessary because the ridirection is handled in it.
        $this->oidc->authenticate();
        $userData = collect($this->oidc->getVerifiedClaims());

        return view('welcome', ['authorizeUri' => '', "userData" => $userData]);
    }
}
