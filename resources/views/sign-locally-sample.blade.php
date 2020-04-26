@extends('template')

@section('content')
    <h1 class="m-b-md">
        Click Sign to initiate signing below XML that can be sent to Bank later
    </h1>

    <p>
        Make sure you are running over https, have ID card in the reader and software installed.
    </p>

    <div class="links m-b-md">
        <button onclick="startSigning()">Start signing</button>
    </div>

    <pre lang="xml" class="left">
{{$fileContent}}
    </pre>
@endsection

@section('scripts')
    <script src="/js/hwcrypto.js"></script>
    <script src="/js/sign-locally.js"></script>
@endsection
