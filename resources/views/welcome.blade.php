@extends('template')

@section('content')
    <h1>
        eID sample apps
    </h1>
    <p>
        Source code for this app can be found from <a href="https://github.com/eideasy/eid-sample-app">https://github.com/eideasy/eid-sample-app</a>
    </p>

    <ul class="list-group">
        <li class="list-group-item"><a href="{{$authorizeUri}}">Login with OAuth 2.0</a></li>
        <li class="list-group-item"><a href="/embedded-identification">Embedded identification</a></li>
        <li class="list-group-item"><a href="/sign-custom-file">Upload and sign PDF file</a></li>
        <li class="list-group-item"><a href="/add-signature-signed-file">Add signature to existing .asice container</a></li>
        <li class="list-group-item"><a href="/sign-locally-sample">Advanced: Sign locally sample</a></li>
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
