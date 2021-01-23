@extends('template')

@section('content')

    <h1>eID Easy services test</h1>
    <p>More info from <a href="https://eideasy.com">eideasy.com</a></p>

    <p>
        Source code for this app can be found from <a href="https://github.com/eideasy/eid-sample-app">https://github.com/eideasy/eid-sample-app</a>
    </p>
    <p>
        Full API documentation is available at <a href=" https://documenter.getpostman.com/view/3869493/Szf6WoG1">https://documenter.getpostman.com/view/3869493/Szf6WoG1</a>
    </p>
    <p>
        This app is built with Laravel and is using VueJS in some places. It is meant to be reference to look at when
        you get into trouble with your integration. If you run this app then do not forget to execute commands "composer
        install" and "npm run dev"
    </p>

    <ul class="list-group">
        <li class="list-group-item"><a href="{{$authorizeUri}}">Login with OAuth 2.0</a></li>
        <li class="list-group-item"><a href="{{url('embedded-identification')}}">Embedded identification</a></li>
        <li class="list-group-item"><a href="{{url('sign-custom-file')}}">Sign a file</a></li>
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

    <footer>This is a free service to test out eID Easy API-s. After the signature or user identification your information
        might be available for the service provider.
    </footer>
@endsection

@section('scripts')
    @if (isset($userData))
        <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
    @endif
@endsection
