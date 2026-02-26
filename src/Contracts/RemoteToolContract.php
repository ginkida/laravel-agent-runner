<?php

namespace Ginkida\AgentRunner\Contracts;

use Ginkida\AgentRunner\DTOs\ToolCallbackRequest;

/**
 * Contract for remote tools that Agent Runner can call back to.
 *
 * Implementations are auto-discovered from app/AgentTools/ or can be
 * manually registered via ToolRegistry.
 */
interface RemoteToolContract
{
    /**
     * Unique tool name. Must match [a-zA-Z][a-zA-Z0-9_]*.
     */
    public function name(): string;

    /**
     * Human-readable description of what the tool does.
     */
    public function description(): string;

    /**
     * JSON Schema for the tool's parameters.
     *
     * @return array<string, mixed>
     */
    public function parameters(): array;

    /**
     * Execute the tool and return the result.
     *
     * @return array{success: bool, content?: string, error?: string}
     */
    public function handle(ToolCallbackRequest $request): array;
}
