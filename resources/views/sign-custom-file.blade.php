@extends('template')
@section('title', 'Signing playground')
@section('content')
    @php
        $flows = [
            'external' => ['Hosted signing', 'eID Easy handles the signing journey. Upload your files, redirect the signer and download the result.'],
            'local' => ['API flow', 'Use the signing widget on your website and keep control of the customer experience.'],
            'digest' => ['Digest signing', 'Send document hashes for signing. ASiC-E is assembled locally; PDF signing uses the configured PAdES helper.'],
            'eseal' => ['Automatic e-Seal', 'Seal documents with a configured signing certificate. This demo uses sandbox credentials.'],
            'multisign' => ['Multiple signers', 'Continue to a hosted management page to enter the names and email addresses of your signers.'],
        ];
        $formats = [
            'pdf' => ['PDF', 'One PDF, signed in PAdES format. Open and verify the result in a PDF reader.'],
            'asice' => ['ASiC-E', 'Bundle one or more files of any type into a single signed .asice container.'],
            'asice-for-each-file' => ['ASiC-E per file', 'Create a separate signed .asice container for each uploaded file.'],
            'enveloped-xades' => ['XML', 'Sign an XML file with an enveloped XAdES signature.'],
        ];
    @endphp
    <div class="demo-heading signing-heading"><span class="eyebrow">SIGNING PLAYGROUND</span><h1>Make it your signature.</h1><p>Choose your file, explore the signing options and put the signature where you want it.</p></div>
    @include('form-feedback')
    <noscript><p class="alert alert-info">You can sign files with this form. Enable JavaScript for PDF preview, visual placement and live file checks.</p></noscript>
    <form method="POST" enctype="multipart/form-data" class="signing-form" id="signing-playground">
        @csrf
        <input type="hidden" name="placement_document_fingerprint" id="placement-document-fingerprint" value="{{ old('placement_document_fingerprint') }}">
        <div class="signing-workspace">
            <section class="setup-panel" aria-label="Signing configuration">
                <div class="setup-section">
                    <span class="eyebrow">01 / YOUR DOCUMENTS</span><h2>Start with a file.</h2>
                    <div class="file-drop" id="file-drop">
                        <i data-lucide="upload" aria-hidden="true"></i>
                        <label for="unsigned_file">Choose files <span>or drop them here</span></label>
                        <input name="unsigned_file[]" multiple type="file" id="unsigned_file" required data-event-name="signing_demo_upload_file_clicked" aria-label="Files to sign" aria-describedby="file-hint file-error">
                        <p id="file-hint">PDF, XML or any file for an ASiC-E container.</p>
                    </div>
                    <button type="button" class="sample-file-button" id="use-sample-file">Or try a sample PDF</button>
                    <ul class="selected-files" id="selected-files" aria-label="Selected files"></ul>
                    <div class="field-error" id="file-feedback" hidden>
                        <div id="file-error" role="alert"></div>
                        <button type="button" id="suggested-format" class="format-suggestion" hidden></button>
                    </div>
                    <p class="field-hint">Preview stays in your browser. Continuing uploads the file for signing.</p>
                </div>
                <div class="setup-section">
                    <span class="eyebrow">02 / YOUR INTEGRATION</span><h2>Make it your setup.</h2>
                    <label for="signType">Signing flow</label>
                    <div class="signing-select">
                    <select name="signType" id="signType" data-event-name-onchange="signing_demo_signature_creation_changed" aria-describedby="flow-hint">
                        @foreach($flows as $value => $flow)<option value="{{ $value }}" @selected(old('signType', 'external') === $value)>{{ $flow[0] }}</option>@endforeach
                    </select>
                    <i data-lucide="chevron-down" aria-hidden="true"></i>
                    </div>
                    @foreach($flows as $value => $flow)<p class="field-hint flow-description" data-flow="{{ $value }}" @if(old('signType', 'external') !== $value) hidden @endif>{{ $flow[1] }}</p>@endforeach
                    <p id="flow-hint" class="sr-only">Choose a hosted, embedded API, digest, e-Seal or multiple-signer flow.</p>
                    <fieldset class="format-fieldset"><legend>Output format</legend><div class="format-options">
                        @foreach($formats as $value => $format)
                            <label><input type="radio" name="containerType" value="{{ $value }}" @checked(old('containerType', 'pdf') === $value) data-event-name="signing_demo_container_type_changed"><span>{{ $format[0] }}</span></label>
                        @endforeach
                    </div></fieldset>
                    @foreach($formats as $value => $format)<p class="field-hint format-description" data-format="{{ $value }}" @if(old('containerType', 'pdf') !== $value) hidden @endif>{{ $format[1] }}</p>@endforeach
                </div>
                <div class="setup-section optional-settings">
                    <details @if($errors->hasAny(['simple_email','simple_sms','simple_firstname','simple_lastname'])) open @endif>
                        <summary><i data-lucide="user-round" aria-hidden="true"></i>Simple signature details <span class="optional-label">Optional</span></summary>
                        <p class="field-hint">Enter the contact details of the person who needs to sign. When you continue and the document is prepared successfully, eID Easy sends that signer an invitation by email, SMS, or both, depending on what you provide. Leave these fields empty to skip the invitation. Names entered here are unverified.</p>
                        <label for="simple_email">Signer’s email</label><input type="email" name="simple_email" id="simple_email" value="{{ old('simple_email') }}" autocomplete="email" data-event-name-onchange="signing_demo_simple_signature_email_changed">
                        <label for="simple_sms">Signer’s mobile number</label><input type="tel" name="simple_sms" id="simple_sms" value="{{ old('simple_sms') }}" placeholder="+372…" autocomplete="tel" data-event-name-onchange="signing_demo_phone_changed"><p class="field-hint">Include the country code, for example +37255512345.</p>
                        <div class="input-pair"><div><label for="simple_firstname">Signer’s first name</label><input name="simple_firstname" id="simple_firstname" value="{{ old('simple_firstname') }}" autocomplete="given-name" data-event-name-onchange="signing_demo_first_name_changed"></div><div><label for="simple_lastname">Signer’s last name</label><input name="simple_lastname" id="simple_lastname" value="{{ old('simple_lastname') }}" autocomplete="family-name" data-event-name-onchange="signing_demo_last_name_changed"></div></div>
                    </details>
                    <details @if($errors->has('redirect_uri')) open @endif><summary><i data-lucide="link" aria-hidden="true"></i>Redirect settings <span class="optional-label">Optional</span></summary><label for="redirect_uri">Return URL after signing</label><input type="url" name="redirect_uri" id="redirect_uri" value="{{ old('redirect_uri') }}" placeholder="https://your-app.example/complete" data-event-name-onchange="signing_demo_redirect_url_typed"><p class="field-hint">Leave empty to return here and download the signed file.</p></details>
                </div>
            </section>
            <section class="preview-panel" aria-label="Document preview and signature appearance">
                <div class="preview-toolbar"><span class="eyebrow">03 / SIGNATURE APPEARANCE</span><div class="page-navigation" id="page-navigation" hidden><button type="button" class="icon-button" id="previous-page" aria-label="Previous page"><i data-lucide="chevron-left" aria-hidden="true"></i></button><span id="page-status" aria-live="polite">Page 1 of 1</span><button type="button" class="icon-button" id="next-page" aria-label="Next page"><i data-lucide="chevron-right" aria-hidden="true"></i></button></div></div>
                <div class="preview-stage" id="preview-stage">
                    <div class="preview-empty" id="preview-empty"><span class="empty-icon"><i data-lucide="file-scan" aria-hidden="true"></i></span><h2>A place for your signature.</h2><p id="preview-message" role="status">Choose a PDF to preview it and position its visual signature. Other files can be signed in an ASiC-E container.</p></div>
                    <div class="pdf-page" id="pdf-page" hidden><canvas id="pdf-canvas" aria-label="Uploaded PDF page preview"></canvas><button type="button" id="signature-marker" class="signature-marker" aria-label="Signature placement. Use arrow keys to move; hold Shift for larger steps." aria-describedby="placement-hint" hidden><i data-lucide="pen-line" aria-hidden="true"></i><span>Signature placement</span></button></div>
                </div>
                <fieldset class="appearance-controls" id="appearance-controls"><legend class="sr-only">PDF signature appearance</legend>
                    <label class="visual-toggle" for="hide_pdf_visual"><input type="checkbox" name="hide_pdf_visual" id="hide_pdf_visual" @checked(old('hide_pdf_visual')) data-event-name="signing_demo_hide_visual_signature_clicked"><span>Hide visual signature</span></label>
                    <div id="placement-fields">
                        <div class="coordinate-fields"><div><label for="pdf_x">X (pt)</label><input name="pdf_x" id="pdf_x" type="number" min="0" step="1" value="{{ old('pdf_x') }}" placeholder="Auto" data-event-name-onchange="signing_demo_pdf_x_coordinate_changed"></div><div><label for="pdf_y">Y (pt)</label><input name="pdf_y" id="pdf_y" type="number" min="0" step="1" value="{{ old('pdf_y') }}" placeholder="Auto" data-event-name-onchange="signing_demo_pdf_y_coordinate_changed"></div><div><label for="pdf_page">Page</label><input name="pdf_page_display" id="pdf_page" type="number" min="1" step="1" value="{{ old('pdf_page_display', old('pdf_page') !== null ? (int) old('pdf_page') + 1 : 1) }}"><input type="hidden" name="pdf_page" id="pdf_page_index" value="{{ old('pdf_page') }}"></div></div>
                        <div class="placement-presets"><span>Place at</span><button type="button" data-preset="left">Bottom left</button><button type="button" data-preset="right">Bottom right</button><button type="button" data-preset="auto">Automatic</button></div>
                        <p class="field-hint" id="placement-hint">Choose a PDF, then click or drag to position. X and Y use PDF points from the bottom-left corner. Leave coordinates empty for automatic placement.</p>
                        <p class="field-hint" id="preview-note">The placement guide is approximate. The signing service determines the final appearance and may move it to avoid existing signatures.</p>
                    </div>
                </fieldset>
                <p class="non-pdf-note" id="non-pdf-note" hidden>Visual placement applies to PDF signatures. Your chosen format signs the file contents without a PDF appearance.</p>
            </section>
        </div>
        <div class="signing-action-bar"><div><span class="eyebrow">READY WHEN YOU ARE</span><p id="signing-summary" aria-live="polite">Choose a file to get started.</p></div><button type="submit" class="demo-button" id="start-signing" data-event-name="signing_demo_sign_now_clicked">Continue to signing <i data-lucide="arrow-right" aria-hidden="true"></i></button></div>
        <div class="integration-notes"><details><summary><i data-lucide="code-xml" aria-hidden="true"></i>Integration notes</summary><p class="field-hint">Hosted signing sends the documents to eID Easy. API flow embeds the signing widget in this app. Digest signing sends hashes to eID Easy; PDF preparation and assembly use the configured PAdES helper. Multiple signers are configured on the next page.</p><a href="https://docs.eideasy.com/" target="_blank" rel="noopener">Read the API documentation</a></details></div>
        <p class="demo-footnote">This is a live integration demo. Use test documents. Continuing may open a signing provider or create a sandbox e-Seal, depending on your selected flow.</p>
    </form>
@endsection
@section('scripts')<script src="{{ mix('/js/signing-playground.js') }}" defer></script>@endsection
