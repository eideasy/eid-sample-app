<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SigningPlaygroundTest extends TestCase
{
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        config(['eideasy.api_url' => 'https://signing.example', 'eideasy.client_id' => 'demo-client', 'eideasy.secret' => 'demo-secret']);
        $handler = HandlerStack::create(new MockHandler([new Response(200, [], json_encode([
            'status' => 'OK', 'doc_id' => 'demo-document', 'available_methods' => [],
        ]))]));
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

    public function testValidationRetainsSettingsAndRendersAllExistingChoices(): void
    {
        $this->from('/sign-custom-file')->post('/sign-custom-file', ['signType' => 'digest', 'containerType' => 'asice', 'simple_firstname' => 'Demo'])->assertRedirect('/sign-custom-file')->assertSessionHasErrors('unsigned_file');
        $this->get('/sign-custom-file')->assertOk()->assertSee('value="digest" selected', false)->assertSee('value="Demo"', false)->assertSee('ASiC-E per file')->assertSee('Automatic e-Seal')->assertSee('Multiple signers');
    }
}
