<?php

namespace Ginkida\AgentRunner\Facades;

use Ginkida\AgentRunner\AgentRunnerManager;
use Ginkida\AgentRunner\Builder\AgentBuilder;
use Ginkida\AgentRunner\Client\AgentRunnerClient;
use Ginkida\AgentRunner\Client\SseStream;
use Ginkida\AgentRunner\Tools\ToolRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AgentBuilder agent(string $name)
 * @method static array createSession(array $agentDefinition, ?array $callback = null, ?string $sessionId = null, ?string $workDir = null)
 * @method static array getSession(string $sessionId)
 * @method static array deleteSession(string $sessionId)
 * @method static array sendMessage(string $sessionId, string $message)
 * @method static SseStream stream(string $sessionId)
 * @method static ToolRegistry tools()
 * @method static AgentRunnerClient client()
 *
 * @see AgentRunnerManager
 */
class AgentRunner extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AgentRunnerManager::class;
    }
}
