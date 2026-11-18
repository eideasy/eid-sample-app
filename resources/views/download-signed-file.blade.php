@extends('template')
@section('title', 'Download your document')
@section('content')
<div class="demo-heading"><span class="eyebrow">BACK FROM SIGNING</span><h1>Your next step. The signed file.</h1><p>Retrieve the result of your signing journey and save a copy.</p></div>
<section class="setup-panel result-panel"><span class="eyebrow">YOUR DOCUMENT</span><h2>Take the result with you.</h2><p>Download the signed document or container. You can open the result in a compatible application to inspect its signatures.</p>
<a class="demo-button" href="{{ url('/download-signed-file') . '?' . http_build_query(['file_id' => $fileId]) }}">Download signed file <i data-lucide="download" aria-hidden="true"></i></a>
<details><summary>Demo reference</summary><span class="result-reference">{{ $fileId }}</span></details><div class="result-links"><a href="{{ url('sign-custom-file') }}">Sign another document</a><a href="{{ url('/') }}">Explore all demos</a></div></section>
@endsection
