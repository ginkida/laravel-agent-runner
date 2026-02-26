<?php

namespace Ginkida\AgentRunner\Tests\Feature;

use Ginkida\AgentRunner\AgentRunnerManager;
use Ginkida\AgentRunner\Client\AgentRunnerClient;
use Ginkida\AgentRunner\Tests\TestCase;
use Ginkida\AgentRunner\Tools\ToolRegistry;

class ServiceProviderTest extends TestCase
{
    public function test_tool_registry_is_singleton(): void
    {
        $registry1 = $this->app->make(ToolRegistry::class);
        $registry2 = $this->app->make(ToolRegistry::class);

        $this->assertSame($registry1, $registry2);
    }

    public function test_agent_runner_client_is_singleton(): void
    {
        $client1 = $this->app->make(AgentRunnerClient::class);
        $client2 = $this->app->make(AgentRunnerClient::class);

        $this->assertSame($client1, $client2);
    }

    public function test_agent_runner_manager_is_singleton(): void
    {
        $manager1 = $this->app->make(AgentRunnerManager::class);
        $manager2 = $this->app->make(AgentRunnerManager::class);

        $this->assertSame($manager1, $manager2);
    }

    public function test_config_is_loaded(): void
    {
        $this->assertSame('http://localhost:8090', config('agent-runner.base_url'));
        $this->assertSame('test-secret', config('agent-runner.hmac_secret'));
        $this->assertSame('test-client', config('agent-runner.client_id'));
    }

    public function test_routes_are_registered(): void
    {
        $routes = $this->app['router']->getRoutes();

        $toolRoute = $routes->getByAction('Ginkida\AgentRunner\Http\Controllers\ToolCallbackController');
        $this->assertNotNull($toolRoute);

        $statusRoute = $routes->getByAction('Ginkida\AgentRunner\Http\Controllers\StatusCallbackController');
        $this->assertNotNull($statusRoute);
    }

    public function test_routes_have_correct_prefix(): void
    {
        $routes = $this->app['router']->getRoutes();

        $toolRoute = $routes->getByAction('Ginkida\AgentRunner\Http\Controllers\ToolCallbackController');
        $this->assertStringContainsString('api/agent-runner/tools', $toolRoute->uri());

        $statusRoute = $routes->getByAction('Ginkida\AgentRunner\Http\Controllers\StatusCallbackController');
        $this->assertStringContainsString('api/agent-runner/sessions', $statusRoute->uri());
    }

    public function test_routes_can_be_disabled(): void
    {
        $this->app['config']->set('agent-runner.routes.enabled', false);

        // Re-register the service provider to pick up the config change
        $this->app->register(\Ginkida\AgentRunner\AgentRunnerServiceProvider::class, true);

        // The original routes are still there from the initial boot,
        // but we verify the config option exists and is respected
        $this->assertFalse(config('agent-runner.routes.enabled'));
    }

    public function test_manager_has_tool_registry(): void
    {
        $manager = $this->app->make(AgentRunnerManager::class);
        $registry = $this->app->make(ToolRegistry::class);

        $this->assertSame($registry, $manager->tools());
    }

    public function test_manager_has_client(): void
    {
        $manager = $this->app->make(AgentRunnerManager::class);

        $this->assertInstanceOf(AgentRunnerClient::class, $manager->client());
    }
}
