@php
    $flow = old('signType', 'external');
    $format = old('containerType', 'pdf');
    $credentials = ['client_id' => '<client ID>', 'secret' => '<server-side secret>'];
    $document = $credentials + ['doc_id' => '<prepared document ID>'];
    $prepare = $credentials + [
        'baseline' => 'LT',
        'signature_redirect' => 'https://demo.example/show-download-signed-file?file_id=example',
        'return_available_methods' => true,
    ];
    $file = fn ($name, $mime, $content) => ['fileName' => $name, 'mimeType' => $mime, 'fileContent' => $content];
    $pdf = $file('agreement.pdf', 'application/pdf', '<base64 PDF>');
    $text = $file('terms.txt', 'text/plain', '<base64 file contents>');
    $pdfDigest = array_replace($pdf, ['fileContent' => '<PAdES helper digest>']);
    $asiceDigest = array_replace($text, ['fileContent' => '<base64 SHA-256 digest>']);
    $prepareEndpoint = '/api/signatures/prepare-files-for-signing';
    $download = ['/api/signatures/download-signed-file', $document];
    $fullFileExamples = [
        'pdf' => [[$prepareEndpoint, $prepare + ['container_type' => 'pdf', 'show_visual' => true, 'files' => [$pdf]]]],
        'asice' => [[$prepareEndpoint, $prepare + ['container_type' => 'asice', 'show_visual' => false, 'files' => [$text]]]],
        'asice-for-each-file' => [[$prepareEndpoint, $prepare + [
            'container_type' => 'asice', 'show_visual' => false, 'files' => [$text + ['containerFileName' => 'terms_0.asice']],
        ]]],
        'enveloped-xades' => [[$prepareEndpoint, $prepare + [
            'container_type' => 'xades', 'show_visual' => false, 'signature_packaging' => 'ENVELOPED',
            'files' => [$file('invoice.xml', 'application/xml', '<base64 XML>')],
        ]]],
    ];
    $digestExamples = [
        'pdf' => [
            ['/api/detached-pades/prepare', ['fileContent' => '<base64 PDF>']],
            [$prepareEndpoint, $prepare + ['container_type' => 'cades', 'show_visual' => false, 'files' => [$pdfDigest]]],
            $download,
            ['/api/detached-pades/complete', [
                'fileContent' => '<base64 PDF>', 'signatureTime' => '<time returned by helper>',
                'signatureValue' => '<base64 detached signature>',
            ]],
        ],
        'asice' => [[$prepareEndpoint, $prepare + ['container_type' => 'xades', 'show_visual' => false, 'files' => [$asiceDigest]]], $download],
    ];
    $steps = [
        'external' => ['The browser opens the signing page URL returned by the API.', []],
        'local' => ['The embedded widget makes provider-specific requests when the signer chooses a method.', []],
        'digest' => ['The embedded widget starts the provider-specific signing flow. The PDF example also shows the calls to the configured PAdES helper.', []],
        'eseal' => ['The backend uses sandbox credentials and authenticates the e-Seal request with an HMAC.', [
            ['/api/signatures/e-seal/create', $document + ['timestamp' => 1700000000, 'hmac' => '<server-generated HMAC>']],
        ]],
        'multisign' => ['The browser opens the management page returned by the API.', [
            ['/api/signatures/signing-queues', $document + ['has_management_page' => true]],
        ]],
    ];
@endphp

<details class="api-examples">
    <summary>API request examples</summary>
    <p class="field-hint">These example POST bodies reflect the selected flow and format. Optional fields are omitted. Credentials, file contents and document references are placeholders.</p>
    @foreach($steps as $key => [$note, $extraRequests])
        <div data-flow="{{ $key }}" @if($flow !== $key) hidden @endif>
            <p class="field-hint">{{ $note }}</p>
            @foreach($key === 'digest' ? $digestExamples : $fullFileExamples as $exampleFormat => $requests)
                <div data-format="{{ $exampleFormat }}" @if($format !== $exampleFormat) hidden @endif>
                    @foreach($key === 'digest' ? $requests : [...$requests, ...$extraRequests, $download] as [$endpoint, $body])
                        <h2>POST <code>{{ $endpoint }}</code></h2>
                        <pre tabindex="0"><code>{{ json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) }}</code></pre>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
    <a href="https://docs.eideasy.com/electronic-signatures/" target="_blank" rel="noopener">Electronic signature documentation</a>
</details>
