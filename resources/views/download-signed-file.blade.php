@extends('template')
@section('title', 'Download signed files')
@section('content')
    <div class="demo-heading"><h1>Download signed files</h1></div>
    @include('form-feedback')
    <section class="setup-panel result-panel" aria-label="Signing result">
        <a class="demo-button" href="{{ url()->query('/download-signed-file', ['file_id' => $fileId]) }}">Download signed file <i data-lucide="download" aria-hidden="true"></i></a>
        <div class="result-links"><a href="{{ url('sign-custom-file') }}">Sign another document</a><a href="{{ url('/') }}">All demos</a></div>
    </section>
@endsection
