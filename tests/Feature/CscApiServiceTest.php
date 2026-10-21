<?php

namespace Tests\Feature;

use App\Services\CscApiService;
use EidEasy\Signatures\Pades;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use Mockery;
use Tests\TestCase;

class CscApiServiceTest extends TestCase
{
    public function testAccountTokenRemainsVerifiableAfterJwtUpgrade(): void
    {
        $secret = 'test-account-secret';
        config(['eideasy.secret' => $secret, 'eideasy.adobe_account_id' => 'test-account']);
        $service = new CscApiService(
            Mockery::mock(Pades::class),
            new Client(),
            'test-client',
            $secret,
            'https://example.test'
        );

        $claims = JWT::decode($service->createAccountToken(), new Key(hash('sha256', $secret, true), 'HS256'));

        $this->assertSame('test-account', $claims->sub);
        $this->assertSame('test-client', $claims->azp);
        $this->assertSame('eid-sample-app', $claims->iss);
        $this->assertNotEmpty($claims->jti);
    }
}
