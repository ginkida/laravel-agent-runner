<?php

namespace Ginkida\AgentRunner\Tests\Feature\Http\Controllers;

use Ginkida\AgentRunner\Client\HmacSigner;
use Ginkida\AgentRunner\Contracts\RemoteToolContract;
use Ginkida\AgentRunner\DTOs\ToolCallbackRequest;
use Ginkida\AgentRunner\Tests\Fixtures\DummyTool;
use Ginkida\AgentRunner\Tests\TestCase;
use Ginkida\AgentRunner\Tools\ToolRegistry;

class ToolCallbackControllerTest extends TestCase
{
    private HmacSigner $signer;

    private ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = new HmacSigner('test-secret');
        $this->registry = $this->app->make(ToolRegistry::class);
    }

    public function test_tool_found_and_executed(): void
    {
        $this->registry->register(new DummyTool);

        $body = json_encode([
            'session_id' => 'sess-123',
            'tool_name' => 'dummy_tool',
            'arguments' => ['input' => 'hello'],
        ]);

        $hmac = $this->signer->sign($body);

        $response = $this->postJson(
            '/api/agent-runner/tools/dummy_tool',
            json_decode($body, true),
            [
                'X-Signature' => $hmac['signature'],
                'X-Timestamp' => $hmac['timestamp'],
                'X-Nonce' => $hmac['nonce'],
            ],
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'content' => 'Handled: hello',
        ]);
    }

    public function test_unknown_tool_returns_404(): void
    {
        $body = json_encode([
            'session_id' => 'sess-123',
            'tool_name' => 'nonexistent',
            'arguments' => [],
        ]);

        $hmac = $this->signer->sign($body);

        $response = $this->postJson(
            '/api/agent-runner/tools/nonexistent',
            json_decode($body, true),
            [
                'X-Signature' => $hmac['signature'],
                'X-Timestamp' => $hmac['timestamp'],
                'X-Nonce' => $hmac['nonce'],
            ],
        );

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
            'error' => 'Unknown tool: nonexistent',
        ]);
    }

    public function test_tool_exception_returns_500(): void
    {
        $failingTool = new class implements RemoteToolContract
        {
            public function name(): string
            {
                return 'failing_tool';
            }

            public function description(): string
            {
                return 'A tool that fails';
            }

            public function parameters(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function handle(ToolCallbackRequest $request): array
            {
                throw new \RuntimeException('Tool exploded');
            }
        };

        $this->registry->register($failingTool);

        $body = json_encode([
            'session_id' => 'sess-123',
            'tool_name' => 'failing_tool',
            'arguments' => [],
        ]);

        $hmac = $this->signer->sign($body);

        $response = $this->postJson(
            '/api/agent-runner/tools/failing_tool',
            json_decode($body, true),
            [
                'X-Signature' => $hmac['signature'],
                'X-Timestamp' => $hmac['timestamp'],
                'X-Nonce' => $hmac['nonce'],
            ],
        );

        $response->assertStatus(500);
        $response->assertJsonStructure(['success', 'error']);
    }

    public function test_request_without_hmac_is_rejected(): void
    {
        $this->registry->register(new DummyTool);

        $response = $this->postJson('/api/agent-runner/tools/dummy_tool', [
            'session_id' => 'sess-123',
            'tool_name' => 'dummy_tool',
            'arguments' => [],
        ]);

        $response->assertUnauthorized();
    }
}
