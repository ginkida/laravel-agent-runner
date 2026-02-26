<?php

namespace Ginkida\AgentRunner\Tools;

use Ginkida\AgentRunner\Contracts\RemoteToolContract;

/**
 * In-memory registry for remote tools.
 */
class ToolRegistry
{
    /** @var array<string, RemoteToolContract> */
    private array $tools = [];

    /**
     * Register a tool instance.
     */
    public function register(RemoteToolContract $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Get a tool by name.
     */
    public function get(string $name): ?RemoteToolContract
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Check if a tool is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Get all registered tool names.
     *
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Get all registered tools.
     *
     * @return array<string, RemoteToolContract>
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Build remote tool definitions for the Agent Runner API payload.
     *
     * @param  string[]|null  $names  Only include these tools. Null = all.
     * @return array<int, array{name: string, description: string, parameters: array}>
     */
    public function definitions(?array $names = null): array
    {
        $tools = $names !== null
            ? array_intersect_key($this->tools, array_flip($names))
            : $this->tools;

        return array_values(array_map(
            fn (RemoteToolContract $tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => $tool->parameters(),
            ],
            $tools,
        ));
    }
}
