<?php
$widgetSandbox = env('EID_WIDGET_SANDBOX_MODE', 'false') ? 'true' : 'false';
?>

@extends('template')

@section('content')
    <div style="max-width: 520px; margin: 20px auto;">
        <h1 style="text-align: center; margin-bottom: 50px">
            Login Widget
        </h1>
        <div style="margin-top: 20px; margin-bottom: 10px;">
            <label for="langPicker">Language:</label>
            <select name="langPicker" id="langPicker">
                <option value="en" selected>English</option>
                <option value="et">Estonian</option>
                <option value="de">German</option>
                <option value="lv">Latvian</option>
                <option value="lt">Lithuanian</option>
                <option value="ru">Russian</option>
            </select>
        </div>

        <div id="widgetHolder" class="widgetHolder"></div>
    </div>

    <script
            src="https://cdn.jsdelivr.net/npm/@eid-easy/eideasy-widget@2.146.1/dist/full/eideasy-widget.umd.min.js?version=2.146.1"
            integrity="sha256-hxUTghFqUvEHptHdeGFaYFXEwdN9D+hXznOtiZS/9Fs="
            crossorigin="anonymous">
    </script>

    <script>
      const widgetHolder = document.getElementById('widgetHolder');
      const eidEasyWidget = document.createElement('eideasy-widget');
      const settings = {
        countryCode: 'EE', // ISO 3166  two letter country code
        language: 'en', // ISO 639-1 two letter language code,
        sandbox: {{ $widgetSandbox }},
        clientId: '{{ config('eideasy.client_id') }}',
        redirectUri: '{{ config('eideasy.redirect_uri') }}', // this gets used for redirects e.g. when using eParaksts mobile
        apiEndpoints: {
          identityStart: () => '{{url('/')}}/api/identity/start',
          identityFinish: () => '{{url('/')}}/api/identity/finish',
          base: () => '{{ config('eideasy.api_url') }}',
        },
        enabledMethods: {
          identification: {{ Illuminate\Support\Js::from($enabledIdentificationMethods) }},
        },
        onSuccess: function(data) {
          console.log('success');
          alert(JSON.stringify(data));
        },
        onFail: function(error) {
          console.log(error);
        },
        oauthParamState: 'custom-state-value-eid-sample-app',
      }

      Object.keys(settings).forEach(key => {
        eidEasyWidget[key] = settings[key];
      });

      widgetHolder.appendChild(eidEasyWidget);

      document
        .getElementById('langPicker')
        .addEventListener('change', (e) => {
          eidEasyWidget.language = e.target.value;
        });
    </script>

@endsection
