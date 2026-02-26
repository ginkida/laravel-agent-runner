<?php

namespace Ginkida\AgentRunner\Tests\Unit\DTOs;

use Ginkida\AgentRunner\DTOs\SseEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SseEventTest extends TestCase
{
    #[Test]
    public function it_identifies_text_event(): void
    {
        $event = new SseEvent('text', ['content' => 'Hello']);

        $this->assertTrue($event->isText());
        $this->assertFalse($event->isToolCall());
        $this->assertFalse($event->isToolResult());
        $this->assertFalse($event->isThinking());
        $this->assertFalse($event->isError());
        $this->assertFalse($event->isDone());
    }

    #[Test]
    public function it_identifies_tool_call_event(): void
    {
        $event = new SseEvent('tool_call', ['tool' => 'search', 'args' => ['q' => 'test']]);

        $this->assertTrue($event->isToolCall());
        $this->assertFalse($event->isText());
    }

    #[Test]
    public function it_identifies_tool_result_event(): void
    {
        $event = new SseEvent('tool_result', ['tool' => 'search', 'success' => true]);

        $this->assertTrue($event->isToolResult());
    }

    #[Test]
    public function it_identifies_thinking_event(): void
    {
        $event = new SseEvent('thinking', ['content' => 'Analyzing...']);

        $this->assertTrue($event->isThinking());
    }

    #[Test]
    public function it_identifies_error_event(): void
    {
        $event = new SseEvent('error', ['message' => 'Something went wrong']);

        $this->assertTrue($event->isError());
    }

    #[Test]
    public function it_identifies_done_event(): void
    {
        $event = new SseEvent('done', [
            'status' => 'completed',
            'output' => 'Result',
            'turns' => 3,
            'duration_ms' => 1500,
        ]);

        $this->assertTrue($event->isDone());
    }

    #[Test]
    public function it_returns_text_content(): void
    {
        $event = new SseEvent('text', ['content' => 'Hello world']);

        $this->assertSame('Hello world', $event->textContent());
    }

    #[Test]
    public function it_returns_null_for_missing_text_content(): void
    {
        $event = new SseEvent('text', []);

        $this->assertNull($event->textContent());
    }

    #[Test]
    public function it_returns_tool_name_and_args(): void
    {
        $event = new SseEvent('tool_call', ['tool' => 'search', 'args' => ['q' => 'test']]);

        $this->assertSame('search', $event->toolName());
        $this->assertSame(['q' => 'test'], $event->toolArgs());
    }

    #[Test]
    public function it_returns_null_for_missing_tool_info(): void
    {
        $event = new SseEvent('tool_call', []);

        $this->assertNull($event->toolName());
        $this->assertNull($event->toolArgs());
    }

    #[Test]
    public function it_returns_error_message(): void
    {
        $event = new SseEvent('error', ['message' => 'Something failed']);

        $this->assertSame('Something failed', $event->errorMessage());
    }

    #[Test]
    public function it_returns_null_for_missing_error_message(): void
    {
        $event = new SseEvent('error', []);

        $this->assertNull($event->errorMessage());
    }

    #[Test]
    public function it_returns_done_accessors(): void
    {
        $event = new SseEvent('done', [
            'status' => 'completed',
            'output' => 'Final output',
            'turns' => 5,
            'duration_ms' => 2000,
        ]);

        $this->assertSame('completed', $event->doneStatus());
        $this->assertSame('Final output', $event->doneOutput());
        $this->assertSame(5, $event->doneTurns());
        $this->assertSame(2000, $event->doneDurationMs());
    }

    #[Test]
    public function it_returns_null_for_missing_done_fields(): void
    {
        $event = new SseEvent('done', []);

        $this->assertNull($event->doneStatus());
        $this->assertNull($event->doneOutput());
        $this->assertNull($event->doneTurns());
        $this->assertNull($event->doneDurationMs());
    }

    #[Test]
    public function it_casts_turns_and_duration_to_int(): void
    {
        $event = new SseEvent('done', [
            'turns' => '7',
            'duration_ms' => '3500',
        ]);

        $this->assertSame(7, $event->doneTurns());
        $this->assertSame(3500, $event->doneDurationMs());
    }
}
