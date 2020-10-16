@extends('template')

@section('content')
    <h1>
        Upload the file to be signed
    </h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data">
        {{csrf_field()}}
        <div class="form-group">
            <label for="unsigned_file">File to be signed</label>
            <input name="unsigned_file" type="file" class="form-control-file" id="unsigned_file">
        </div>
        <div class="form-group">
            <label for="redirect_uri-url">Optional: Where to redirect after signing completed</label>
            <input name="redirect_uri" type="url" class="form-control" id="redirect_uri">
        </div>

        <h3>Signature creation process and integration complexity</h3>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="sign-externally" value="external" checked>
            <label class="form-check-label" for="sign-externally">
                Sign externally - much easier implementation and everything just works with 2 API-s. Send files to be signed, download signed file.
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="sign-locally" value="local">
            <label class="form-check-label" for="sign-locally">
                Sign locally - Better UX but you are responsible for showing all contents of the signed file and you need to implement each method separately.
            </label>
        </div>
        <br>

        <h3>Container type</h3>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="containerType" id="asice-selection" value="asice" checked>
            <label class="form-check-label" for="asice-selection">
                Create and .asice container. Very powerful signature type, allows to sign any type of file and multiple files at once.
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="containerType" id="pdf-selection" value="pdf">
            <label class="form-check-label" for="pdf-selection">
                Sign PDF. Signature can be verified with Adobe Reader and only one PDF is allowed.
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Sign now</button>
    </form>

@endsection
