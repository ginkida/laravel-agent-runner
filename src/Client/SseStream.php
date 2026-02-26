<?php

namespace Ginkida\AgentRunner\Client;

use Generator;
use Ginkida\AgentRunner\DTOs\SseEvent;
use Ginkida\AgentRunner\Exceptions\AgentRunnerException;

/**
 * SSE stream reader using ext-curl and curl_multi for non-blocking event parsing.
 *
 * Guzzle doesn't support SSE natively. We use CURLOPT_WRITEFUNCTION with
 * curl_multi to incrementally parse events as they arrive.
 */
class SseStream
{
    private string $buffer = '';

    public function __construct(
        private readonly string $url,
        private readonly string $clientId,
        private readonly ?HmacSigner $signer = null,
        private readonly int $timeout = 600,
    ) {}

    /**
     * Yield SseEvent objects from the stream as a Generator.
     *
     * @return Generator<int, SseEvent>
     */
    public function events(): Generator
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new AgentRunnerException('Failed to initialize curl handle.');
        }

        $headers = [
            'Accept: text/event-stream',
            'Cache-Control: no-cache',
            "X-Client-ID: {$this->clientId}",
        ];

        if ($this->signer !== null) {
            $hmac = $this->signer->sign('');
            $headers[] = "X-Signature: {$hmac['signature']}";
            $headers[] = "X-Timestamp: {$hmac['timestamp']}";
            $headers[] = "X-Nonce: {$hmac['nonce']}";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        /** @var SseEvent[] $pendingEvents */
        $pendingEvents = [];

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, string $chunk) use (&$pendingEvents): int {
            $this->buffer .= $chunk;

            while (($pos = strpos($this->buffer, "\n\n")) !== false) {
                $rawEvent = substr($this->buffer, 0, $pos);
                $this->buffer = substr($this->buffer, $pos + 2);

                $event = $this->parseEvent($rawEvent);
                if ($event !== null) {
                    $pendingEvents[] = $event;
                }
            }

            return strlen($chunk);
        });

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        try {
            do {
                $status = curl_multi_exec($mh, $active);

                // Yield any events parsed during this iteration
                while (count($pendingEvents) > 0) {
                    $event = array_shift($pendingEvents);
                    yield $event;

                    if ($event->isDone()) {
                        return;
                    }
                }

                if ($active && $status === CURLM_OK) {
                    // Wait for activity, but poll regularly to yield events promptly
                    curl_multi_select($mh, 0.1);
                }
            } while ($active && $status === CURLM_OK);

            // Yield any remaining events
            while (count($pendingEvents) > 0) {
                yield array_shift($pendingEvents);
            }

            // Check for errors on the individual curl handle (covers network
            // failures, timeouts, and connection resets mid-stream)
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);

            if ($curlErrno !== CURLE_OK) {
                throw new AgentRunnerException("SSE stream error: {$curlError}");
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 400) {
                throw new AgentRunnerException("SSE stream returned HTTP {$httpCode}");
            }
        } finally {
            curl_multi_remove_handle($mh, $ch);
            curl_multi_close($mh);
            curl_close($ch);
            $this->buffer = '';
        }
    }

    /**
     * Consume the stream with typed callbacks.
     *
     * @param  array{
     *     text?: callable(string): void,
     *     tool_call?: callable(string, array): void,
     *     tool_result?: callable(string, bool, string): void,
     *     thinking?: callable(string): void,
     *     error?: callable(string): void,
     *     done?: callable(array): void,
     * }  $callbacks
     */
    public function listen(array $callbacks): ?SseEvent
    {
        $lastDone = null;

        foreach ($this->events() as $event) {
            match ($event->type) {
                'text' => isset($callbacks['text'])
                    ? $callbacks['text']($event->textContent())
                    : null,
                'tool_call' => isset($callbacks['tool_call'])
                    ? $callbacks['tool_call']($event->toolName(), $event->toolArgs())
                    : null,
                'tool_result' => isset($callbacks['tool_result'])
                    ? $callbacks['tool_result'](
                        $event->toolName(),
                        $event->data['success'] ?? false,
                        $event->data['content'] ?? '',
                    )
                    : null,
                'thinking' => isset($callbacks['thinking'])
                    ? $callbacks['thinking']($event->textContent())
                    : null,
                'error' => isset($callbacks['error'])
                    ? $callbacks['error']($event->errorMessage())
                    : null,
                'done' => (function () use ($event, $callbacks, &$lastDone) {
                    $lastDone = $event;
                    if (isset($callbacks['done'])) {
                        $callbacks['done']($event->data);
                    }
                })(),
                default => null,
            };
        }

        return $lastDone;
    }

    /**
     * Parse a raw SSE event block into an SseEvent.
     *
     * SSE format from Go's sse.Writer:
     *   event: {type}\ndata: {json}\n\n
     */
    private function parseEvent(string $raw): ?SseEvent
    {
        $type = null;
        $data = null;

        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);

            // Skip comments (heartbeats)
            if (str_starts_with($line, ':')) {
                continue;
            }

            if (str_starts_with($line, 'event:')) {
                $type = trim(substr($line, 6));
            } elseif (str_starts_with($line, 'data:')) {
                $data = trim(substr($line, 5));
            }
        }

        if ($type === null || $data === null) {
            return null;
        }

        $decoded = json_decode($data, true, 64, JSON_THROW_ON_ERROR);

        return new SseEvent($type, is_array($decoded) ? $decoded : []);
    }
}
