@extends('template')

@section('content')
    <h1>
        File with local ID {{$fileId}}has been signed.
    </h1>

    <p>Download it from here <a href="{{url("/download-signed-file?file_id=$fileId")}}">{{url("/download-signed-file?file_id=$fileId")}}</a></p>

@endsection
