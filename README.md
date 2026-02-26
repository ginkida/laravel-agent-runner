# Agent Runner Laravel SDK

Laravel SDK for [Agent Runner](https://github.com/ginkida/agent-runner) — orchestrate AI agents with tool-calling and SSE streaming.

## Features

- Fluent builder API for configuring and executing agent sessions
- Three execution modes: synchronous, manual stream, and fire-and-forget
- Server-Sent Events (SSE) streaming for real-time responses
- HMAC-SHA256 request signing and verification
- Auto-discovery of remote tools
- Event broadcasting for session lifecycle

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x
- ext-curl
- ext-json

## Installation

```bash
composer require ginkida/agent-runner
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=agent-runner-config
```

## Configuration

Add these environment variables to your `.env`:

```env
AGENT_RUNNER_URL=http://localhost:8090
AGENT_RUNNER_HMAC_SECRET=your-shared-secret
AGENT_RUNNER_CLIENT_ID=laravel
AGENT_RUNNER_CALLBACK_URL=https://your-app.test/api/agent-runner
```

See `config/agent-runner.php` for all available options.

## Quick Start

### Synchronous execution

```php
use Ginkida\AgentRunner\Facades\AgentRunner;

$result = AgentRunner::agent('assistant')
    ->model('gpt-4o')
    ->systemPrompt('You are a helpful assistant.')
    ->tools(['read_file', 'write_file'])
    ->onText(fn ($event) => echo $event->content())
    ->run('Summarize the README.md file');
```

### Manual stream consumption

```php
$session = AgentRunner::agent('coder')
    ->systemPrompt('You are a senior developer.')
    ->withAllRemoteTools()
    ->start('Refactor the User model');

foreach ($session['stream']->events() as $event) {
    match ($event->type) {
        'text'        => handleText($event),
        'tool_call'   => handleToolCall($event),
        'tool_result' => handleToolResult($event),
        'done'        => break,
        default       => null,
    };
}
```

### Fire-and-forget

```php
$sessionId = AgentRunner::agent('worker')
    ->dispatch('Process the uploaded CSV file');

// Results arrive via status callback events
```

## Remote Tools

Create a tool by implementing `RemoteToolContract` in `app/AgentTools/`:

```php
namespace App\AgentTools;

use Ginkida\AgentRunner\Contracts\RemoteToolContract;
use Ginkida\AgentRunner\DTOs\ToolCallbackRequest;

class SearchDatabase implements RemoteToolContract
{
    public function name(): string
    {
        return 'search_database';
    }

    public function description(): string
    {
        return 'Search the application database for records.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(ToolCallbackRequest $request): array
    {
        $results = DB::table('records')
            ->where('content', 'like', "%{$request->arguments['query']}%")
            ->limit(10)
            ->get();

        return [
            'success' => true,
            'content' => $results->toJson(),
        ];
    }
}
```

Tools in `app/AgentTools/` are auto-discovered. You can also register them manually:

```php
AgentRunner::tools()->register(new SearchDatabase());
```

## Events

Listen for session lifecycle events in your `EventServiceProvider`:

| Event | Description |
|-------|-------------|
| `AgentSessionCreated` | Session was created |
| `AgentSessionRunning` | Session started processing |
| `AgentSessionCompleted` | Session finished successfully |
| `AgentSessionFailed` | Session encountered an error |
| `AgentSessionCancelled` | Session was cancelled |

## Low-level Client

For direct API access:

```php
$client = AgentRunner::client();

$session = $client->createSession($agentDefinition);
$client->sendMessage($session['session_id'], 'Hello');
$stream = $client->stream($session['session_id']);

$info = $client->getSession($sessionId);
$client->deleteSession($sessionId);
```

## Security

All communication between Laravel and Agent Runner is signed with HMAC-SHA256. Incoming callbacks are verified via the `VerifyHmacSignature` middleware with nonce replay protection.

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
