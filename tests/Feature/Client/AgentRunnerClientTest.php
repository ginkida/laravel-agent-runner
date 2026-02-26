<?php

namespace Ginkida\AgentRunner\Tests\Feature\Client;

use Ginkida\AgentRunner\Client\AgentRunnerClient;
use Ginkida\AgentRunner\Exceptions\AgentRunnerException;
use Ginkida\AgentRunner\Exceptions\SessionNotFoundException;
use Ginkida\AgentRunner\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class AgentRunnerClientTest extends TestCase
{
    private AgentRunnerClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new AgentRunnerClient(
            baseUrl: 'http://localhost:8090',
            clientId: 'test-client',
            hmacSecret: 'test-secret',
        );
    }

    public function test_create_session_sends_correct_payload(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'created',
            ]),
        ]);

        $result = $this->client->createSession(
            agentDefinition: ['name' => 'test-agent', 'model' => 'gpt-4o-mini'],
            callback: ['base_url' => 'http://localhost:8000/api/agent-runner'],
            sessionId: 'custom-id',
            workDir: '/tmp/work',
        );

        $this->assertSame('sess-123', $result['session_id']);

        Http::assertSent(function ($request) {
            $body = $request->body();
            $data = json_decode($body, true);

            return $request->method() === 'POST'
                && str_contains($request->url(), '/v1/sessions')
                && $data['agent']['name'] === 'test-agent'
                && $data['callback']['base_url'] === 'http://localhost:8000/api/agent-runner'
                && $data['session_id'] === 'custom-id'
                && $data['work_dir'] === '/tmp/work';
        });
    }

    public function test_create_session_includes_hmac_headers(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'created',
            ]),
        ]);

        $this->client->createSession(['name' => 'test-agent']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Signature')
                && $request->hasHeader('X-Timestamp')
                && $request->hasHeader('X-Nonce')
                && $request->hasHeader('X-Client-ID')
                && str_starts_with($request->header('X-Signature')[0], 'sha256=');
        });
    }

    public function test_create_session_without_hmac_secret(): void
    {
        $client = new AgentRunnerClient(
            baseUrl: 'http://localhost:8090',
            clientId: 'test-client',
            hmacSecret: '',
        );

        Http::fake([
            'localhost:8090/v1/sessions' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'created',
            ]),
        ]);

        $client->createSession(['name' => 'test-agent']);

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('X-Signature')
                && ! $request->hasHeader('X-Timestamp')
                && ! $request->hasHeader('X-Nonce');
        });
    }

    public function test_get_session_returns_data(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions/sess-123' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'running',
            ]),
        ]);

        $result = $this->client->getSession('sess-123');

        $this->assertSame('sess-123', $result['session_id']);
        $this->assertSame('running', $result['status']);
    }

    public function test_get_session_throws_on_404(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions/missing' => Http::response(['error' => 'not found'], 404),
        ]);

        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionMessage('Session not found: missing');

        $this->client->getSession('missing');
    }

    public function test_delete_session_returns_data(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions/sess-123' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'cancelled',
            ]),
        ]);

        $result = $this->client->deleteSession('sess-123');

        $this->assertSame('cancelled', $result['status']);
    }

    public function test_delete_session_throws_on_404(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions/missing' => Http::response(['error' => 'not found'], 404),
        ]);

        $this->expectException(SessionNotFoundException::class);

        $this->client->deleteSession('missing');
    }

    public function test_send_message_sends_correct_payload(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions/sess-123/messages' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'running',
                'tools_registered' => [],
            ]),
        ]);

        $result = $this->client->sendMessage('sess-123', 'Hello, agent!');

        $this->assertSame('sess-123', $result['session_id']);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $request->method() === 'POST'
                && str_contains($request->url(), '/v1/sessions/sess-123/messages')
                && $body['message'] === 'Hello, agent!';
        });
    }

    public function test_api_error_throws_exception(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions' => Http::response([
                'error' => 'Internal server error',
            ], 500),
        ]);

        $this->expectException(AgentRunnerException::class);
        $this->expectExceptionMessage('Agent Runner API error (500): Internal server error');

        $this->client->createSession(['name' => 'test-agent']);
    }

    public function test_body_then_sign_pattern(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'created',
            ]),
        ]);

        $this->client->createSession(['name' => 'test', 'model' => 'gpt-4o-mini']);

        Http::assertSent(function ($request) {
            $signature = $request->header('X-Signature')[0];
            $timestamp = $request->header('X-Timestamp')[0];
            $nonce = $request->header('X-Nonce')[0];
            $body = $request->body();

            // Verify the signature matches the raw body bytes
            $payload = $timestamp.'.'.$nonce.'.'.$body;
            $expected = 'sha256='.hash_hmac('sha256', $payload, 'test-secret');

            return $signature === $expected;
        });
    }

    public function test_create_session_omits_null_optional_fields(): void
    {
        Http::fake([
            'localhost:8090/v1/sessions' => Http::response([
                'session_id' => 'sess-123',
                'status' => 'created',
            ]),
        ]);

        $this->client->createSession(['name' => 'test']);

        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);

            return ! array_key_exists('callback', $data)
                && ! array_key_exists('session_id', $data)
                && ! array_key_exists('work_dir', $data);
        });
    }
}
