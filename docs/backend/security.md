# Security Baseline

This note captures the current repo-specific security baseline for launch-ready mobile API traffic.

## Trust boundaries

- Public clients call the versioned JSON API under `/api/v1`.
- Authenticated clients cross into user-scoped pantry, profile, preference, suggestion, recipe detail, and recipe explanation routes through Sanctum bearer tokens.
- The API crosses an external trust boundary when the grounded explanation flow calls the configured AI provider.
- Infrastructure secrets stay outside the repo and enter the app only through environment-specific configuration.

## Sensitive assets

- Sanctum personal access tokens
- user account, profile, pantry, and preference data
- database, Redis, mail, cloud, and AI provider credentials
- grounded explanation prompt/result flow and provider failure context

## Threat model summary

- Assets: bearer tokens, user-scoped food data, environment secrets, AI provider availability
- Actors: normal mobile users, abusive anonymous clients, compromised-token holders, operators with environment access
- Top abuse paths: brute-force auth traffic, token replay after logout expectations, accidental secret leakage in logs, stale cached auth responses, future AI/provider exceptions carrying unsafe context
- Likely impact: account misuse, cross-request debugging blind spots, secret disclosure to operators or vendors, confusing client-side reuse of sensitive responses
- Current controls: request ids, named route throttles, standardized `401`/`403`, policy/gate foundation, server-side AI boundary, safe client-facing AI failure responses
- Remaining gaps addressed here: reusable security audit events, safer API response headers, log-context redaction, clearer environment separation guidance

## Implemented baseline

### Security audit logging

The backend now emits `security.audit` log entries for real token lifecycle events:

- `auth.register.succeeded`
- `auth.login.succeeded`
- `auth.logout.succeeded`
- `auth.logout_all.succeeded`
- `moderation.contribution.approved`
- `moderation.contribution.rejected`
- `moderation.contribution.flagged`

These events now also land in the internal append-only `admin_events` store for admin-facing browsing without parsing raw log files.

Each event includes:

- `event`
- `actor_id` when available
- `request_id`
- `target_type`
- `target_id` when applicable
- small safe metadata such as `revoked_token_count`
- moderation transition metadata such as `from_status`, `to_status`, and `moderation_case_id`

The audit logger never stores raw bearer tokens, passwords, provider secrets, or full provider payloads.
It also avoids raw moderator notes in privileged moderation audit events.

### Safe API response defaults

All API responses now include:

- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: no-referrer`

Auth endpoints and authenticated API responses also include:

- `Cache-Control: no-store, private`

This keeps token-bearing and user-scoped responses out of client and intermediary caches without changing JSON payload shapes.

### Secret-handling and logging

- AI explanation failure logs now redact secret-like context keys before anything is written to application logs.
- privileged moderation decisions reuse the same sanitized audit logger and do not write raw notes into `security.audit`
- admin-visible AI failure records store only curated safe fields such as request id, provider, failure type, and fallback usage
- AI explanation guardrails reject unsupported certainty language and allergy-safety phrasing before anything reaches clients
- Client-facing AI failures still return the existing safe `503` contract and do not expose provider errors or secrets.
- The repo intentionally does not log raw passwords, raw bearer tokens, or full provider request/response bodies.

## Environment separation

- keep `APP_DEBUG=false` outside local development
- use unique credentials per environment for PostgreSQL, Redis, mail, and AI providers
- do not reuse local secrets in staging or production
- keep `LOG_LEVEL=debug` local-only unless actively investigating a controlled incident
- rotate provider or infrastructure secrets by environment rather than sharing a single credential across deployments
- store mobile tokens on-device in secure storage and never copy them into logs, tickets, or seeded fixtures

## Uploads

There is no active upload surface in the current live repo.

When uploads are added later, they must ship with:

- strict MIME and size validation
- storage outside executable/public code paths unless explicitly intended
- owner or role-based access boundaries
- tests for malicious file types, oversize payloads, and authorization

The current moderation workflow still does not include any upload or media-review path.
