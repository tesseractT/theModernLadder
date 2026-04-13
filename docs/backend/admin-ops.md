# Admin Operations Guide

Step 8 adds the first internal control surfaces for admin users.

The scope stays intentionally narrow:

- admin-only read APIs over flagged moderation state
- admin-only read APIs over recent moderation actions
- admin-only read APIs over queryable audit events and AI explanation failures
- a lightweight suspicious-activity summary built from real repo signals

There is still no dashboard UI in this prompt.

## Access boundary

All admin ops endpoints are protected by:

- `auth:sanctum`
- `active.user`
- `can:access-admin`

This reuses the existing role/gate foundation already present in the repo.

Effects:

- normal `user` accounts are denied
- `moderator` accounts are also denied
- only `admin` accounts can reach these internal read surfaces

## Endpoints

- `GET /api/v1/admin/moderation/flagged-contributions`
- `GET /api/v1/admin/moderation/actions`
- `GET /api/v1/admin/ops/suspicious-activity`
- `GET /api/v1/admin/ai/failures`
- `GET /api/v1/admin/audit-events`

All endpoints use the normal request-id, JSON, and security-header middleware stack.

Admin reads also use a named route throttle so internal tooling still follows the repo’s route-specific throttling pattern.

## Flagged content listing

The flagged contribution list is read-only and currently scoped to flagged contributions only.

It exposes enough context for an operator to understand why an item is flagged:

- contribution id, type, status, and subject summary
- submitting user summary
- active moderation case summary
- aggregate report count
- latest moderation action summary

Current filters:

- `reason_code`
- `subject_type`

## Recent moderation actions

The recent moderation actions endpoint reads from the persistent `moderation_actions` table added in the moderation prompt.

It exposes:

- actor summary
- action
- from/to status
- contribution target summary
- `request_id`
- concise `notes_summary`
- timestamp

Current filters:

- `action`
- `actor_user_id`

## Queryable admin event store

This prompt adds an append-only `admin_events` table as the smallest shared internal event store for admin visibility.

Current streams:

- `security_audit`
- `ai_explanation_failure`

### Security audit events

Existing `SecurityAuditLogger` behavior is preserved:

- events are still written to application logs as `security.audit`
- safe redaction still happens before log write

New in this step:

- the same sanitized audit event is also persisted to `admin_events`
- admin users can browse it through `GET /api/v1/admin/audit-events`

Exposed fields include:

- event name
- actor id when available
- target type and target id when available
- `request_id`
- route name
- safe metadata
- `occurred_at`

This endpoint does not parse runtime log files.

## AI failure visibility

AI explanation failures were previously log-only through `recipe_template.explanation.failed`.

This step keeps the log event and adds a queryable admin record for failed explanation attempts.

Stored fields are intentionally small and safe:

- request id
- route name
- user id when available
- recipe template id
- provider
- model when available
- failure type
- failure reason
- provider error status/code when available
- whether deterministic fallback was used
- prompt/schema versions
- occurred-at timestamp

Not stored:

- raw prompts
- raw provider payloads
- bearer tokens
- provider secrets
- large exception blobs

## Suspicious-activity summary

`GET /api/v1/admin/ops/suspicious-activity` currently summarizes three explainable signals over a 7-day lookback:

- high report volume users
  Current threshold: 3 or more report actions from the same user.
- contribution churn
  Current threshold: 2 or more moderation decisions on the same contribution with at least 2 distinct resulting states.
- repeated AI failures
  Current threshold: 2 or more AI explanation failure records on the same recipe template.

This is not a reputation engine or opaque trust score. It is a lightweight triage summary built from existing moderation history and internal admin events.

## Rollout notes

- run `php artisan migrate`
- ensure at least one internal user has the `admin` role
- keep moderators on the moderation endpoints; admin ops endpoints are intentionally stricter
- verify queryable `admin_events` rows appear for new auth/moderation audit events and AI explanation failures

## Rollback notes

- run `php artisan migrate:rollback --step=1` to remove the `admin_events` table
- remove any internal tooling that depends on the new admin endpoints before rollback
- note that rollback removes queryable audit/AI-failure visibility but does not remove the underlying application log events
