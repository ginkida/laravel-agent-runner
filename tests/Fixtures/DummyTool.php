<?php

namespace Ginkida\AgentRunner\Tests\Fixtures;

use Ginkida\AgentRunner\Contracts\RemoteToolContract;
use Ginkida\AgentRunner\DTOs\ToolCallbackRequest;

class DummyTool implements RemoteToolContract
{
    public function name(): string
    {
        return 'dummy_tool';
    }

    public function description(): string
    {
        return 'A dummy tool for testing';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'input' => [
                    'type' => 'string',
                    'description' => 'Test input',
                ],
            ],
            'required' => ['input'],
        ];
    }

    public function handle(ToolCallbackRequest $request): array
    {
        return [
            'success' => true,
            'content' => 'Handled: '.$request->argument('input', ''),
        ];
    }
}
