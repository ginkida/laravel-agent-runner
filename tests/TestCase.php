<?php

namespace Ginkida\AgentRunner\Tests;

use Ginkida\AgentRunner\AgentRunnerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AgentRunnerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('agent-runner.base_url', 'http://localhost:8090');
        $app['config']->set('agent-runner.hmac_secret', 'test-secret');
        $app['config']->set('agent-runner.client_id', 'test-client');
        $app['config']->set('agent-runner.callback.base_url', 'http://localhost:8000/api/agent-runner');
        $app['config']->set('agent-runner.tools.discovery.enabled', false);
    }
}
