<?php

namespace Ginkida\AgentRunner\Exceptions;

class SessionNotFoundException extends AgentRunnerException
{
    public static function withId(string $sessionId): self
    {
        return new self("Session not found: {$sessionId}");
    }
}
