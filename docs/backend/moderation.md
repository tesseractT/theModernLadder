# Moderation Workflow Guide

This step activates the first real contribution and moderation workflow for the mobile/backend stack.

It stays intentionally small:

- authenticated users can submit structured text-first contributions
- approved public contributions can be reported by users
- moderators and admins can review queue items and take explicit moderation actions
- every moderation decision is stored in domain data and emits the existing `security.audit` log event for privileged actions

It does not yet auto-apply approved contributions into canonical ingredient or recipe records.

## Contribution types

Current supported contribution types:

- `recipe_template_change`
- `pairing_tip`
- `substitution_tip`
- `ingredient_alias_correction`

These are intentionally aligned to the existing ingredient and recipe-template domain model.

## Contribution states

Current contribution states:

- `pending`
- `approved`
- `rejected`
- `flagged`

Supported transitions:

- `pending -> approved`
- `pending -> rejected`
- `approved -> flagged`
- `flagged -> approved`
- `flagged -> rejected`

Invalid transitions return a validation-shaped `422` response.

## Roles and boundaries

- `user`
  Can submit contributions and report approved or already-flagged contributions.
- `moderator`
  Can access the moderation queue, contribution detail, and moderation action endpoints.
- `admin`
  Shares moderation access and keeps the broader admin role foundation already present in the repo.

The implementation reuses the existing internal role model plus `User::canModerate()` through Laravel policy checks. No parallel permission system is introduced.

## Endpoints

User-facing:

- `POST /api/v1/me/contributions`
- `POST /api/v1/me/contributions/{contribution}/reports`

Internal moderation:

- `GET /api/v1/moderation/contributions`
- `GET /api/v1/moderation/contributions/{contribution}`
- `POST /api/v1/moderation/contributions/{contribution}/actions`

Route-specific throttles are applied to contribution submission, reporting, and moderation actions.

## Report flow

- reports are structured through `reason_code`
- reporter notes are optional and length-limited
- duplicate open reports from the same user for the same contribution and reason are rejected
- reporting an approved contribution moves it to `flagged` so it enters the review queue deterministically

## Audit and history

Persistent workflow history now lives in:

- `contributions`
  Current contribution state plus latest moderator note and reviewer metadata.
- `moderation_cases`
  Active or resolved review cases tied to reported or manually flagged contributions.
- `moderation_actions`
  Append-only action history for reports and moderator decisions, including actor, request id, and state transition context.

Privileged moderation actions also emit `security.audit` events:

- `moderation.contribution.approved`
- `moderation.contribution.rejected`
- `moderation.contribution.flagged`

Audit logs intentionally exclude raw moderation notes and any secret/token data.

## Current constraints

- approved contributions are moderated records only; they are not yet auto-written into canonical ingredient, alias, pairing, substitution, or recipe-template tables
- there is still no media or upload moderation surface
- there is still no full admin dashboard, notifications workflow, or automated trust-and-safety rules engine

## Rollout notes

- run `php artisan migrate`
- ensure at least one internal account is assigned `moderator` or `admin`
- wire Flutter or internal tooling only to the new contribution/report/moderation endpoints documented above
- confirm `security.audit` events appear for moderator decisions with `target_type=contribution`

## Rollback notes

- run `php artisan migrate:rollback --step=1` to remove the `moderation_actions` table and the new `contributions.type` column
- note that rollback normalizes any `flagged` contribution rows back to the legacy `submitted` placeholder state
- remove client usage of the moderation endpoints before rollback to avoid orphaned workflow expectations
