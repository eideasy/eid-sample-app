<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SigningPlaygroundTest extends TestCase
{
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        config(['eideasy.api_url' => 'https://signing.example', 'eideasy.client_id' => 'demo-client', 'eideasy.secret' => 'demo-secret']);
        $this->mockSigningApi(new Response(200, [], json_encode([
            'status' => 'OK', 'doc_id' => 'demo-document', 'available_methods' => [],
        ])));
    }

    private function mockSigningApi(Response ...$responses): void
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->requests));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'unsigned_file' => [UploadedFile::fake()->create('agreement.pdf', 5, 'application/pdf')],
            'signType' => 'external', 'containerType' => 'pdf',
        ], $overrides);
    }

    private function prepared(): array
    {
        return json_decode((string) $this->requests[0]['request']->getBody(), true);
    }

    public function testAutomaticPlacementDoesNotSendEmptyCoordinates(): void
    {
        $this->post('/sign-custom-file', $this->payload(['pdf_x' => '', 'pdf_y' => '', 'pdf_page' => '']))->assertRedirect();
        $this->assertArrayNotHasKey('visual_coordinates', $this->prepared());
        $this->assertTrue($this->prepared()['show_visual']);
    }

    public function testCustomPlacementPreservesZeroCoordinatesAndPageIndex(): void
    {
        $this->post('/sign-custom-file', $this->payload(['pdf_x' => '0', 'pdf_y' => '24', 'pdf_page' => '1']))->assertRedirect();
        $this->assertSame(['x' => 0, 'y' => 24, 'page' => 1], $this->prepared()['visual_coordinates']);
    }

    public function testHiddenAppearanceIgnoresStaleCoordinates(): void
    {
        $this->post('/sign-custom-file', $this->payload(['hide_pdf_visual' => 'on', 'pdf_x' => 'invalid', 'pdf_y' => -5]))->assertRedirect();
        $this->assertArrayNotHasKey('visual_coordinates', $this->prepared());
        $this->assertFalse($this->prepared()['show_visual']);
    }

    #[DataProvider('unrestrictedSignatureLevels')]
    public function testAllSignatureLevelsAreAllowedByDefault(array $overrides): void
    {
        $this->post('/sign-custom-file', $this->payload($overrides))->assertRedirect();
        $this->assertArrayNotHasKey('allowed_signature_levels', $this->prepared());
    }

    public static function unrestrictedSignatureLevels(): array
    {
        return [
            'not selected' => [[]],
            'every level selected' => [['allowed_signature_levels' => ['qualified', 'simple', 'advanced']]],
        ];
    }

    public function testSelectedSignatureLevelsLimitTheSigningOptions(): void
    {
        $this->post('/sign-custom-file', $this->payload(['allowed_signature_levels' => ['qualified', 'advanced']]))->assertRedirect();
        $this->assertSame(['advanced', 'qualified'], $this->prepared()['allowed_signature_levels']);
    }

    public function testSignatureLevelsAreNotSentForESeals(): void
    {
        config(['eideasy.eid_test_api' => 'https://sandbox.example', 'eideasy.eid_test_client_id' => 'sandbox-client', 'eideasy.eid_test_secret' => 'sandbox-secret']);
        $this->mockSigningApi(
            new Response(200, [], '{"status":"OK","doc_id":"demo-document"}'),
            new Response(200, [], '{"status":"OK"}'),
        );
        $this->post('/sign-custom-file', $this->payload(['signType' => 'eseal', 'allowed_signature_levels' => ['qualified']]))->assertRedirect();
        $this->assertArrayNotHasKey('allowed_signature_levels', $this->prepared());
    }

    public function testUnknownSignatureLevelsAreRejected(): void
    {
        $this->postJson('/sign-custom-file', $this->payload(['allowed_signature_levels' => ['qualified', 'basic']]))
            ->assertUnprocessable()->assertJsonValidationErrors('allowed_signature_levels.1');
        $this->postJson('/sign-custom-file', $this->payload(['allowed_signature_levels' => 'qualified']))
            ->assertUnprocessable()->assertJsonValidationErrors('allowed_signature_levels');
        $this->assertCount(0, $this->requests);
    }

    public function testHumanPageNumbersConvertOnTheServerWithoutJavaScript(): void
    {
        $this->post('/sign-custom-file', $this->payload(['pdf_x' => 10, 'pdf_y' => 20, 'pdf_page' => 0, 'pdf_page_display' => 2]))->assertRedirect();
        $this->assertSame(1, $this->prepared()['visual_coordinates']['page']);
    }

    public function testHumanPageNumbersRejectZero(): void
    {
        $this->postJson('/sign-custom-file', $this->payload(['pdf_page_display' => 0]))->assertUnprocessable()->assertJsonValidationErrors('pdf_page_display');
        $this->assertCount(0, $this->requests);
    }

    public function testAsiceIgnoresPdfCoordinatesAndKeepsAllFiles(): void
    {
        $this->post('/sign-custom-file', $this->payload([
            'containerType' => 'asice', 'pdf_x' => 'invalid', 'pdf_y' => -5,
            'unsigned_file' => [UploadedFile::fake()->create('one.txt', 1), UploadedFile::fake()->create('two.txt', 1)],
        ]))->assertRedirect();
        $this->assertArrayNotHasKey('visual_coordinates', $this->prepared());
        $this->assertCount(2, $this->prepared()['files']);
    }

    public function testPdfRejectsExtraFilesBeforeCallingTheSigningService(): void
    {
        $this->postJson('/sign-custom-file', $this->payload([
            'unsigned_file' => [UploadedFile::fake()->create('one.pdf', 1, 'application/pdf'), UploadedFile::fake()->create('two.pdf', 1, 'application/pdf')],
        ]))->assertUnprocessable()->assertJsonValidationErrors('unsigned_file');
        $this->assertCount(0, $this->requests);
    }

    public function testPdfRejectsNonPdfFilesAndIncompleteCoordinates(): void
    {
        $this->postJson('/sign-custom-file', $this->payload(['unsigned_file' => [UploadedFile::fake()->create('photo.jpg', 1, 'image/jpeg')]]))->assertUnprocessable()->assertJsonValidationErrors('unsigned_file.0');
        $this->postJson('/sign-custom-file', $this->payload(['pdf_x' => '15']))->assertUnprocessable()->assertJsonValidationErrors('pdf_y');
        $this->postJson('/sign-custom-file', $this->payload(['pdf_x' => 'abc', 'pdf_y' => '-2', 'pdf_page' => '1.5']))->assertUnprocessable()->assertJsonValidationErrors(['pdf_x', 'pdf_y', 'pdf_page']);
        $this->assertCount(0, $this->requests);
    }

    public function testEnvelopedXadesRejectsNonXmlFiles(): void
    {
        $this->postJson('/sign-custom-file', $this->payload(['containerType' => 'enveloped-xades']))->assertUnprocessable()->assertJsonValidationErrors('unsigned_file.0');
        $this->assertCount(0, $this->requests);
    }

    public function testEnvelopedXadesAcceptsXmlWithoutADeclaration(): void
    {
        $this->post('/sign-custom-file', $this->payload([
            'containerType' => 'enveloped-xades',
            'unsigned_file' => [UploadedFile::fake()->createWithContent('invoice.xml', '<root><item>example</item></root>')],
        ]))->assertRedirect();
        $this->assertSame('application/xml', $this->prepared()['files'][0]['mimeType']);
    }

    public function testEnvelopedXadesRejectsMalformedXmlAndDoctypes(): void
    {
        foreach (['<root>', '<!DOCTYPE root><root/>'] as $xml) {
            $this->postJson('/sign-custom-file', $this->payload([
                'containerType' => 'enveloped-xades',
                'unsigned_file' => [UploadedFile::fake()->createWithContent('invoice.xml', $xml)],
            ]))->assertUnprocessable()->assertJsonValidationErrors('unsigned_file.0');
        }
        $this->assertCount(0, $this->requests);
    }

    public function testValidationRetainsSettingsAndRendersAllExistingChoices(): void
    {
        $this->from('/sign-custom-file')->post('/sign-custom-file', ['signType' => 'digest', 'containerType' => 'asice', 'simple_firstname' => 'Demo'])->assertRedirect('/sign-custom-file')->assertSessionHasErrors('unsigned_file');
        $this->get('/sign-custom-file')->assertOk()->assertSee('value="digest" selected', false)->assertSee('value="Demo"', false)->assertSee('ASiC-E per file')->assertSee('Automatic e-Seal')->assertSee('Multiple signers');
    }
    public function testValidationRestoresPlacementWithTheUploadedDocumentFingerprint(): void
    {
        $file = UploadedFile::fake()->createWithContent('agreement.pdf', "%PDF-1.4\noriginal document");
        $fingerprint = hash_file('sha256', $file->getRealPath());
        $this->from('/sign-custom-file')->post('/sign-custom-file', $this->payload([
            'unsigned_file' => [$file], 'pdf_x' => 32, 'pdf_y' => 48, 'pdf_page_display' => 2,
            'redirect_uri' => 'invalid-url', 'placement_document_fingerprint' => $fingerprint,
        ]))->assertRedirect('/sign-custom-file')->assertSessionHasErrors('redirect_uri')
            ->assertSessionHasInput('placement_document_fingerprint', $fingerprint)
            ->assertSessionHasInput('pdf_x', 32)->assertSessionHasInput('pdf_y', 48)
            ->assertSessionHasInput('pdf_page_display', 2);
        $this->get('/sign-custom-file')->assertOk()
            ->assertSee('id="placement-document-fingerprint" value="'.$fingerprint.'"', false);
        $this->assertCount(0, $this->requests);
    }

    public function testUploadedNonPdfFilesAreOfferedOnlyAsAttachments(): void
    {
        $this->post('/sign-custom-file', $this->payload([
            'signType' => 'local',
            'containerType' => 'asice',
            'unsigned_file' => [UploadedFile::fake()->createWithContent('test & notes.html', '<p>Test file</p>')],
        ]))->assertRedirect('/sign-locally-sample?doc_id=demo-document');

        $this->get('/sign-locally-sample?doc_id=demo-document')->assertOk()
            ->assertSee('test &amp; notes.html', false)->assertDontSee('<object', false);
        $this->get('/download-unsigned-file?doc_id=demo-document&filename=test%20%26%20notes.html')
            ->assertOk()->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get('/download-unsigned-file?doc_id=demo-document&filename=other.html')->assertNotFound();
        $this->get('/sign-locally-sample?doc_id%5B%5D=demo-document')->assertRedirect('/sign-custom-file');
    }

    public function testEmbeddedSigningRendersMethodsAndAValidPdfPreview(): void
    {
        $this->post('/sign-custom-file', $this->payload([
            'signType' => 'local',
            'unsigned_file' => [UploadedFile::fake()->createWithContent(
                'sample.pdf',
                file_get_contents(public_path('samples/signing-demo.pdf')),
            )],
        ]))->assertRedirect('/sign-locally-sample?doc_id=demo-document');
        Cache::put('available_methods-demo-document', ['smart-id-signature']);
        $this->get('/sign-locally-sample?doc_id=demo-document')->assertOk()
            ->assertSee('data:application/pdf;base64,', false)
            ->assertSee('doc-id="demo-document"', false)
            ->assertSee('smart-id-signature');
    }

    public function testOAuthCallbackRequiresMatchingStateAndUsesItOnce(): void
    {
        Http::fake([
            'https://signing.example/oauth/access_token' => Http::response(['access_token' => 'test-token']),
            'https://signing.example/api/v2/user_data*' => Http::response(['firstname' => 'Demo']),
        ]);

        $response = $this->get('/oauth/start')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $parameters);
        $state = $parameters['state'];
        $this->assertNotEmpty($state);

        $this->get('/?code=test-code&state=unrelated')->assertOk()->assertSee('Start a new OAuth login');
        $this->get('/?error=cancelled&error_description=untrusted-description')->assertOk()
            ->assertDontSee('untrusted-description');
        Http::assertNothingSent();

        $callback = '/?' . http_build_query(['code' => 'test-code', 'state' => $state]);
        $this->get($callback)->assertOk()->assertSee('OAuth login result')->assertSee('Demo');
        $this->get($callback)->assertOk()->assertSee('Start a new OAuth login');
        Http::assertSentCount(2);
    }

    public function testUnsupportedDigestFormatStopsBeforePreparation(): void
    {
        $this->postJson('/sign-custom-file', $this->payload([
            'signType' => 'digest',
            'containerType' => 'asice-for-each-file',
        ]))->assertUnprocessable()->assertJsonValidationErrors('containerType');
        $this->assertCount(0, $this->requests);
    }

    public function testMissingDocumentIdShowsAnActionableError(): void
    {
        $this->app->instance(Client::class, new Client(['handler' => new MockHandler([
            new Response(200, [], '{"status":"OK"}'),
        ])]));

        $this->post('/sign-custom-file', $this->payload())
            ->assertRedirect('/sign-custom-file')->assertSessionHas('message');
        $this->get('/sign-custom-file')->assertOk()->assertSee('choose your files again and retry');
    }

    public function testSandboxSealFailureDoesNotOfferADownload(): void
    {
        config([
            'eideasy.eid_test_api' => 'https://sandbox.example',
            'eideasy.eid_test_client_id' => 'sandbox-client',
            'eideasy.eid_test_secret' => 'sandbox-secret',
        ]);
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"status":"OK","doc_id":"sandbox-document"}'),
            new Response(400, [], '{"status":"error"}'),
        ]));
        $handler->push(Middleware::history($this->requests));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));

        $this->post('/sign-custom-file', $this->payload(['signType' => 'eseal']))
            ->assertRedirect('/sign-custom-file')->assertSessionHas('message');
        $this->assertSame('https://sandbox.example/api/signatures/e-seal/create', (string) $this->requests[1]['request']->getUri());
        $this->get('/sign-custom-file')->assertOk()->assertSee('The sandbox could not create the e-Seal.');
    }
    public function testUnavailableSignedFilesReturnToTheDownloadPage(): void
    {
        Cache::put('doc_id-pending-file', 'pending-document');
        $this->mockSigningApi(new Response(404, [], '{"status":"error"}'));

        $this->get('/download-signed-file?file_id=pending-file')
            ->assertRedirect('/show-download-signed-file?file_id=pending-file')
            ->assertSessionHas('alert-class', 'alert-warning');
        $this->get('/show-download-signed-file?file_id=pending-file')
            ->assertOk()->assertSee('The signed files are unavailable.');
        $this->assertCount(1, $this->requests);
    }

    #[DataProvider('signedFileFormats')]
    public function testSignedFilesDownloadWithFormatSpecificHeaders(
        string $format,
        string $mimeType,
        string $filename,
        string $contents,
    ): void {
        Cache::put('doc_id-finished-file', 'finished-document');
        Cache::put('containerType-finished-file', $format);
        $this->mockSigningApi($this->signedFileResponse($filename, $contents));

        $response = $this->get('/download-signed-file?file_id=finished-file')
            ->assertOk()
            ->assertDownload()
            ->assertHeader('Content-Type', $mimeType)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertContent($contents);
        $this->assertStringContainsString('filename*=utf-8\'\'' . rawurlencode($filename), $response->headers->get('Content-Disposition'));
    }

    public static function signedFileFormats(): array
    {
        return [
            'PDF' => ['pdf', 'application/pdf', 'signed agreement.pdf', '%PDF-1.7 demo'],
            'XML' => ['enveloped-xades', 'application/xml', 'signed invoice.xml', '<invoice/>'],
        ];
    }

    public function testAddingASignatureUsesTheSessionDocumentIdForDownload(): void
    {
        $contents = 'test ASiC-E contents';
        $this->mockSigningApi(
            new Response(200, [], '{"doc_id":"add-signature-document"}'),
            $this->signedFileResponse('signed.asice', $contents),
        );

        $this->post('/add-signature-signed-file', [
            'signed_file' => UploadedFile::fake()->createWithContent('existing.asice', 'test input'),
        ])->assertRedirect('https://signing.example/add-signature?client_id=demo-client&doc_id=add-signature-document');

        $returnUrl = $this->prepared()['signature_redirect'];
        $this->get($returnUrl)->assertOk()->assertSee('Download signed file');
        $downloadUrl = str_replace('/show-download-signed-file?', '/download-signed-file?', $returnUrl);
        $this->get($downloadUrl)->assertOk()->assertDownload()->assertContent($contents);

        $request = $this->requests[1]['request'];
        $this->assertSame('/api/signatures/download-signed-file', $request->getUri()->getPath());
        $this->assertSame('add-signature-document', json_decode((string) $request->getBody(), true)['doc_id']);
    }

    private function signedFileResponse(string $filename, string $contents): Response
    {
        $encoded = base64_encode($contents);

        return new Response(200, [], json_encode([
            'status' => 'OK',
            'filename' => $filename,
            'signed_files' => [['name' => $filename, 'contents' => $encoded]],
            'signed_file_contents' => $encoded,
        ], JSON_THROW_ON_ERROR));
    }

}
