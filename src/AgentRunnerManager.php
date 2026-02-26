<?php

namespace Ginkida\AgentRunner;

use Ginkida\AgentRunner\Builder\AgentBuilder;
use Ginkida\AgentRunner\Client\AgentRunnerClient;
use Ginkida\AgentRunner\Client\SseStream;
use Ginkida\AgentRunner\Tools\ToolRegistry;

/**
 * Central manager that proxies the low-level client and creates fluent builders.
 */
class AgentRunnerManager
{
    public function __construct(
        private readonly AgentRunnerClient $client,
        private readonly ToolRegistry $registry,
        private readonly array $defaults,
        private readonly ?array $callbackConfig,
        private readonly int $sseTimeout = 600,
    ) {}

    /**
     * Create a new fluent agent builder.
     */
    public function agent(string $name): AgentBuilder
    {
        return (new AgentBuilder(
            client: $this->client,
            registry: $this->registry,
            defaults: $this->defaults,
            callbackConfig: $this->callbackConfig,
            sseTimeout: $this->sseTimeout,
        ))->agent($name);
    }

    /**
     * Create a session directly (low-level).
     */
    public function createSession(array $agentDefinition, ?array $callback = null, ?string $sessionId = null, ?string $workDir = null): array
    {
        return $this->client->createSession($agentDefinition, $callback, $sessionId, $workDir);
    }

    /**
     * Get session info.
     */
    public function getSession(string $sessionId): array
    {
        return $this->client->getSession($sessionId);
    }

    /**
     * Delete/cancel a session.
     */
    public function deleteSession(string $sessionId): array
    {
        return $this->client->deleteSession($sessionId);
    }

    /**
     * Send a message to a session (low-level).
     */
    public function sendMessage(string $sessionId, string $message): array
    {
        return $this->client->sendMessage($sessionId, $message);
    }

    /**
     * Open an SSE stream for a session.
     */
    public function stream(string $sessionId): SseStream
    {
        return $this->client->stream($sessionId, $this->sseTimeout);
    }

    /**
     * Get the tool registry.
     */
    public function tools(): ToolRegistry
    {
        return $this->registry;
    }

    /**
     * Get the underlying HTTP client.
     */
    public function client(): AgentRunnerClient
    {
        return $this->client;
    }
}
