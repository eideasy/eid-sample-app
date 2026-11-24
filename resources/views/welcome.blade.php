@extends('template')
@section('title', 'eID Easy playground')
@section('content')
    <div class="demo-heading hub-heading">
        <h1>eID Easy playground</h1>
        <p>Try eID Easy’s signing, identification and wallet integrations with test data.</p>
    </div>

    @if(!empty($error))
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
    @elseif(isset($userData))
        <section class="result-panel setup-panel mb-4" aria-labelledby="oauth-result">
            <h2 id="oauth-result">OAuth login result</h2>
            <pre tabindex="0"><code>{{ json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</code></pre>
        </section>
    @endif

    <div class="demo-catalogue">
        <a class="demo-card featured" href="{{ url('sign-custom-file') }}">
            <div class="demo-card-top"><i data-lucide="file-signature" aria-hidden="true"></i></div>
            <h2>Digital signatures</h2>
            <p>Try the signing page, embed the signing widget, send document digests for signing or create a sandbox e-Seal.</p>
            <span class="card-action">Open the signing playground <i data-lucide="arrow-up-right" aria-hidden="true"></i></span>
        </a>
        <a class="demo-card sage" href="{{ route('oauth.start') }}">
            <div class="demo-card-top"><i data-lucide="external-link" aria-hidden="true"></i></div>
            <h2>OAuth 2.0 login</h2>
            <p>Choose an identity provider on the eID Easy login page. After login, the demo backend exchanges the authorization code and retrieves the available user data.</p>
            <span class="card-action">Try OAuth login <i data-lucide="arrow-up-right" aria-hidden="true"></i></span>
        </a>
        <a class="demo-card" href="{{ config('eideasy.wallet_demos_url') }}" target="_blank" rel="noopener">
            <div class="demo-card-top"><i data-lucide="wallet-cards" aria-hidden="true"></i></div>
            <h2>Digital identity wallets</h2>
            <p>Try sharing wallet credentials in the rental, banking and cinema demos, or inspect the requests in the technical playground.</p>
            <span class="card-action">Explore the wallet demos <i data-lucide="arrow-up-right" aria-hidden="true"></i></span>
        </a>
    </div>
    <details class="additional-demos">
        <summary>More integration examples</summary>
        <ul>
            <li><a href="{{ url('login-widget') }}">Standalone embedded identification widget</a></li>
            <li><a href="{{ url('add-signature-signed-file') }}">Add a signature to an existing ASiC-E container</a></li>
        </ul>
    </details>
@endsection
