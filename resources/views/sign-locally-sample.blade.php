@extends('template')

@section('content')
    <h1>
        Sample for signing on your page
    </h1>

    <p>
        Make sure you are running over https, have ID card in the reader and software installed is using ID card signing.
    </p>

    @if(count($files)===0) {
        <p>
            No files prepared for signing
        </p>
    @endif

    @foreach($files as $key => $file)
        <a href="/download-unsigned-file?doc_id={{$doc_id}}&filename={{$file['fileName']}}">{{$file['fileName']}}</a><br>
        <object type="{{$file['mimeType']}}" data="data:{{$file['mimeType']}};base64,{{$file['fileContent']}}" width="1000" height="500">
            <p>This type of file preview cannot be shown</p>
        </object><br>
    @endforeach

    <div id="app">
        <embedded-signing doc_id="{{$doc_id}}"></embedded-signing>
    </div>

@endsection

@section('scripts')
    <script src="/js/hwcrypto.js"></script>
    <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
@endsection
