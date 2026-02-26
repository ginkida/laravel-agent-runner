<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Agent Runner Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the Agent Runner Go microservice.
    |
    */
    'base_url' => env('AGENT_RUNNER_URL', 'http://localhost:8090'),

    /*
    |--------------------------------------------------------------------------
    | HMAC Authentication
    |--------------------------------------------------------------------------
    |
    | Shared secret for HMAC-SHA256 request signing. Must match the secret
    | configured in Agent Runner's config.yaml (auth.hmac_secret).
    |
    */
    'hmac_secret' => env('AGENT_RUNNER_HMAC_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Client ID
    |--------------------------------------------------------------------------
    |
    | Identifies this Laravel application to Agent Runner. Sent as
    | X-Client-ID header on every outgoing request.
    |
    */
    'client_id' => env('AGENT_RUNNER_CLIENT_ID', 'laravel'),

    /*
    |--------------------------------------------------------------------------
    | Callback Configuration
    |--------------------------------------------------------------------------
    |
    | Base URL that Agent Runner will call back to for remote tool execution
    | and status notifications. Must be reachable from the Agent Runner host.
    |
    */
    'callback' => [
        'base_url' => env('AGENT_RUNNER_CALLBACK_URL', ''),
        'timeout' => (int) env('AGENT_RUNNER_CALLBACK_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Agent Settings
    |--------------------------------------------------------------------------
    |
    | Default values applied to all agents unless overridden via the builder.
    |
    */
    'defaults' => [
        'model' => env('AGENT_RUNNER_DEFAULT_MODEL', 'gpt-4o-mini'),
        'max_turns' => (int) env('AGENT_RUNNER_DEFAULT_MAX_TURNS', 30),
        'max_tokens' => (int) env('AGENT_RUNNER_DEFAULT_MAX_TOKENS', 0),
        'temperature' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the incoming callback routes (tool execution + status).
    |
    */
    'routes' => [
        'prefix' => env('AGENT_RUNNER_ROUTE_PREFIX', 'api/agent-runner'),
        'middleware' => ['api'],
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Discovery
    |--------------------------------------------------------------------------
    |
    | Automatic discovery of RemoteToolContract implementations.
    |
    */
    'tools' => [
        'discovery' => [
            'enabled' => true,
            'path' => app_path('AgentTools'),
            'namespace' => 'App\\AgentTools',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the outgoing HTTP client to Agent Runner.
    |
    */
    'http' => [
        'timeout' => (int) env('AGENT_RUNNER_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('AGENT_RUNNER_HTTP_CONNECT_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSE Settings
    |--------------------------------------------------------------------------
    |
    | Settings for Server-Sent Events streaming from Agent Runner.
    |
    */
    'sse' => [
        'timeout' => (int) env('AGENT_RUNNER_SSE_TIMEOUT', 600),
    ],

];
