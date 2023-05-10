@extends('template')

@section('content')



    @if (isset($userData))
        <div id="app">
            <user-data user-data='{!!json_encode($userData)!!}'></user-data>
        </div>
    @else
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
    @endif

    <p>
        This is a demo app you can use to test out <a href="https://eideasy.com" target="_blank">eID Easy</a> services.
    </p>

    <p>
        Source code for this app is available at <a href="https://github.com/eideasy/eid-sample-app" target="_blank">https://github.com/eideasy/eid-sample-app</a>
    </p>
    <p>
        Documentation for eID Easy services is available at <a href="https://docs.eideasy.com" target="_blank">https://docs.eideasy.com</a>
    </p>

    <div class="pt-4">
        <ul class="list-group">
            <li class="list-group-item"><a href="{{$authorizeUri}}">Login with OAuth 2.0</a></li>
            <li class="list-group-item"><a href="{{url('login-widget')}}">Login widget</a></li>
            <li class="list-group-item"><a href="{{url('embedded-identification')}}">Embedded identification</a></li>
            <li class="list-group-item"><a href="{{url('sign-custom-file')}}">Sign a file or create an e-Seal</a></li>
            <li class="list-group-item"><a href="{{url('add-signature-signed-file')}}">Add signature to an existing .asice
                    container</a></li>
        </ul>
    </div>
@endsection

@section('scripts')
    @if (isset($userData))
        <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
    @endif
@endsection
