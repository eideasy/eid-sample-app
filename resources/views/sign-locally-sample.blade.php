@extends('template')
@section('title', 'Embedded signing widget')

@section('content')
    <div class="demo-heading">
        <h1>Embedded signing widget</h1>
        <p>Review your files and choose a signing method. ID-card signing requires HTTPS, a card reader and ID-card software.</p>
    </div>
    <div class="row">
        <section class="col-md-7 mb-4" aria-labelledby="document-preview">
            <h2 id="document-preview">Documents</h2>
            @foreach($files as $file)
                <div class="card mb-3">
                    <div class="card-body">
                        <a href="{{ url()->query('/download-unsigned-file', ['doc_id' => $doc_id, 'filename' => $file['fileName']]) }}">
                            Download {{ $file['fileName'] }}
                        </a>
                        @if($file['pdfPreview'] !== null)
                            <object type="application/pdf" data="data:application/pdf;base64,{{ $file['pdfPreview'] }}"
                                    class="w-100 mt-2" height="550" aria-label="PDF preview">
                                <p>Download the PDF to preview it.</p>
                            </object>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>
        <section class="col-md-5" aria-labelledby="signing-method">
            <h2 id="signing-method">Signing method</h2>
            @if($available_methods === [])
                <p class="alert alert-warning">No signing methods are available for this document.</p>
            @else
                <div id="app">
                    <div v-if="!isSuccess">
                        <widget
                            doc-id="{{ $doc_id }}"
                            client-id="{{ $client_id }}"
                            base-url="{{ $base_url }}"
                            :available-methods="{{ Illuminate\Support\Js::from($available_methods) }}"
                            @success="handleSuccess"
                            @fail="handleFail"
                        ></widget>
                    </div>
                    <div v-else v-cloak>
                        <p class="alert alert-success" role="status">Signing complete.</p>
                        <a class="demo-button" href="{{ url()->query('/download-signed-file', ['file_id' => $file_id]) }}">
                            Download signed file
                        </a>
                    </div>
                </div>
                <noscript><p class="alert alert-info">Enable JavaScript to use the signing widget.</p></noscript>
            @endif
        </section>
    </div>
@endsection

@section('scripts')
    @if($available_methods !== [])
        <script src="{{ asset('/js/hwcrypto.js') }}" defer></script>
        <script src="{{ mix('/js/app.js') }}" defer></script>
    @endif
@endsection
