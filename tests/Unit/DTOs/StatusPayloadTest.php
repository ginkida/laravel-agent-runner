<?php

namespace Ginkida\AgentRunner\Tests\Unit\DTOs;

use Ginkida\AgentRunner\DTOs\StatusPayload;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatusPayloadTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $payload = StatusPayload::fromArray([
            'session_id' => 'sess-123',
            'client_id' => 'laravel',
            'status' => 'completed',
            'error' => null,
            'output' => 'Done',
            'turns' => 3,
            'duration_ms' => 1500,
        ]);

        $this->assertSame('sess-123', $payload->sessionId);
        $this->assertSame('laravel', $payload->clientId);
        $this->assertSame('completed', $payload->status);
        $this->assertNull($payload->error);
        $this->assertSame('Done', $payload->output);
        $this->assertSame(3, $payload->turns);
        $this->assertSame(1500, $payload->durationMs);
    }

    #[Test]
    public function it_handles_missing_optional_fields(): void
    {
        $payload = StatusPayload::fromArray([
            'session_id' => 'sess-123',
            'client_id' => 'laravel',
            'status' => 'running',
        ]);

        $this->assertNull($payload->error);
        $this->assertNull($payload->output);
        $this->assertNull($payload->turns);
        $this->assertNull($payload->durationMs);
    }

    #[Test]
    public function it_handles_empty_array(): void
    {
        $payload = StatusPayload::fromArray([]);

        $this->assertSame('', $payload->sessionId);
        $this->assertSame('', $payload->clientId);
        $this->assertSame('', $payload->status);
    }

    #[Test]
    public function it_casts_turns_and_duration_to_int(): void
    {
        $payload = StatusPayload::fromArray([
            'session_id' => 'sess-123',
            'client_id' => 'laravel',
            'status' => 'completed',
            'turns' => '5',
            'duration_ms' => '2000',
        ]);

        $this->assertSame(5, $payload->turns);
        $this->assertSame(2000, $payload->durationMs);
    }

    #[Test]
    public function it_checks_created_status(): void
    {
        $payload = StatusPayload::fromArray(['status' => 'created']);

        $this->assertTrue($payload->isCreated());
        $this->assertFalse($payload->isRunning());
        $this->assertFalse($payload->isCompleted());
        $this->assertFalse($payload->isFailed());
        $this->assertFalse($payload->isCancelled());
        $this->assertFalse($payload->isTerminal());
    }

    #[Test]
    public function it_checks_running_status(): void
    {
        $payload = StatusPayload::fromArray(['status' => 'running']);

        $this->assertTrue($payload->isRunning());
        $this->assertFalse($payload->isTerminal());
    }

    #[Test]
    public function it_checks_completed_status(): void
    {
        $payload = StatusPayload::fromArray(['status' => 'completed']);

        $this->assertTrue($payload->isCompleted());
        $this->assertTrue($payload->isTerminal());
    }

    #[Test]
    public function it_checks_failed_status(): void
    {
        $payload = StatusPayload::fromArray(['status' => 'failed']);

        $this->assertTrue($payload->isFailed());
        $this->assertTrue($payload->isTerminal());
    }

    #[Test]
    public function it_checks_cancelled_status(): void
    {
        $payload = StatusPayload::fromArray(['status' => 'cancelled']);

        $this->assertTrue($payload->isCancelled());
        $this->assertTrue($payload->isTerminal());
    }

    #[Test]
    public function it_stores_error_for_failed_status(): void
    {
        $payload = StatusPayload::fromArray([
            'status' => 'failed',
            'error' => 'Something went wrong',
        ]);

        $this->assertTrue($payload->isFailed());
        $this->assertSame('Something went wrong', $payload->error);
    }
}
