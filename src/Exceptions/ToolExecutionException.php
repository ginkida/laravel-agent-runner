<?php

namespace Ginkida\AgentRunner\Exceptions;

class ToolExecutionException extends AgentRunnerException
{
    public function __construct(
        public readonly string $toolName,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("Tool '{$toolName}' execution failed: {$message}", $code, $previous);
    }
}
