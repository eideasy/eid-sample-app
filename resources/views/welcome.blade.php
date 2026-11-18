@extends('template')
@section('title', 'Explore the demos')
@section('content')
    @if(!empty($error))
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
        <a class="demo-button" href="{{ url('/') }}">Back to all demos</a>
    @else
        @if(isset($userData))
            <div class="demo-heading"><span class="eyebrow">IDENTIFICATION COMPLETE</span><h1>Your verified connection.</h1></div>
            <div id="app"><user-data :user-data='@json($userData)'></user-data></div>
        @else
            <div class="demo-heading hub-heading">
                <span class="eyebrow">THE EID EASY PLAYGROUND</span>
                <h1>Try it. Make it yours.</h1>
                <p>From a verified identity to a signed document. Explore the services, try a real flow and find the right integration for your application.</p>
            </div>
        @endif
        <div class="catalogue-heading"><h2>What would you like to explore?</h2><span>Three ways to get started.</span></div>
        <div class="demo-catalogue">
            <a class="demo-card featured" href="{{ url('sign-custom-file') }}">
                <div class="demo-card-top"><span class="eyebrow">01 / DIGITAL SIGNATURES</span><i data-lucide="file-signature" aria-hidden="true"></i></div>
                <h2>A document.<br>A trusted signature.</h2>
                <p>Sign a PDF, create an ASiC-E container or try an automatic e-Seal. Configure the integration and place the visual signature on your document.</p>
                <span class="card-caption">Hosted signing · API flow · Digest signing · e-Seals</span>
                <span class="card-action">Open the signing playground <i data-lucide="arrow-up-right" aria-hidden="true"></i></span>
            </a>
            <a class="demo-card sage" href="{{ $authorizeUri }}">
                <div class="demo-card-top"><span class="eyebrow">02 / HOSTED IDENTIFICATION</span><i data-lucide="external-link" aria-hidden="true"></i></div>
                <h2>One redirect.<br>A verified identity.</h2>
                <p>Let eID Easy guide the login journey. Your customer identifies themselves and returns to your application with an OAuth authorization code.</p>
                <span class="card-caption">OAuth 2.0 · Hosted experience · Backend exchange</span>
                <span class="card-action">Try OAuth login <i data-lucide="arrow-up-right" aria-hidden="true"></i></span>
            </a>
            <a class="demo-card" href="{{ config('eideasy.wallet_demos_url') }}" target="_blank" rel="noopener">
                <div class="demo-card-top"><span class="eyebrow">03 / DIGITAL IDENTITY WALLETS</span><i data-lucide="wallet-cards" aria-hidden="true"></i></div>
                <h2>Your identity.<br>In your wallet.</h2>
                <p>Explore digital identity in action. Try the wallet playground or see how identity sharing works in rental, banking and cinema demos.</p>
                <span class="card-caption">EUDI wallets · Real-world journeys · Technical playground</span>
                <span class="card-action">Explore the wallet demos <i data-lucide="arrow-up-right" aria-hidden="true"></i></span>
            </a>
        </div>
        <p class="demo-footnote">Built for exploring the API. Use test documents and avoid uploading sensitive information.</p>
    @endif
@endsection
@section('scripts')
    @if(isset($userData))<script src="{{ mix('/js/app.js') }}"></script>@endif
@endsection
