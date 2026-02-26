<?php

namespace Ginkida\AgentRunner\DTOs;

/**
 * Represents an incoming status callback payload from Agent Runner.
 *
 * Matches Go's session.StatusPayload struct.
 */
final readonly class StatusPayload
{
    public function __construct(
        public string $sessionId,
        public string $clientId,
        public string $status,
        public ?string $error = null,
        public ?string $output = null,
        public ?int $turns = null,
        public ?int $durationMs = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            sessionId: $data['session_id'] ?? '',
            clientId: $data['client_id'] ?? '',
            status: $data['status'] ?? '',
            error: $data['error'] ?? null,
            output: $data['output'] ?? null,
            turns: isset($data['turns']) ? (int) $data['turns'] : null,
            durationMs: isset($data['duration_ms']) ? (int) $data['duration_ms'] : null,
        );
    }

    public function isCreated(): bool
    {
        return $this->status === 'created';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }
}
