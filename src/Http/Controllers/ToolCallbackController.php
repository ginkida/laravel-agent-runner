<?php

namespace Ginkida\AgentRunner\Http\Controllers;

use Ginkida\AgentRunner\DTOs\ToolCallbackRequest;
use Ginkida\AgentRunner\Exceptions\ToolExecutionException;
use Ginkida\AgentRunner\Tools\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles incoming tool callback requests from Agent Runner.
 *
 * Route: POST /tools/{toolName}
 * Payload: {session_id, tool_name, arguments}
 * Response: {success: bool, content?: string, error?: string}
 */
class ToolCallbackController
{
    public function __construct(
        private readonly ToolRegistry $registry,
    ) {}

    public function __invoke(Request $request, string $toolName): JsonResponse
    {
        $tool = $this->registry->get($toolName);

        if ($tool === null) {
            return response()->json([
                'success' => false,
                'error' => "Unknown tool: {$toolName}",
            ], 404);
        }

        $callbackRequest = ToolCallbackRequest::fromArray($request->json()->all());

        try {
            $result = $tool->handle($callbackRequest);

            return response()->json($result);
        } catch (\Throwable $e) {
            throw new ToolExecutionException($toolName, $e->getMessage(), 0, $e);
        }
    }
}
