@extends('template')

@section('content')

    <h1 class="hero">
        <a
            href="https://eideasy.com"
            target="_blank"
            class="hero__link"
        >
            eID Easy
        </a>
        <br>
        Services Demo
    </h1>
    
    <p>
        This is a demo app you can use to test out eID Easy services.
    </p>
    <p>More info from <a href="https://eideasy.com">eideasy.com</a></p>

    <p>
        Source code for this app is available at <a href="https://github.com/eideasy/eid-sample-app" target="_blank">https://github.com/eideasy/eid-sample-app</a>
    </p>
    <p>
        eID Easy services documentation is available at <a href="https://docs.eideasy.com" target="_blank">https://docs.eideasy.com</a>
    </p>
    <p>
        This app is built with Laravel and is using VueJS in some places. It is meant to be reference to look at when
        you get into trouble with your integration.
        Do not forget to  "composer
        install" and "npm run dev"
    </p>

    <ul class="list-group">
        <li class="list-group-item"><a href="{{$authorizeUri}}">Login with OAuth 2.0</a></li>
        <li class="list-group-item"><a href="{{url('login-widget')}}">Login widget</a></li>
        <li class="list-group-item"><a href="{{url('embedded-identification')}}">Embedded identification</a></li>
        <li class="list-group-item"><a href="{{url('sign-custom-file')}}">Sign a file or create e-Seal</a></li>
        <li class="list-group-item"><a href="{{url('add-signature-signed-file')}}">Add signature to existing .asice
                container</a></li>
    </ul>
    <div>
        @if (isset($userData))
            <div id="app">
                <user-data user-data='{!!json_encode($userData)!!}'></user-data>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @if (isset($userData))
        <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
    @endif
@endsection
