@extends('template')

@section('content')
    <h1>
        eID sample apps
    </h1>

    <ul class="list-group">
        <li class="list-group-item"><a href="/sign-custom-file">Upload and sign PDF file</a></li>
        <li class="list-group-item"><a href="/add-signature-signed-file">Add signature to existing .asice container</a></li>
        <li class="list-group-item"><a href="/sign-locally-sample">Advanced: Sign locally sample</a></li>
    </ul>
@endsection
