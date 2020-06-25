@extends('template')

@section('content')

    <div id="app">
        <embedded-login></embedded-login>
    </div>

@endsection

@section('scripts')
    <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
@endsection
