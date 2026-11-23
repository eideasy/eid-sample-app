<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'eID Easy sample apps') — eID Easy</title>

    <link rel="preload" href="{{ asset('/font/67ef84cc51edf88c27c4cc59_FKDisplay.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{asset('/css/vendor/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('/css/vendor/toastr.min.css')}}">
    <link rel="stylesheet" href="{{mix('/css/style.css')}}">
</head>
<body @if(!request()->is('login-widget')) class="modern-demo" @endif>

@include('topbar')

<main class="main">
    <div class="container">
        @yield('content')
    </div>
</main>

@include('footer')

<script src="{{asset('/js/vendor/jquery-3.5.1.min.js')}}"></script>
<script src="{{asset('/js/vendor/popper.min.js')}}"></script>
<script src="{{asset('/js/vendor/bootstrap.min.js')}}"></script>
<script src="{{asset('/js/vendor/toastr.min.js')}}"></script>

<script>
  toastr.options = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": false,
    "progressBar": false,
    "positionClass": "toast-top-center",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
  }
</script>
@if(!request()->is('login-widget'))
<script src="{{ asset('/js/vendor/lucide.min.js') }}"></script>
<script>lucide.createIcons();</script>
@endif
@if(config('services.amplitude.enabled') && config('services.amplitude.api_key') && !request()->hasAny(['code', 'error']))
    @include('analytics-instrumentation')
@endif
@yield('scripts')
</body>
</html>
