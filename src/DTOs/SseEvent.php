<?php

namespace Ginkida\AgentRunner\DTOs;

/**
 * Represents a Server-Sent Event from Agent Runner.
 *
 * Event types: text, tool_call, tool_result, thinking, error, done
 */
final readonly class SseEvent
{
    public function __construct(
        public string $type,
        public array $data,
    ) {}

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isToolCall(): bool
    {
        return $this->type === 'tool_call';
    }

    public function isToolResult(): bool
    {
        return $this->type === 'tool_result';
    }

    public function isThinking(): bool
    {
        return $this->type === 'thinking';
    }

    public function isError(): bool
    {
        return $this->type === 'error';
    }

    public function isDone(): bool
    {
        return $this->type === 'done';
    }

    public function textContent(): ?string
    {
        return $this->data['content'] ?? null;
    }

    public function toolName(): ?string
    {
        return $this->data['tool'] ?? null;
    }

    public function toolArgs(): ?array
    {
        return $this->data['args'] ?? null;
    }

    public function errorMessage(): ?string
    {
        return $this->data['message'] ?? null;
    }

    public function doneStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function doneOutput(): ?string
    {
        return $this->data['output'] ?? null;
    }

    public function doneTurns(): ?int
    {
        return isset($this->data['turns']) ? (int) $this->data['turns'] : null;
    }

    public function doneDurationMs(): ?int
    {
        return isset($this->data['duration_ms']) ? (int) $this->data['duration_ms'] : null;
    }
}
