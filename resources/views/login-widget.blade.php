@extends('template')

@section('content')
    <div style="max-width: 450px; margin: 20px auto;">
        <h1 style="text-align: center">
            Login Widget
        </h1>
        <div style="margin-top: 20px; margin-bottom: 10px;">
            <label for="langPicker">Language:</label>
            <select name="langPicker" id="langPicker">
                <option value="en" selected>English</option>
                <option value="et">Estonian</option>
                <option value="lv">Latvian</option>
                <option value="lt">Lithuanian</option>
                <option value="ru">Russian</option>
            </select>
        </div>

        <div id="widgetHolder" class="widgetHolder"></div>
    </div>

    <script
            src="https://cdn.jsdelivr.net/npm/@eid-easy/eideasy-widget@0.10.0-alpha.5/dist/full/eideasy-widget.umd.js"
            integrity="sha256-qqncWqLxeTJhyGlIuMqqoDpNiL+mNAzSrPhOClMMpKw="
            crossorigin="anonymous"
    ></script>
    <script>
      const widgetHolder = document.getElementById('widgetHolder');
      const eidEasyWidget = document.createElement('eideasy-widget');
      const settings = {
        countryCode: 'EE', // ISO 3166  two letter country code
        language: 'en', // ISO 639-1 two letter language code,
        sandbox: true,
        clientId: '{{ env('EID_CLIENT_ID', '') }}',
        apiEndpoints: {
          identityStart: () => '{{url('/')}}/api/identity/start',
          identityFinish: () => '{{url('/')}}/api/identity/finish',
        },
        enabledMethods: {
          identification: 'all',
        },
        onSuccess: function(data) {
          console.log('success');
          alert(JSON.stringify(data));
        },
        onFail: function(error) {
          console.log(error);
        },
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