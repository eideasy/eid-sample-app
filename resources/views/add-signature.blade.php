@extends('template')
@section('title', 'Add a signature')
@section('content')
<div class="demo-heading"><span class="eyebrow">ANOTHER SIGNER</span><h1>Build on a signature.</h1><p>Add another signature to an existing ASiC-E container. Choose the signed file, then continue to the hosted signing journey.</p></div>
@include('form-feedback')
<div class="simple-workspace">
    <form method="POST" enctype="multipart/form-data" class="setup-panel simple-form" id="add-signature-form">
        @csrf
        <span class="eyebrow">01 / YOUR SIGNED CONTAINER</span><h2>Keep the signatures going.</h2>
        <div class="file-drop"><i data-lucide="file-plus-2" aria-hidden="true"></i><label for="signed_file">Choose an ASiC-E file</label><input name="signed_file" type="file" id="signed_file" accept=".asice" required aria-describedby="asice-hint"><p id="asice-hint">An existing .asice container with XAdES signatures.</p></div>
        <p class="field-hint" id="asice-file-status" aria-live="polite">No file selected.</p>
        <details class="redirect-details" @if($errors->has('redirect_uri')) open @endif><summary>Redirect settings <span class="optional-label">Optional</span></summary><label for="redirect_uri">Return URL after signing</label><input name="redirect_uri" type="url" id="redirect_uri" value="{{ old('redirect_uri') }}" placeholder="https://your-app.example/complete"><p class="field-hint">Leave empty to return to the demo.</p></details>
        <button type="submit" class="demo-button">Continue to signing <i data-lucide="arrow-right" aria-hidden="true"></i></button>
    </form>
    <aside class="journey-notes"><span class="eyebrow">WHAT HAPPENS NEXT</span><h2>Same container.<br>One more signature.</h2><ol><li>Upload the existing signed container.</li><li>Choose an identification method and sign.</li><li>Download the container with the added signature.</li></ol><p>Starting from unsigned files instead?</p><a href="{{ url('sign-custom-file') }}">Open the signing playground</a></aside>
</div>
@endsection
@section('scripts')<script>document.getElementById('signed_file').addEventListener('change', function () { document.getElementById('asice-file-status').textContent = this.files.length ? this.files[0].name : 'No file selected.'; });</script>@endsection
