<?php

namespace Ginkida\AgentRunner\Tests\Feature\Http\Middleware;

use Ginkida\AgentRunner\Client\HmacSigner;
use Ginkida\AgentRunner\Exceptions\HmacVerificationException;
use Ginkida\AgentRunner\Http\Middleware\VerifyHmacSignature;
use Ginkida\AgentRunner\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerifyHmacSignatureTest extends TestCase
{
    private VerifyHmacSignature $middleware;

    private HmacSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new VerifyHmacSignature;
        $this->signer = new HmacSigner('test-secret');
    }

    public function test_valid_signature_passes(): void
    {
        $body = '{"test":"data"}';
        $hmac = $this->signer->sign($body);

        $request = Request::create('/test', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Signature', $hmac['signature']);
        $request->headers->set('X-Timestamp', $hmac['timestamp']);
        $request->headers->set('X-Nonce', $hmac['nonce']);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('OK', $response->getContent());
    }

    public function test_missing_headers_throws(): void
    {
        $this->expectException(HmacVerificationException::class);
        $this->expectExceptionMessage('Missing signature, timestamp, or nonce headers.');

        $request = Request::create('/test', 'POST', [], [], [], [], '{}');

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_missing_signature_header_throws(): void
    {
        $this->expectException(HmacVerificationException::class);

        $request = Request::create('/test', 'POST', [], [], [], [], '{}');
        $request->headers->set('X-Timestamp', (string) time());
        $request->headers->set('X-Nonce', bin2hex(random_bytes(16)));

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_invalid_signature_throws(): void
    {
        $this->expectException(HmacVerificationException::class);
        $this->expectExceptionMessage('Invalid HMAC signature.');

        $request = Request::create('/test', 'POST', [], [], [], [], '{"test":"data"}');
        $request->headers->set('X-Signature', 'sha256=invalid');
        $request->headers->set('X-Timestamp', (string) time());
        $request->headers->set('X-Nonce', bin2hex(random_bytes(16)));

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_skips_verification_when_no_secret_configured(): void
    {
        $this->app['config']->set('agent-runner.hmac_secret', '');

        $request = Request::create('/test', 'POST', [], [], [], [], '{}');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('OK', $response->getContent());
    }

    public function test_nonce_replay_is_rejected(): void
    {
        $body = '{"test":"data"}';
        $hmac = $this->signer->sign($body);

        $request1 = Request::create('/test', 'POST', [], [], [], [], $body);
        $request1->headers->set('X-Signature', $hmac['signature']);
        $request1->headers->set('X-Timestamp', $hmac['timestamp']);
        $request1->headers->set('X-Nonce', $hmac['nonce']);

        // First request should pass
        $this->middleware->handle($request1, fn ($req) => response('OK'));

        // Second request with same nonce should fail
        $this->expectException(HmacVerificationException::class);

        $request2 = Request::create('/test', 'POST', [], [], [], [], $body);
        $request2->headers->set('X-Signature', $hmac['signature']);
        $request2->headers->set('X-Timestamp', $hmac['timestamp']);
        $request2->headers->set('X-Nonce', $hmac['nonce']);

        $this->middleware->handle($request2, fn ($req) => response('OK'));
    }

    public function test_different_nonces_both_pass(): void
    {
        $body = '{"test":"data"}';

        $hmac1 = $this->signer->sign($body);
        $request1 = Request::create('/test', 'POST', [], [], [], [], $body);
        $request1->headers->set('X-Signature', $hmac1['signature']);
        $request1->headers->set('X-Timestamp', $hmac1['timestamp']);
        $request1->headers->set('X-Nonce', $hmac1['nonce']);

        $response1 = $this->middleware->handle($request1, fn ($req) => response('OK'));
        $this->assertSame('OK', $response1->getContent());

        $hmac2 = $this->signer->sign($body);
        $request2 = Request::create('/test', 'POST', [], [], [], [], $body);
        $request2->headers->set('X-Signature', $hmac2['signature']);
        $request2->headers->set('X-Timestamp', $hmac2['timestamp']);
        $request2->headers->set('X-Nonce', $hmac2['nonce']);

        $response2 = $this->middleware->handle($request2, fn ($req) => response('OK'));
        $this->assertSame('OK', $response2->getContent());
    }

    public function test_nonce_stored_in_cache(): void
    {
        $body = '{}';
        $hmac = $this->signer->sign($body);

        $request = Request::create('/test', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Signature', $hmac['signature']);
        $request->headers->set('X-Timestamp', $hmac['timestamp']);
        $request->headers->set('X-Nonce', $hmac['nonce']);

        $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue(Cache::has('agent-runner:nonce:'.$hmac['nonce']));
    }
}
