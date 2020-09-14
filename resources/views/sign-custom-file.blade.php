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

    <form method="POST" enctype="multipart/form-data" >
        {{csrf_field()}}
        <div class="form-group">
            <label for="unsigned_file">File to be signed</label>
            <input name="unsigned_file" type="file" class="form-control-file" id="unsigned_file">
        </div>
        <div class="form-group">
            <label for="redirect_uri-url">Optional: Where to redirect after signing completed</label>
            <input name="redirect_uri" type="url" class="form-control" id="redirect_uri">
        </div>

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

        <button type="submit" class="btn btn-primary">Sign now</button>
    </form>

@endsection
