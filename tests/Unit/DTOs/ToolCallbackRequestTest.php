<?php

namespace Ginkida\AgentRunner\Tests\Unit\DTOs;

use Ginkida\AgentRunner\DTOs\ToolCallbackRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ToolCallbackRequestTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $request = ToolCallbackRequest::fromArray([
            'session_id' => 'sess-123',
            'tool_name' => 'search',
            'arguments' => ['query' => 'test', 'limit' => 10],
        ]);

        $this->assertSame('sess-123', $request->sessionId);
        $this->assertSame('search', $request->toolName);
        $this->assertSame(['query' => 'test', 'limit' => 10], $request->arguments);
    }

    #[Test]
    public function it_handles_missing_fields(): void
    {
        $request = ToolCallbackRequest::fromArray([]);

        $this->assertSame('', $request->sessionId);
        $this->assertSame('', $request->toolName);
        $this->assertSame([], $request->arguments);
    }

    #[Test]
    public function it_returns_argument_by_key(): void
    {
        $request = ToolCallbackRequest::fromArray([
            'arguments' => ['name' => 'John', 'age' => 30],
        ]);

        $this->assertSame('John', $request->argument('name'));
        $this->assertSame(30, $request->argument('age'));
    }

    #[Test]
    public function it_returns_default_for_missing_argument(): void
    {
        $request = ToolCallbackRequest::fromArray([
            'arguments' => ['name' => 'John'],
        ]);

        $this->assertNull($request->argument('missing'));
        $this->assertSame('default', $request->argument('missing', 'default'));
        $this->assertSame(42, $request->argument('missing', 42));
    }

    #[Test]
    public function it_returns_null_default_when_not_specified(): void
    {
        $request = ToolCallbackRequest::fromArray([
            'arguments' => [],
        ]);

        $this->assertNull($request->argument('anything'));
    }
}
