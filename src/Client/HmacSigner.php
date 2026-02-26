<?php

namespace Ginkida\AgentRunner\Client;

/**
 * HMAC-SHA256 request signing and verification.
 *
 * Replicates the Go implementation in internal/auth/hmac.go:
 * - Payload format: "{timestamp}.{nonce}.{body}"
 * - Signature format: "sha256={hex digest}"
 * - Constant-time comparison via hash_equals()
 * - Timestamp freshness: ±2 minutes
 * - Nonce: 16 random bytes hex-encoded (32 chars)
 */
class HmacSigner
{
    private const MAX_TIMESTAMP_AGE = 120; // 2 minutes in seconds

    public function __construct(
        private readonly string $secret,
    ) {}

    /**
     * Sign a request body and return [signature, timestamp, nonce].
     *
     * @return array{signature: string, timestamp: string, nonce: string}
     */
    public function sign(string $body): array
    {
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));

        $payload = $timestamp.'.'.$nonce.'.'.$body;
        $signature = 'sha256='.hash_hmac('sha256', $payload, $this->secret);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ];
    }

    /**
     * Verify a request signature against the expected HMAC.
     */
    public function verify(string $signature, string $timestamp, string $nonce, string $body): bool
    {
        if ($signature === '' || $timestamp === '' || $nonce === '') {
            return false;
        }

        if (! $this->isValidNonce($nonce)) {
            return false;
        }

        if (! $this->isTimestampFresh($timestamp)) {
            return false;
        }

        $payload = $timestamp.'.'.$nonce.'.'.$body;
        $expected = 'sha256='.hash_hmac('sha256', $payload, $this->secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Check if the timestamp is within the allowed age window (±2 minutes).
     */
    private function isTimestampFresh(string $timestamp): bool
    {
        if (! ctype_digit($timestamp)) {
            return false;
        }

        $age = abs(time() - (int) $timestamp);

        return $age <= self::MAX_TIMESTAMP_AGE;
    }

    /**
     * Validate nonce format: 8-128 chars, alphanumeric + underscore/dash.
     * Matches Go's isValidNonce().
     */
    private function isValidNonce(string $nonce): bool
    {
        $length = strlen($nonce);

        if ($length < 8 || $length > 128) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9_\-]+$/', $nonce) === 1;
    }
}
