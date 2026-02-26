<?php

namespace Ginkida\AgentRunner\Tests\Unit\Exceptions;

use Ginkida\AgentRunner\Exceptions\AgentRunnerException;
use Ginkida\AgentRunner\Exceptions\HmacVerificationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HmacVerificationExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_base_exception(): void
    {
        $exception = HmacVerificationException::missingHeaders();

        $this->assertInstanceOf(AgentRunnerException::class, $exception);
    }

    #[Test]
    public function missing_headers_has_correct_message(): void
    {
        $exception = HmacVerificationException::missingHeaders();

        $this->assertSame('Missing signature, timestamp, or nonce headers.', $exception->getMessage());
    }

    #[Test]
    public function invalid_timestamp_has_correct_message(): void
    {
        $exception = HmacVerificationException::invalidTimestamp();

        $this->assertSame('Invalid or expired timestamp.', $exception->getMessage());
    }

    #[Test]
    public function invalid_nonce_has_correct_message(): void
    {
        $exception = HmacVerificationException::invalidNonce();

        $this->assertSame('Invalid nonce format.', $exception->getMessage());
    }

    #[Test]
    public function invalid_signature_has_correct_message(): void
    {
        $exception = HmacVerificationException::invalidSignature();

        $this->assertSame('Invalid HMAC signature.', $exception->getMessage());
    }
}
