<?php

namespace Ginkida\AgentRunner\DTOs;

/**
 * Represents an incoming tool callback request from Agent Runner.
 *
 * Matches the JSON payload: {session_id, tool_name, arguments}
 */
final readonly class ToolCallbackRequest
{
    public function __construct(
        public string $sessionId,
        public string $toolName,
        public array $arguments,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            sessionId: $data['session_id'] ?? '',
            toolName: $data['tool_name'] ?? '',
            arguments: $data['arguments'] ?? [],
        );
    }

    public function argument(string $key, mixed $default = null): mixed
    {
        return $this->arguments[$key] ?? $default;
    }
}
