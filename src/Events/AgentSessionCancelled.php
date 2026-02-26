<?php

namespace Ginkida\AgentRunner\Events;

use Ginkida\AgentRunner\DTOs\StatusPayload;
use Illuminate\Foundation\Events\Dispatchable;

class AgentSessionCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly StatusPayload $payload,
    ) {}
}
