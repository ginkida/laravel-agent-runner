# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 0.1.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please report it responsibly. **Do not open a public issue.**

Instead, please email the maintainer directly or use [GitHub's private vulnerability reporting](https://github.com/ginkida/laravel-agent-runner/security/advisories/new).

We will acknowledge receipt within 48 hours and provide a timeline for a fix.

## Security Considerations

### HMAC Authentication

This package uses HMAC-SHA256 for authenticating requests between Laravel and the Agent Runner microservice. Key considerations:

- **Shared secret**: The `AGENT_RUNNER_HMAC_SECRET` must be kept confidential and match between both services
- **Timestamp freshness**: Signatures are valid for ±2 minutes to prevent replay attacks
- **Nonce replay protection**: Each nonce can only be used once (stored in cache for 4 minutes)
- **Constant-time comparison**: Signature verification uses `hash_equals()` to prevent timing attacks

### Key Management

- Store the HMAC secret in environment variables, never in source code
- Use a strong, randomly generated secret (minimum 32 characters recommended)
- Rotate secrets periodically and update both services simultaneously
- When `AGENT_RUNNER_HMAC_SECRET` is empty, HMAC verification is skipped — this should only be used in local development

### Callback Security

- Callback URLs should use HTTPS in production
- The callback endpoint is protected by the same HMAC middleware
- Tool handlers should validate and sanitize all input from `ToolCallbackRequest`
