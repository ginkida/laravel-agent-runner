<?php

namespace Ginkida\AgentRunner\Http\Controllers;

use Ginkida\AgentRunner\DTOs\StatusPayload;
use Ginkida\AgentRunner\Events\AgentSessionCancelled;
use Ginkida\AgentRunner\Events\AgentSessionCompleted;
use Ginkida\AgentRunner\Events\AgentSessionCreated;
use Ginkida\AgentRunner\Events\AgentSessionFailed;
use Ginkida\AgentRunner\Events\AgentSessionRunning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles incoming status callback requests from Agent Runner.
 *
 * Route: POST /sessions/{sessionId}/status
 * Dispatches Laravel events based on the session status.
 */
class StatusCallbackController
{
    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $data = $request->json()->all();
        $data['session_id'] = $sessionId;

        $payload = StatusPayload::fromArray($data);

        $event = match ($payload->status) {
            'created' => new AgentSessionCreated($payload),
            'running' => new AgentSessionRunning($payload),
            'completed' => new AgentSessionCompleted($payload),
            'failed' => new AgentSessionFailed($payload),
            'cancelled' => new AgentSessionCancelled($payload),
            default => null,
        };

        if ($event !== null) {
            event($event);
        }

        return response()->json(['ok' => true]);
    }
}
