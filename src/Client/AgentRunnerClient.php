<?php

namespace Ginkida\AgentRunner\Client;

use Ginkida\AgentRunner\Exceptions\AgentRunnerException;
use Ginkida\AgentRunner\Exceptions\SessionNotFoundException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for all 5 Agent Runner endpoints.
 *
 * Uses body-then-sign pattern: JSON-encode body first, sign it, send via
 * withBody() to ensure HMAC matches the exact wire bytes.
 */
class AgentRunnerClient
{
    private readonly ?HmacSigner $signer;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        string $hmacSecret = '',
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 5,
    ) {
        $this->signer = $hmacSecret !== '' ? new HmacSigner($hmacSecret) : null;
    }

    /**
     * POST /v1/sessions — Create a new session.
     *
     * @return array{session_id: string, status: string}
     */
    public function createSession(array $agentDefinition, ?array $callback = null, ?string $sessionId = null, ?string $workDir = null): array
    {
        $body = ['agent' => $agentDefinition];

        if ($callback !== null) {
            $body['callback'] = $callback;
        }
        if ($sessionId !== null) {
            $body['session_id'] = $sessionId;
        }
        if ($workDir !== null) {
            $body['work_dir'] = $workDir;
        }

        return $this->sendJson('POST', '/v1/sessions', $body);
    }

    /**
     * GET /v1/sessions/{id} — Get session info.
     */
    public function getSession(string $sessionId): array
    {
        $response = $this->signedRequest()
            ->get($this->url("/v1/sessions/{$sessionId}"));

        if ($response->status() === 404) {
            throw SessionNotFoundException::withId($sessionId);
        }

        $this->ensureSuccessful($response);

        return $response->json();
    }

    /**
     * DELETE /v1/sessions/{id} — Cancel and delete session.
     */
    public function deleteSession(string $sessionId): array
    {
        $response = $this->signedRequest()
            ->delete($this->url("/v1/sessions/{$sessionId}"));

        if ($response->status() === 404) {
            throw SessionNotFoundException::withId($sessionId);
        }

        $this->ensureSuccessful($response);

        return $response->json();
    }

    /**
     * POST /v1/sessions/{id}/messages — Send message to session (starts agent).
     *
     * @return array{session_id: string, status: string, tools_registered: array}
     */
    public function sendMessage(string $sessionId, string $message): array
    {
        return $this->sendJson('POST', "/v1/sessions/{$sessionId}/messages", [
            'message' => $message,
        ]);
    }

    /**
     * GET /v1/sessions/{id}/stream — Open SSE stream.
     */
    public function stream(string $sessionId, int $sseTimeout = 600): SseStream
    {
        return new SseStream(
            url: $this->url("/v1/sessions/{$sessionId}/stream"),
            clientId: $this->clientId,
            signer: $this->signer,
            timeout: $sseTimeout,
        );
    }

    /**
     * Send a JSON request using body-then-sign pattern.
     *
     * JSON-encodes the body first, signs the raw bytes, then sends with
     * withBody() to ensure the HMAC matches the exact wire representation.
     */
    private function sendJson(string $method, string $path, array $data): array
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $request = $this->request();

        if ($this->signer !== null) {
            $hmac = $this->signer->sign($json);
            $request = $request->withHeaders([
                'X-Signature' => $hmac['signature'],
                'X-Timestamp' => $hmac['timestamp'],
                'X-Nonce' => $hmac['nonce'],
            ]);
        }

        $response = $request
            ->withBody($json, 'application/json')
            ->send($method, $this->url($path));

        $this->ensureSuccessful($response);

        return $response->json();
    }

    /**
     * Create a base pending request with common headers.
     */
    private function request(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders([
                'X-Client-ID' => $this->clientId,
            ])
            ->acceptJson();
    }

    /**
     * Create a request with HMAC headers for bodyless requests (GET, DELETE).
     *
     * Signs an empty body since Go's middleware reads the body (empty for GET/DELETE)
     * and computes the HMAC with payload = "{timestamp}.{nonce}.".
     */
    private function signedRequest(): PendingRequest
    {
        $request = $this->request();

        if ($this->signer !== null) {
            $hmac = $this->signer->sign('');
            $request = $request->withHeaders([
                'X-Signature' => $hmac['signature'],
                'X-Timestamp' => $hmac['timestamp'],
                'X-Nonce' => $hmac['nonce'],
            ]);
        }

        return $request;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . $path;
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->json();
        $error = $body['error'] ?? 'Unknown error';

        throw new AgentRunnerException(
            "Agent Runner API error ({$response->status()}): {$error}",
            $response->status(),
        );
    }
}
