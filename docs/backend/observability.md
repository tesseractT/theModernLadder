# Observability Guide

This note captures the lightweight observability baseline for the current Laravel API.

## Current health surfaces

- `GET /up`
  Laravel's framework health route. Use it for basic process-level liveness.
- `GET /api/v1/health`
  JSON readiness endpoint for API-oriented checks. It returns app status plus a cheap database readiness signal and includes the normal `X-Request-Id` header.

Example success response:

```json
{
  "status": "ok",
  "checks": {
    "app": {
      "status": "ok"
    },
    "database": {
      "status": "ok"
    }
  },
  "meta": {
    "api_version": "v1",
    "request_id": "client-or-server-request-id",
    "checked_at": "2026-04-12T12:00:00+00:00"
  }
}
```

If the database readiness check fails, the endpoint returns `503` with:

- `status: unavailable`
- `checks.database.status: down`

The response stays intentionally small and does not expose hostnames, credentials, driver DSNs, stack traces, or internal topology.

## Structured log fields

Current operational logs now use a stable request-scoped field set where available:

- `request_id`
- `request_method`
- `request_path`
- `route_name`
- `route_action`
- `user_id` when safely available

High-signal operational events currently include:

- `security.audit`
- `recipe_template.explanation.generated`
- `recipe_template.explanation.failed`
- `api.request.slow`
- `api.request.failed`
- `api.request.exception`
- `health.database.failed`

These events intentionally exclude raw bearer tokens, passwords, provider secrets, and full provider payloads.

`recipe_template.explanation.generated` now distinguishes explanation delivery paths through its `source` field:

- `ai` for a fresh provider-backed explanation
- `fallback` for deterministic fallback output
- `cache` when a previously validated AI explanation was reused for the same grounded request shape

`security.audit` now also covers privileged moderation decisions, with request correlation and safe transition metadata.
Admin users can now browse sanitized audit and AI-failure records through the queryable internal `admin_events` store, while the original log events remain unchanged.

## Request correlation

- API requests preserve a safe caller-supplied `X-Request-Id` when present.
- Otherwise the backend generates one and returns it in the response header.
- The same `request_id` is attached to API log context for correlation across auth, AI, health, and error events.

For staging and production, preserve `X-Request-Id` across proxies or ingress layers rather than generating a second unrelated correlation id downstream.

## Slow and failed request logging

- slow API requests are logged as `api.request.slow`
- unhandled exceptions are logged as `api.request.exception`
- responses with status `500+` are logged as `api.request.failed`

Configure the slow-request threshold with:

- `LOG_SLOW_REQUEST_THRESHOLD_MS`

Set the value to `0` or a negative number to disable slow-request warnings.

## Queue visibility

The repo is queue-ready and includes `jobs`, `job_batches`, and `failed_jobs` tables, but there are no first-party job classes driving live product flows yet.

If a worker is running in staging or production, the current operational commands are:

- `php artisan queue:work`
- `php artisan queue:failed`
- `php artisan queue:retry all`

No extra queue-specific health endpoint or monitoring dashboard is added in this prompt because queued workloads are not yet a real live path in the app code.

## Production logging guidance

- keep `APP_DEBUG=false` outside local development
- prefer log shipping from `stderr` or your platform's standard application log collection path
- keep `LOG_LEVEL=info` or stricter in shared environments unless an incident requires temporary debugging
- use `request_id` plus `route_name` as the first filter when tracing a failing API request
- use `/up` for simple liveness and `/api/v1/health` for API-oriented readiness checks
