@if(Session::has('message'))<div class="alert {{ Session::get('alert-class', 'alert-info') }}" role="alert">{{ Session::get('message') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert" tabindex="-1" id="validation-errors"><strong>Check your setup before continuing.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p class="mb-0">Please choose your files again. Your other settings have been kept.</p></div>
@endif
