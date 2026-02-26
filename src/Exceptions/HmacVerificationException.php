<?php

namespace Ginkida\AgentRunner\Exceptions;

class HmacVerificationException extends AgentRunnerException
{
    public static function missingHeaders(): self
    {
        return new self('Missing signature, timestamp, or nonce headers.');
    }

    public static function invalidTimestamp(): self
    {
        return new self('Invalid or expired timestamp.');
    }

    public static function invalidNonce(): self
    {
        return new self('Invalid nonce format.');
    }

    public static function invalidSignature(): self
    {
        return new self('Invalid HMAC signature.');
    }
}
