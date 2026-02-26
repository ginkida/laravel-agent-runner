<?php

namespace Ginkida\AgentRunner\Builder;

use Closure;
use Ginkida\AgentRunner\Client\AgentRunnerClient;
use Ginkida\AgentRunner\Client\SseStream;
use Ginkida\AgentRunner\DTOs\SseEvent;
use Ginkida\AgentRunner\Tools\ToolRegistry;

/**
 * Fluent builder for configuring and executing agent sessions.
 *
 * Three execution modes:
 * - run()      — sync: create → message → stream → return result
 * - start()    — returns {session_id, stream} for manual SSE consumption
 * - dispatch() — fire-and-forget, rely on status callbacks
 */
class AgentBuilder
{
    private string $name = '';

    private ?string $model = null;

    private ?string $systemPrompt = null;

    private ?int $maxTurns = null;

    private ?int $maxTokens = null;

    private ?float $temperature = null;

    /** @var string[] */
    private array $builtinTools = [];

    /** @var string[] */
    private array $remoteToolNames = [];

    private bool $allRemoteTools = false;

    private ?string $sessionId = null;

    private ?string $workDir = null;

    private ?array $callbackOverride = null;

    /** @var array<string, Closure> */
    private array $callbacks = [];

    public function __construct(
        private readonly AgentRunnerClient $client,
        private readonly ToolRegistry $registry,
        private readonly array $defaults,
        private readonly ?array $callbackConfig = null,
        private readonly int $sseTimeout = 600,
    ) {}

    /**
     * Set the agent name.
     */
    public function agent(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the LLM model.
     */
    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the system prompt.
     */
    public function systemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    /**
     * Set max turns for the agent loop.
     */
    public function maxTurns(int $maxTurns): static
    {
        $this->maxTurns = $maxTurns;

        return $this;
    }

    /**
     * Set max tokens for LLM responses.
     */
    public function maxTokens(int $maxTokens): static
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * Set the temperature.
     */
    public function temperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Specify built-in tools (read_file, write_file, bash, etc.).
     *
     * @param  string[]  $tools
     */
    public function tools(array $tools): static
    {
        $this->builtinTools = array_merge($this->builtinTools, $tools);

        return $this;
    }

    /**
     * Specify remote tools by name.
     *
     * @param  string[]  $names
     */
    public function remoteTools(array $names): static
    {
        $this->remoteToolNames = array_merge($this->remoteToolNames, $names);

        return $this;
    }

    /**
     * Include all registered remote tools.
     */
    public function withAllRemoteTools(): static
    {
        $this->allRemoteTools = true;

        return $this;
    }

    /**
     * Set a custom session ID (must be 1-128 alphanumeric/dash/underscore).
     */
    public function sessionId(string $id): static
    {
        $this->sessionId = $id;

        return $this;
    }

    /**
     * Set the working directory for built-in tools.
     */
    public function workDir(string $path): static
    {
        $this->workDir = $path;

        return $this;
    }

    /**
     * Override the callback URL for this session.
     */
    public function callback(string $baseUrl, ?int $timeout = null): static
    {
        $this->callbackOverride = array_filter([
            'base_url' => $baseUrl,
            'timeout_sec' => $timeout,
        ], fn ($v) => $v !== null);

        return $this;
    }

    /**
     * Register a callback for text streaming events.
     */
    public function onText(Closure $callback): static
    {
        $this->callbacks['text'] = $callback;

        return $this;
    }

    /**
     * Register a callback for tool call events.
     */
    public function onToolCall(Closure $callback): static
    {
        $this->callbacks['tool_call'] = $callback;

        return $this;
    }

    /**
     * Register a callback for tool result events.
     */
    public function onToolResult(Closure $callback): static
    {
        $this->callbacks['tool_result'] = $callback;

        return $this;
    }

    /**
     * Register a callback for thinking events.
     */
    public function onThinking(Closure $callback): static
    {
        $this->callbacks['thinking'] = $callback;

        return $this;
    }

    /**
     * Register a callback for error events.
     */
    public function onError(Closure $callback): static
    {
        $this->callbacks['error'] = $callback;

        return $this;
    }

    /**
     * Register a callback for the done event.
     */
    public function onDone(Closure $callback): static
    {
        $this->callbacks['done'] = $callback;

        return $this;
    }

    /**
     * Sync execution: create session → send message → stream → return done event.
     */
    public function run(string $message): ?SseEvent
    {
        $session = $this->createSession();
        $sessionId = $session['session_id'];

        $this->client->sendMessage($sessionId, $message);

        $stream = $this->client->stream($sessionId, $this->sseTimeout);

        return $stream->listen($this->callbacks);
    }

    /**
     * Start session and return session ID + stream for manual consumption.
     *
     * @return array{session_id: string, stream: SseStream}
     */
    public function start(string $message): array
    {
        $session = $this->createSession();
        $sessionId = $session['session_id'];

        $this->client->sendMessage($sessionId, $message);

        return [
            'session_id' => $sessionId,
            'stream' => $this->client->stream($sessionId, $this->sseTimeout),
        ];
    }

    /**
     * Fire-and-forget: create session → send message → return session ID.
     * Results arrive via status callbacks.
     */
    public function dispatch(string $message): string
    {
        $session = $this->createSession();
        $sessionId = $session['session_id'];

        $this->client->sendMessage($sessionId, $message);

        return $sessionId;
    }

    /**
     * Build the AgentDefinition payload matching Go's session.AgentDefinition struct.
     */
    private function buildAgentDefinition(): array
    {
        $agent = [
            'name' => $this->name,
            'model' => $this->model ?? $this->defaults['model'] ?? 'gpt-4o-mini',
            'system_prompt' => $this->systemPrompt ?? '',
            'max_turns' => $this->maxTurns ?? $this->defaults['max_turns'] ?? 30,
            'tools' => $this->buildToolsDefinition(),
        ];

        if ($this->maxTokens !== null || ! empty($this->defaults['max_tokens'])) {
            $agent['max_tokens'] = $this->maxTokens ?? $this->defaults['max_tokens'];
        }

        $defaultTemp = $this->defaults['temperature'] ?? null;
        if ($this->temperature !== null || $defaultTemp !== null) {
            $agent['temperature'] = $this->temperature ?? $defaultTemp;
        }

        return $agent;
    }

    /**
     * Build the ToolsDefinition matching Go's session.ToolsDefinition struct.
     */
    private function buildToolsDefinition(): array
    {
        // null = all tools, empty array = no tools, non-empty = specific tools
        $remoteNames = $this->allRemoteTools
            ? null
            : ($this->remoteToolNames ?: []);

        return [
            'builtin' => array_values(array_unique($this->builtinTools)),
            'remote' => $this->registry->definitions($remoteNames),
        ];
    }

    /**
     * Create the session on Agent Runner.
     */
    private function createSession(): array
    {
        $callback = $this->callbackOverride ?? $this->callbackConfig;

        return $this->client->createSession(
            agentDefinition: $this->buildAgentDefinition(),
            callback: $callback,
            sessionId: $this->sessionId,
            workDir: $this->workDir,
        );
    }
}
