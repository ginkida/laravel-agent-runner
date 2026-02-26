# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2025-01-01

### Added

- Fluent `AgentBuilder` with three execution modes: `run()`, `start()`, `dispatch()`
- `AgentRunnerClient` HTTP client for all 5 Agent Runner API endpoints
- HMAC-SHA256 request signing and verification (`HmacSigner`)
- SSE streaming via `curl_multi` (`SseStream`)
- Remote tool system with auto-discovery (`ToolRegistry`, `ToolDiscovery`, `RemoteToolContract`)
- Callback controllers for tool execution and session status updates
- `VerifyHmacSignature` middleware with nonce replay protection
- Session status events: `AgentSessionCreated`, `AgentSessionRunning`, `AgentSessionCompleted`, `AgentSessionFailed`, `AgentSessionCancelled`
- Readonly DTOs: `SseEvent`, `StatusPayload`, `ToolCallbackRequest`
- Laravel 11 and 12 support
- PHP 8.2+ support
