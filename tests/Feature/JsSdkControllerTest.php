<?php

namespace Tests\Feature;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JsSdkControllerTest extends TestCase
{
    #[DataProvider('timestampOffsets')]
    public function testDecryptedPayloadKeepsTheSixtySecondWindow(int $offset, int $status): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 12:00:00', 'UTC'));
        $secret = str_repeat('s', 32);
        config(['eideasy.secret' => $secret]);
        $iv = random_bytes(16);
        $payload = openssl_encrypt(json_encode([
            'timestamp' => now()->addSeconds($offset)->getTimestampMs(),
            'eid_nonce' => 'test-nonce',
            'firstname' => 'Test',
        ]), 'AES-256-CBC', $secret, 0, $iv);

        $response = $this->postJson('/api/js-sdk/decrypt-user-data', [
            'payload' => $payload,
            'iv' => base64_encode($iv),
            'hmac' => base64_encode(hash_hmac('SHA256', $payload, $secret, true)),
        ])->assertStatus($status);

        if ($status === 200) {
            $response->assertExactJson(['firstname' => 'Test']);
        }
    }

    public static function timestampOffsets(): array
    {
        return [
            'current' => [0, 200],
            'past boundary' => [-60, 200],
            'future boundary' => [60, 200],
            'expired' => [-61, 403],
            'too far in future' => [61, 403],
        ];
    }
}
