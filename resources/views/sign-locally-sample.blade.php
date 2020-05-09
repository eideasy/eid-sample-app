@extends('template')

@section('content')
    <h1>
        Click Sign to initiate signing below XML that can be sent to Bank later
    </h1>

    <p>
        Make sure you are running over https, have ID card in the reader and software installed.
    </p>

    <p>
        Main backend logic is in SignLocallyController. Sample file to be signed is in storage/samples/payment.xml. Main
        front logic is in sign-locally-sample.blade.php with including javascript from sign-locally.js
    </p>

    <button class="btn btn-primary form-control" onclick="startSigning()">Start signing</button>

    <pre class="pt-5"><code lang="xml">{{$fileContent}}</code></pre>

@endsection

@section('scripts')
    <script src="/js/hwcrypto.js"></script>
    <script src="/js/sign-locally.js"></script>
@endsection
