@extends('template')

@section('content')
    <h1>
        Sign via CSC API
    </h1>

    @if(Session::has('message'))
        <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p>
    @endif
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

        <button type="submit" class="btn btn-primary">Sign now</button>
    </form>

@endsection
