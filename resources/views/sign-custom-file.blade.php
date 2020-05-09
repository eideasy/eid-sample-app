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
        <button type="submit" class="btn btn-primary">Sign now</button>
    </form>

@endsection
