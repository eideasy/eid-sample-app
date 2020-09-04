@extends('template')

@section('content')
    <h1>
        eID sample apps
    </h1>
    <p>
        Source code for this app can be found from <a href="https://github.com/eideasy/eid-sample-app">https://github.com/eideasy/eid-sample-app</a>
    </p>
    <p>
        Full API documentation is available at <a href=" https://documenter.getpostman.com/view/3869493/Szf6WoG1">https://documenter.getpostman.com/view/3869493/Szf6WoG1</a>
    </p>
    <p>
        This app is built with Laravel and is using VueJS in some places. It is meant to be reference to look at when you get into trouble with your integration.
    </p>

    <ul class="list-group">
        <li class="list-group-item"><a href="{{$authorizeUri}}">Login with OAuth 2.0</a></li>
        <li class="list-group-item"><a href="{{url('embedded-identification')}}">Embedded identification</a></li>
        <li class="list-group-item"><a href="{{url('sign-custom-file')}}">Upload and sign PDF file</a></li>
        <li class="list-group-item"><a href="{{url('add-signature-signed-file')}}">Add signature to existing .asice container</a></li>
        <li class="list-group-item"><a href="{{url('sign-locally-sample')}}">Advanced: Sign locally sample</a></li>
    </ul>
    <div>
        @if (isset($userData))
            <div id="app">
                <user-data user-data='{!!$userData!!}'></user-data>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @if (isset($userData))
        <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
    @endif
@endsection
