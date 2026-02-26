<?php

namespace Ginkida\AgentRunner\Tests\Unit\Client;

use Ginkida\AgentRunner\Client\HmacSigner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HmacSignerTest extends TestCase
{
    private HmacSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = new HmacSigner('test-secret');
    }

    #[Test]
    public function it_signs_a_request_body(): void
    {
        $result = $this->signer->sign('{"message":"hello"}');

        $this->assertArrayHasKey('signature', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('nonce', $result);

        $this->assertStringStartsWith('sha256=', $result['signature']);
        $this->assertTrue(ctype_digit($result['timestamp']));
        $this->assertSame(32, strlen($result['nonce'])); // 16 random bytes hex-encoded
    }

    #[Test]
    public function it_verifies_a_valid_signature(): void
    {
        $body = '{"test":"data"}';
        $hmac = $this->signer->sign($body);

        $this->assertTrue($this->signer->verify(
            $hmac['signature'],
            $hmac['timestamp'],
            $hmac['nonce'],
            $body,
        ));
    }

    #[Test]
    public function it_rejects_tampered_body(): void
    {
        $hmac = $this->signer->sign('{"original":"data"}');

        $this->assertFalse($this->signer->verify(
            $hmac['signature'],
            $hmac['timestamp'],
            $hmac['nonce'],
            '{"tampered":"data"}',
        ));
    }

    #[Test]
    public function it_rejects_wrong_secret(): void
    {
        $hmac = $this->signer->sign('body');

        $otherSigner = new HmacSigner('wrong-secret');

        $this->assertFalse($otherSigner->verify(
            $hmac['signature'],
            $hmac['timestamp'],
            $hmac['nonce'],
            'body',
        ));
    }

    #[Test]
    public function it_rejects_empty_signature(): void
    {
        $this->assertFalse($this->signer->verify('', (string) time(), bin2hex(random_bytes(16)), 'body'));
    }

    #[Test]
    public function it_rejects_empty_timestamp(): void
    {
        $this->assertFalse($this->signer->verify('sha256=abc', '', bin2hex(random_bytes(16)), 'body'));
    }

    #[Test]
    public function it_rejects_empty_nonce(): void
    {
        $this->assertFalse($this->signer->verify('sha256=abc', (string) time(), '', 'body'));
    }

    #[Test]
    public function it_rejects_stale_timestamp(): void
    {
        $body = 'test-body';
        $staleTimestamp = (string) (time() - 300); // 5 minutes ago
        $nonce = bin2hex(random_bytes(16));

        $payload = $staleTimestamp.'.'.$nonce.'.'.$body;
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'test-secret');

        $this->assertFalse($this->signer->verify($signature, $staleTimestamp, $nonce, $body));
    }

    #[Test]
    public function it_rejects_non_numeric_timestamp(): void
    {
        $this->assertFalse($this->signer->verify('sha256=abc', 'not-a-number', bin2hex(random_bytes(16)), 'body'));
    }

    #[Test]
    public function it_rejects_short_nonce(): void
    {
        $this->assertFalse($this->signer->verify('sha256=abc', (string) time(), 'short', 'body'));
    }

    #[Test]
    public function it_rejects_nonce_with_invalid_characters(): void
    {
        $this->assertFalse($this->signer->verify('sha256=abc', (string) time(), str_repeat('!', 32), 'body'));
    }

    #[Test]
    public function it_accepts_nonce_with_dashes_and_underscores(): void
    {
        $body = 'test';
        $nonce = 'valid_nonce-with-dash';
        $timestamp = (string) time();

        $payload = $timestamp.'.'.$nonce.'.'.$body;
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'test-secret');

        $this->assertTrue($this->signer->verify($signature, $timestamp, $nonce, $body));
    }

    #[Test]
    public function it_verifies_empty_body(): void
    {
        $hmac = $this->signer->sign('');

        $this->assertTrue($this->signer->verify(
            $hmac['signature'],
            $hmac['timestamp'],
            $hmac['nonce'],
            '',
        ));
    }
}
