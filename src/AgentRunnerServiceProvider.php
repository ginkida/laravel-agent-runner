<?php

namespace Ginkida\AgentRunner;

use Ginkida\AgentRunner\Client\AgentRunnerClient;
use Ginkida\AgentRunner\Exceptions\HmacVerificationException;
use Ginkida\AgentRunner\Exceptions\ToolExecutionException;
use Ginkida\AgentRunner\Tools\ToolDiscovery;
use Ginkida\AgentRunner\Tools\ToolRegistry;
use Illuminate\Support\ServiceProvider;

class AgentRunnerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/agent-runner.php', 'agent-runner');

        $this->app->singleton(ToolRegistry::class);

        $this->app->singleton(AgentRunnerClient::class, function ($app) {
            $config = $app['config']['agent-runner'];

            return new AgentRunnerClient(
                baseUrl: $config['base_url'],
                clientId: $config['client_id'],
                hmacSecret: $config['hmac_secret'] ?? '',
                timeout: $config['http']['timeout'] ?? 30,
                connectTimeout: $config['http']['connect_timeout'] ?? 5,
            );
        });

        $this->app->singleton(AgentRunnerManager::class, function ($app) {
            $config = $app['config']['agent-runner'];

            $callbackConfig = null;
            $callbackUrl = $config['callback']['base_url'] ?? '';
            if ($callbackUrl !== '') {
                $callbackConfig = [
                    'base_url' => $callbackUrl,
                    'timeout_sec' => $config['callback']['timeout'] ?? 30,
                ];
            }

            return new AgentRunnerManager(
                client: $app->make(AgentRunnerClient::class),
                registry: $app->make(ToolRegistry::class),
                defaults: $config['defaults'] ?? [],
                callbackConfig: $callbackConfig,
                sseTimeout: $config['sse']['timeout'] ?? 600,
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/agent-runner.php' => config_path('agent-runner.php'),
        ], 'agent-runner-config');

        $this->loadRoutes();
        $this->discoverTools();
        $this->registerExceptionRendering();
    }

    private function loadRoutes(): void
    {
        $config = $this->app['config']['agent-runner'];

        if (! ($config['routes']['enabled'] ?? true)) {
            return;
        }

        $this->app['router']
            ->prefix($config['routes']['prefix'] ?? 'api/agent-runner')
            ->middleware($config['routes']['middleware'] ?? ['api'])
            ->group(__DIR__ . '/../routes/agent-runner.php');
    }

    private function discoverTools(): void
    {
        $config = $this->app['config']['agent-runner'];

        if (! ($config['tools']['discovery']['enabled'] ?? true)) {
            return;
        }

        $path = $config['tools']['discovery']['path'] ?? app_path('AgentTools');
        $namespace = $config['tools']['discovery']['namespace'] ?? 'App\\AgentTools';

        $discovery = new ToolDiscovery($path, $namespace);
        $registry = $this->app->make(ToolRegistry::class);

        foreach ($discovery->discover() as $tool) {
            $registry->register($tool);
        }
    }

    private function registerExceptionRendering(): void
    {
        try {
            $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        } catch (\Throwable) {
            return;
        }

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(function (HmacVerificationException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        });

        $handler->renderable(function (ToolExecutionException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        });
    }
}
