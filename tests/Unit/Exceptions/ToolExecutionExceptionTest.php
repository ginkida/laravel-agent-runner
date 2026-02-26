<?php

namespace Ginkida\AgentRunner\Tests\Unit\Exceptions;

use Ginkida\AgentRunner\Exceptions\AgentRunnerException;
use Ginkida\AgentRunner\Exceptions\ToolExecutionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ToolExecutionExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_base_exception(): void
    {
        $exception = new ToolExecutionException('my_tool', 'Something broke');

        $this->assertInstanceOf(AgentRunnerException::class, $exception);
    }

    #[Test]
    public function it_stores_tool_name(): void
    {
        $exception = new ToolExecutionException('search_tool', 'Connection failed');

        $this->assertSame('search_tool', $exception->toolName);
    }

    #[Test]
    public function it_formats_message_with_tool_name(): void
    {
        $exception = new ToolExecutionException('my_tool', 'timeout');

        $this->assertSame("Tool 'my_tool' execution failed: timeout", $exception->getMessage());
    }

    #[Test]
    public function it_preserves_previous_exception(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new ToolExecutionException('my_tool', 'wrapped', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
