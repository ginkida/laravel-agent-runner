<?php

namespace Ginkida\AgentRunner\Events;

use Ginkida\AgentRunner\DTOs\StatusPayload;
use Illuminate\Foundation\Events\Dispatchable;

class AgentSessionRunning
{
    use Dispatchable;

    public function __construct(
        public readonly StatusPayload $payload,
    ) {}
}
