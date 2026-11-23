<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SmartIdEmbeddedLoginTest extends TestCase
{
    public function testStartAndFinishUseTheConfiguredHttpClient(): void
    {
        config([
            'eideasy.client_id' => 'demo-client',
            'eideasy.secret' => 'demo-secret',
            'eideasy.api_url' => 'https://identity.example',
            'eideasy.notify_email' => null,
        ]);
        $start = ['status' => 'OK', 'token' => 'demo-token', 'challenge' => '1234'];
        $finish = ['status' => 'RUNNING'];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($start)),
            new Response(200, [], json_encode($finish)),
        ]));
        $requests = [];
        $handler->push(Middleware::history($requests));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));

        $this->postJson('/api/identity/start', [
            'method' => 'smartid', 'country' => 'EE', 'idcode' => '00000000000', 'lang' => 'en',
        ])->assertOk()->assertExactJson($start);
        $this->postJson('/api/identity/finish', [
            'method' => 'smartid', 'token' => $start['token'], 'lang' => 'en',
        ])->assertOk()->assertExactJson($finish);

        $this->assertSame(
            ['/api/identity/demo-client/smartid/start', '/api/identity/demo-client/smartid/complete'],
            array_map(fn (array $entry) => $entry['request']->getUri()->getPath(), $requests),
        );
    }
}
