<?php

namespace Ginkida\AgentRunner\Exceptions;

class HmacVerificationException extends AgentRunnerException
{
    public static function missingHeaders(): static
    {
        return new static('Missing signature, timestamp, or nonce headers.');
    }

    public static function invalidTimestamp(): static
    {
        return new static('Invalid or expired timestamp.');
    }

    public static function invalidNonce(): static
    {
        return new static('Invalid nonce format.');
    }

    public static function invalidSignature(): static
    {
        return new static('Invalid HMAC signature.');
    }
}
