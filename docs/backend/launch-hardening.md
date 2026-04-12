# Launch Hardening Notes

Prompt 1 adds three immediate launch-hardening foundations without changing the public API shape:

- a fast GitHub Actions quality gate
- named endpoint throttles for high-risk auth and AI explanation routes
- API-wide request correlation with `X-Request-Id`

Later prompts add a small security baseline on top of those controls:

- reusable `security.audit` logging for live token lifecycle events
- API-safe response headers for content sniffing, referrer handling, and sensitive-response caching
- redaction of secret-like AI/provider log context before it reaches application logs
- clearer environment and secret-separation guidance in the repo docs

## CI quality gate

The backend workflow lives at `.github/workflows/quality.yml`.

It runs:

- `composer run quality:validate`
- `composer lint`
- `composer test`
- `composer run quality:audit`

Local verification should match CI:

```bash
cp .env.example .env
php artisan key:generate
composer run quality
```

Scope and behavior:

- it runs for pull requests targeting `main` and pushes to `main`
- it skips pure `apps/flutter_app/**` changes so the workflow stays backend-specific
- Composer downloads are cached for faster repeat runs
- stale runs on the same branch or pull request are canceled automatically
- no Postgres or Redis service containers are required because the backend test suite uses in-memory SQLite and array/sync drivers in `phpunit.xml`

## Endpoint-specific throttles

The global `api` limiter still applies to all API routes.

This prompt adds tighter Laravel-native named limiters for:

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/recipes/templates/{recipeTemplate}/explanation`

Default per-minute limits are configured through `.env`:

- `API_AUTH_REGISTER_RATE_LIMIT_PER_MINUTE=5`
- `API_AUTH_LOGIN_RATE_LIMIT_PER_MINUTE=10`
- `API_AUTH_LOGOUT_RATE_LIMIT_PER_MINUTE=30`
- `API_RECIPE_EXPLANATION_RATE_LIMIT_PER_MINUTE=5`

When one of these named limits is exceeded, the API returns a safe `429` JSON response:

```json
{
  "message": "Too many requests. Please try again later.",
  "code": "too_many_requests",
  "retry_after_seconds": 60
}
```

The response also includes the standard rate-limit headers plus `X-Request-Id`.

## Request correlation

All API responses now include `X-Request-Id`.

Behavior:

- if the client sends a safe ASCII `X-Request-Id`, the backend preserves it
- otherwise the backend generates a UUID
- the request id is attached to API log context for debugging
- raw secrets, tokens, and provider payloads remain excluded from client responses

For public traffic, upstream proxies and edge services should preserve `X-Request-Id` so logs can be correlated across layers.

## Security baseline follow-through

The current repo-specific security baseline is documented in [docs/backend/security.md](security.md).

Highlights:

- auth endpoints and authenticated API responses now send `Cache-Control: no-store, private`
- all API responses now send `X-Content-Type-Options: nosniff` and `Referrer-Policy: no-referrer`
- token issue and revoke flows now emit `security.audit` log events without logging raw tokens
- AI/provider failure logs redact secret-like context keys before writing log entries

## Migration, rollout, and rollback

Migrations:

- none

Rollout:

- merge the workflow and composer/doc updates to `main`
- ensure GitHub Actions is enabled for the repository
- set the `Backend Quality` workflow as a required status check for pull requests if branch protection is being used
- keep the default limiter env values or override them per environment
- verify `GET /api/v1/meta` returns `X-Request-Id`
- verify `GET /api/v1/meta` also returns `X-Content-Type-Options: nosniff` and `Referrer-Policy: no-referrer`
- verify auth and authenticated API responses return `Cache-Control: no-store, private`
- verify repeated auth or explanation calls return the safe `429` JSON shape
- verify `security.audit` entries appear for register, login, logout, and logout-all without raw bearer tokens
- ensure reverse proxies do not strip `X-Request-Id`
- confirm the workflow reports separate validate, lint, test, and audit steps in GitHub

Rollback:

- revert the workflow, composer script, and doc changes if this CI baseline must be removed
- remove the workflow if CI must be rolled back with the code
- remove the new API security-header middleware and audit-log calls if this prompt must be fully rolled back
- redeploy

No data rollback is required because this prompt does not change schema or persisted records.

## Short risk register

Still unresolved for public launch:

- email verification, password reset, and stronger token lifecycle controls
- internal moderation and admin tooling for future public contribution surfaces
- centralized alerting, log shipping, and queue failure visibility
- AI cost controls beyond per-route throttling, such as caching and provider budget guardrails
- future upload handling, which still needs validation, storage, and access-boundary hardening when that surface is introduced
