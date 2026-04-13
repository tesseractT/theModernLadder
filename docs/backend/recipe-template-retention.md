# Recipe Template Retention Guide

Step 10 adds the first user-scoped retention foundation for recipe-template follow-through.

The scope stays intentionally small:

- private favorites for recipe templates
- private saved suggestions anchored to canonical template ids
- bounded recent history for meaningful template use
- a lightweight short-horizon planner with `today`, `tomorrow`, and `this_week`

This step does not add public sharing, notifications, grocery workflows, or a full meal-planning system.

## Endpoints

All endpoints remain versioned under `/api/v1` and use the normal authenticated mobile stack:

- `GET /api/v1/me/recipe-templates/favorites`
- `PUT /api/v1/me/recipe-templates/{recipeTemplate}/favorite`
- `DELETE /api/v1/me/recipe-templates/{recipeTemplate}/favorite`
- `GET /api/v1/me/recipe-templates/saved-suggestions`
- `PUT /api/v1/me/recipe-templates/{recipeTemplate}/saved-suggestion`
- `DELETE /api/v1/me/recipe-templates/{recipeTemplate}/saved-suggestion`
- `GET /api/v1/me/recipe-templates/history`
- `GET /api/v1/me/recipe-plans`
- `POST /api/v1/me/recipe-plans`
- `PATCH /api/v1/me/recipe-plans/{recipePlanItem}`
- `DELETE /api/v1/me/recipe-plans/{recipePlanItem}`

Write endpoints use dedicated route throttles so the retention layer follows the same hardened route-specific throttling pattern as auth, moderation, and AI explanation writes.

## Canonical data model

### Favorites

Favorites are long-lived private bookmarks.

Properties:

- anchored to the canonical `recipe_template_id`
- idempotent `PUT` write behavior
- no duplicated template blobs
- removable with a simple `DELETE`

### Saved suggestions

Saved suggestions are for “come back to this later” moments and still use the canonical template as the source of truth.

Stored context stays intentionally small:

- `recipe_template_id`
- `source` such as `suggestions`, `recipe_detail`, or `recipe_explanation`
- optional `goal` when Flutter wants to preserve the suggestion filter that produced the save
- timestamps

Not stored:

- raw suggestion candidate blobs
- AI prompts or provider payloads
- large explanation text snapshots

### Recent history

Recent history is privacy-conscious by design.

Current behavior:

- history is recorded automatically when the authenticated user opens `GET /api/v1/recipes/templates/{recipeTemplate}`
- history is also refreshed when the user successfully requests `POST /api/v1/recipes/templates/{recipeTemplate}/explanation`
- history is deduped to one row per user and recipe template
- history is capped to a configurable recent window through `RECIPE_RECENT_HISTORY_MAX_ENTRIES`
- older rows are trimmed instead of building an infinite append-only event stream

This means history reflects meaningful template follow-through without storing every suggestion impression or becoming a broad surveillance log.

### Planner-lite

Planner items are also anchored to canonical recipe templates.

Current fields:

- `recipe_template_id`
- `horizon`: `today`, `tomorrow`, or `this_week`
- optional short `note`

Current write behavior:

- `POST` creates a plan item for a template/horizon pair
- posting the same template + horizon again updates the existing item instead of creating a duplicate
- `PATCH` updates `horizon` and/or `note`
- `DELETE` removes the item outright

This is intentionally not a full meal-planning engine.

## Privacy and retention notes

Current privacy guardrails:

- every record is scoped by `user_id`
- list reads only return the authenticated user's data
- plan-item route binding is owner-scoped and returns `404` for other users' records
- no retention data is public or social in this step
- no raw AI/provider payloads are stored anywhere in this foundation
- history is bounded and deduped rather than infinitely appended
- deletes are straightforward for favorites, saved suggestions, and plan items

A later “clear history” endpoint can be added cleanly because history already uses bounded canonical rows rather than immutable event logs.

## Flutter consumption

Recommended client flow:

1. Use the `recipe_template_id` returned from deterministic suggestions or template detail as the canonical id for all retention actions.
2. Favorite a template with `PUT /api/v1/me/recipe-templates/{recipeTemplate}/favorite`.
3. Save a suggestion with `PUT /api/v1/me/recipe-templates/{recipeTemplate}/saved-suggestion` and pass `source` plus optional `goal` when available.
4. Fetch favorites with `GET /api/v1/me/recipe-templates/favorites`.
5. Fetch saved suggestions with `GET /api/v1/me/recipe-templates/saved-suggestions`.
6. Fetch recent history with `GET /api/v1/me/recipe-templates/history`.
7. Create a planner item with `POST /api/v1/me/recipe-plans`.
8. Update a planner item with `PATCH /api/v1/me/recipe-plans/{recipePlanItem}`.
9. Delete a planner item with `DELETE /api/v1/me/recipe-plans/{recipePlanItem}`.

Client expectations:

- favorite `PUT` is idempotent; repeated calls keep one favorite row
- saved-suggestion `PUT` is idempotent for the same template and updates the lightweight context if called again
- planner `POST` dedupes by template + horizon and returns the updated existing row on repeat calls
- recent history updates automatically when the user opens detail or explanation, so Flutter does not need a separate explicit history-write call in this step

## Rollout notes

- run `php artisan migrate`
- no backfill is required; favorites, saved suggestions, plan items, and recent history begin from deployment forward
- if you want a tighter privacy window, set `RECIPE_RECENT_HISTORY_MAX_ENTRIES` before deploy
- ensure Flutter reuses canonical `recipe_template_id` values from suggestions/detail instead of storing raw candidate blobs locally as a source of truth

## Rollback notes

- run `php artisan migrate:rollback --step=1`
- remove client usage of the new retention endpoints before rollback
- rollback removes the retention tables entirely, including any saved favorites, saved suggestions, recent history, and plan items created after rollout
