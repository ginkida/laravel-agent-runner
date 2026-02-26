<?php

namespace Ginkida\AgentRunner\Http\Middleware;

use Closure;
use Ginkida\AgentRunner\Client\HmacSigner;
use Ginkida\AgentRunner\Exceptions\HmacVerificationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that verifies HMAC-SHA256 signatures on incoming Agent Runner callbacks.
 *
 * Validates X-Signature, X-Timestamp, X-Nonce headers against the request body.
 * Includes nonce replay protection matching Go's replayNonceStore behavior.
 */
class VerifyHmacSignature
{
    /** Nonce cache TTL — matches Go's 2 * maxTimestampAge (4 minutes). */
    private const NONCE_TTL_SECONDS = 240;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('agent-runner.hmac_secret', '');

        // If no secret configured, skip verification
        if ($secret === '') {
            return $next($request);
        }

        $signature = $request->header('X-Signature', '');
        $timestamp = $request->header('X-Timestamp', '');
        $nonce = $request->header('X-Nonce', '');

        if ($signature === '' || $timestamp === '' || $nonce === '') {
            throw HmacVerificationException::missingHeaders();
        }

        $signer = new HmacSigner($secret);
        $body = $request->getContent();

        if (! $signer->verify($signature, $timestamp, $nonce, $body)) {
            throw HmacVerificationException::invalidSignature();
        }

        // Replay protection: reject previously seen nonces.
        // Cache::add() is atomic — returns false if key already exists.
        $cacheKey = 'agent-runner:nonce:'.$nonce;

        if (! Cache::add($cacheKey, true, self::NONCE_TTL_SECONDS)) {
            throw HmacVerificationException::invalidSignature();
        }

        return $next($request);
    }
}
