<?php

namespace Ginkida\AgentRunner\Exceptions;

class SessionNotFoundException extends AgentRunnerException
{
    public static function withId(string $sessionId): static
    {
        return new static("Session not found: {$sessionId}");
    }
}
