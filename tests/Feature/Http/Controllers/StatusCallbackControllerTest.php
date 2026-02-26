<?php

namespace Ginkida\AgentRunner\Tests\Feature\Http\Controllers;

use Ginkida\AgentRunner\Client\HmacSigner;
use Ginkida\AgentRunner\Events\AgentSessionCancelled;
use Ginkida\AgentRunner\Events\AgentSessionCompleted;
use Ginkida\AgentRunner\Events\AgentSessionCreated;
use Ginkida\AgentRunner\Events\AgentSessionFailed;
use Ginkida\AgentRunner\Events\AgentSessionRunning;
use Ginkida\AgentRunner\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class StatusCallbackControllerTest extends TestCase
{
    private HmacSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = new HmacSigner('test-secret');
    }

    public function test_created_status_dispatches_event(): void
    {
        Event::fake();

        $this->sendStatusCallback('sess-123', 'created');

        Event::assertDispatched(AgentSessionCreated::class, function ($event) {
            return $event->payload->sessionId === 'sess-123'
                && $event->payload->isCreated();
        });
    }

    public function test_running_status_dispatches_event(): void
    {
        Event::fake();

        $this->sendStatusCallback('sess-123', 'running');

        Event::assertDispatched(AgentSessionRunning::class, function ($event) {
            return $event->payload->sessionId === 'sess-123'
                && $event->payload->isRunning();
        });
    }

    public function test_completed_status_dispatches_event(): void
    {
        Event::fake();

        $this->sendStatusCallback('sess-123', 'completed', [
            'output' => 'Done!',
            'turns' => 5,
            'duration_ms' => 2000,
        ]);

        Event::assertDispatched(AgentSessionCompleted::class, function ($event) {
            return $event->payload->sessionId === 'sess-123'
                && $event->payload->isCompleted()
                && $event->payload->output === 'Done!'
                && $event->payload->turns === 5
                && $event->payload->durationMs === 2000;
        });
    }

    public function test_failed_status_dispatches_event(): void
    {
        Event::fake();

        $this->sendStatusCallback('sess-123', 'failed', [
            'error' => 'LLM timeout',
        ]);

        Event::assertDispatched(AgentSessionFailed::class, function ($event) {
            return $event->payload->sessionId === 'sess-123'
                && $event->payload->isFailed()
                && $event->payload->error === 'LLM timeout';
        });
    }

    public function test_cancelled_status_dispatches_event(): void
    {
        Event::fake();

        $this->sendStatusCallback('sess-123', 'cancelled');

        Event::assertDispatched(AgentSessionCancelled::class, function ($event) {
            return $event->payload->sessionId === 'sess-123'
                && $event->payload->isCancelled();
        });
    }

    public function test_unknown_status_does_not_dispatch_event(): void
    {
        Event::fake();

        $this->sendStatusCallback('sess-123', 'unknown_status');

        Event::assertNotDispatched(AgentSessionCreated::class);
        Event::assertNotDispatched(AgentSessionRunning::class);
        Event::assertNotDispatched(AgentSessionCompleted::class);
        Event::assertNotDispatched(AgentSessionFailed::class);
        Event::assertNotDispatched(AgentSessionCancelled::class);
    }

    public function test_returns_ok_response(): void
    {
        Event::fake();

        $response = $this->sendStatusCallback('sess-123', 'completed');

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    private function sendStatusCallback(string $sessionId, string $status, array $extra = [])
    {
        $payload = array_merge([
            'client_id' => 'test-client',
            'status' => $status,
        ], $extra);

        $body = json_encode($payload);
        $hmac = $this->signer->sign($body);

        return $this->postJson(
            "/api/agent-runner/sessions/{$sessionId}/status",
            $payload,
            [
                'X-Signature' => $hmac['signature'],
                'X-Timestamp' => $hmac['timestamp'],
                'X-Nonce' => $hmac['nonce'],
            ],
        );
    }
}
