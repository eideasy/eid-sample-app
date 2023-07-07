<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if(config('services.amplitude.enabled'))
        @include('analytics-instrumentation')
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>eID Easy sample apps</title>

    <link rel="stylesheet" href="{{asset('/css/vendor/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('/css/vendor/toastr.min.css')}}">
    <link rel="stylesheet" href="{{mix('/css/style.css')}}">
</head>
<body>

@include('topbar')

<div class="main">
    <div class="container">
        @yield('content')
    </div>
</div>

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
@yield('scripts')
</body>
</html>
