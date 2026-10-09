<?php

namespace Tests\Feature;

use EidEasy\Api\EidEasyApi;
use Mockery;
use Tests\TestCase;

class AudkenniEmbeddedLoginTest extends TestCase
{
    private function mockApi()
    {
        $api = Mockery::mock(EidEasyApi::class);
        $api->shouldReceive('setClientId')->once();
        $api->shouldReceive('setSecret')->once();
        $api->shouldReceive('setApiUrl')->once();
        $this->app->instance(EidEasyApi::class, $api);
        return $api;
    }

    public function testStartForwardsValidatedDataAndReturnsChallenge(): void
    {
        $data = ['country' => 'IS', 'idcode' => '0000000000', 'lang' => 'en'];
        $result = ['status' => 'OK', 'token' => str_repeat('a', 64), 'challenge' => '1234', 'interval' => 2];
        $this->mockApi()->shouldReceive('startIdentification')->once()->with('audkenni-login', $data)->andReturn($result);
        $this->postJson('/api/identity/start', ['method' => 'audkenni-login'] + $data)
            ->assertOk()->assertExactJson($result);
    }

    public function testCompletionForwardsTokenAndPreservesPendingStatus(): void
    {
        $data = ['token' => str_repeat('a', 64), 'lang' => 'en'];
        $this->mockApi()->shouldReceive('completeIdentification')->once()->with('audkenni-login', $data)
            ->andReturn(['status' => 'RUNNING', 'interval' => 2]);
        $this->postJson('/api/identity/finish', ['method' => 'audkenni-login'] + $data)
            ->assertOk()->assertExactJson(['status' => 'RUNNING', 'interval' => 2]);
    }

    public function testInvalidInputDoesNotReachCore(): void
    {
        $this->mockApi()->shouldNotReceive('startIdentification');
        $this->postJson('/api/identity/start', ['method' => 'audkenni-login', 'country' => 'EE', 'idcode' => 'bad'])
            ->assertStatus(422)->assertJsonValidationErrors(['country', 'idcode']);
    }
}
