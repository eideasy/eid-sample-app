@extends('template')

@section('content')

    <div id="app">
        <embedded-login client-id="{{ $client_id }}" card-domain="{{ $card_domain }}"></embedded-login>
    </div>

@endsection

@section('scripts')
    <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
@endsection
