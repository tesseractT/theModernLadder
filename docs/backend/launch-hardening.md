# Launch Hardening Notes

Prompt 1 adds three immediate launch-hardening foundations without changing the public API shape:

- a fast GitHub Actions quality gate
- named endpoint throttles for high-risk auth and AI explanation routes
- API-wide request correlation with `X-Request-Id`

## CI quality gate

The backend workflow lives at `.github/workflows/quality.yml`.

It runs:

- `composer lint`
- `composer test`

Local verification should match CI:

```bash
cp .env.example .env
php artisan key:generate
composer lint
composer test
```

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

## Migration, rollout, and rollback

Migrations:

- none

Rollout:

- deploy the code changes
- keep the default limiter env values or override them per environment
- verify `GET /api/v1/meta` returns `X-Request-Id`
- verify repeated auth or explanation calls return the safe `429` JSON shape
- ensure reverse proxies do not strip `X-Request-Id`

Rollback:

- revert the code changes, or temporarily raise the new per-route limiter env values
- remove the workflow if CI must be rolled back with the code
- redeploy

No data rollback is required because this prompt does not change schema or persisted records.

## Short risk register

Still unresolved for public launch:

- email verification, password reset, and stronger token lifecycle controls
- internal moderation and admin tooling for future public contribution surfaces
- centralized alerting, log shipping, and queue failure visibility
- AI cost controls beyond per-route throttling, such as caching and provider budget guardrails
