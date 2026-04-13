# The Modern Ladder Backend

Production-grade backend foundation for a mobile-first food discovery platform built with Laravel, PostgreSQL, Redis, and an API-first modular monolith architecture.

## Product guardrails

- Food discovery, recipe suggestion, substitutions, pairing, pantry, and general nutrition education.
- Not a medical diagnosis, treatment, or disease-management system.
- No sensitive health-condition modeling in the Step 1 schema.
- User-generated content is prepared for moderation and auditability from the start.

## Stack

- Laravel 13
- PostgreSQL as the primary database
- Redis for cache and queues
- Sanctum personal access tokens for Flutter/mobile auth
- REST API designed for a Flutter client

## Module structure

See [docs/backend/architecture.md](docs/backend/architecture.md) for the foundation architecture note, [docs/backend/authentication.md](docs/backend/authentication.md) for Flutter auth usage, [docs/backend/security.md](docs/backend/security.md) for the current security baseline and threat model, [docs/backend/observability.md](docs/backend/observability.md) for health and logging operations, [docs/backend/pantry.md](docs/backend/pantry.md) for pantry integration, [docs/backend/suggestions.md](docs/backend/suggestions.md) for deterministic suggestions, [docs/backend/recipe-templates.md](docs/backend/recipe-templates.md) for template detail follow-through, [docs/backend/recipe-template-explanations.md](docs/backend/recipe-template-explanations.md) for the grounded AI explanation layer, [docs/backend/moderation.md](docs/backend/moderation.md) for the first live contribution/moderation workflow, and [docs/backend/admin-ops.md](docs/backend/admin-ops.md) for the internal admin control surfaces.

Core modules:

- `Shared`: cross-cutting API conventions, enums, and reusable support
- `Auth`: token-auth foundation and future session/device boundaries
- `Users`: user accounts, profiles, and preferences
- `Pantry`: user pantry inventory
- `Ingredients`: ingredients, aliases, pairings, and substitutions
- `Recipes`: recipe template catalog
- `Contributions`: structured user submissions and change proposals
- `Moderation`: moderation cases and review state
- `Reputation`: contributor reputation aggregates
- `Notifications`: future notification delivery boundaries
- `Admin`: future internal tooling and audit operations
- `AI`: server-side grounded explanation orchestration

## Local setup

1. Copy `.env.example` to `.env` if needed and update PostgreSQL / Redis credentials.
2. Install dependencies: `composer install`
3. Generate the key: `php artisan key:generate`
4. Run migrations: `php artisan migrate`
5. Load the starter catalog: `php artisan db:seed`
6. Install Flutter dependencies once: `cd apps/flutter_app && flutter pub get`
7. Start Laravel plus Flutter together: `./dev.sh`
8. Start the API only when needed: `composer dev`
9. Start a worker when needed: `composer queue:work`

Useful commands:

- `composer test`
- `composer lint`
- `composer format`
- `./dev.sh chrome`
- `./dev.sh ios`

## Quality gate and launch hardening

- GitHub Actions backend quality gate lives at `.github/workflows/quality.yml`.
- CI runs Composer metadata validation, Pint in test mode, the Laravel test suite, and a Composer dependency audit on pull requests targeting `main` plus pushes to `main`.
- The backend workflow intentionally skips pure `apps/flutter_app/**` changes so it stays focused on the live Laravel surface.
- Local verification matches CI: `cp .env.example .env && php artisan key:generate && composer run quality`
- The backend test suite stays lightweight in CI because PHPUnit uses in-memory SQLite plus array/sync drivers, so no Postgres or Redis service containers are required.
- Launch-hardening notes, env toggles, rollout steps, and rollback notes live in [docs/backend/launch-hardening.md](docs/backend/launch-hardening.md).
- Repo-specific security baseline, threat model notes, and secret-handling expectations live in [docs/backend/security.md](docs/backend/security.md).
- Observability, health-check, and production log-usage notes live in [docs/backend/observability.md](docs/backend/observability.md).

## Implemented so far

- Laravel API bootstrapped in this repository
- Explicit module boundaries under `app/Modules`
- PostgreSQL and Redis defaults configured for local and production-like development
- Sanctum-based register, login, logout, and bearer-token account auth
- Lean core schema for users, profiles, preferences, ingredients, recipes, and moderation foundations
- Authenticated pantry CRUD, ingredient lookup, and deterministic pantry-to-suggestion generation
- Pantry-aware recipe-template detail and grounded server-side AI explanations
- Structured contribution submission, reporting, and first-pass moderation queue/actions
- Admin-only internal ops endpoints for flagged content, moderation history, audit events, suspicious summary hooks, and AI failure visibility
- Request correlation IDs on API responses plus tighter throttles on high-risk auth and AI explanation endpoints
- Versioned API scaffolding with request validation, resources, and feature tests
- Documentation for Flutter authentication and current backend scope

## Current auth endpoints

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/logout/all`
- `GET /api/v1/me`
- `PATCH /api/v1/me/profile`
- `PATCH /api/v1/me/preferences`

## Current pantry endpoints

- `GET /api/v1/ingredients/search?q=...`
- `GET /api/v1/me/pantry`
- `POST /api/v1/me/pantry`
- `PATCH /api/v1/me/pantry/{pantryItem}`
- `DELETE /api/v1/me/pantry/{pantryItem}`

## Current suggestion endpoint

- `POST /api/v1/me/suggestions`

## Current recipe detail endpoint

- `GET /api/v1/recipes/templates/{recipeTemplate}`

## Current recipe explanation endpoint

- `POST /api/v1/recipes/templates/{recipeTemplate}/explanation`

## Current contribution and moderation endpoints

- `POST /api/v1/me/contributions`
- `POST /api/v1/me/contributions/{contribution}/reports`
- `GET /api/v1/moderation/contributions`
- `GET /api/v1/moderation/contributions/{contribution}`
- `POST /api/v1/moderation/contributions/{contribution}/actions`

## Current admin ops endpoints

- `GET /api/v1/admin/moderation/flagged-contributions`
- `GET /api/v1/admin/moderation/actions`
- `GET /api/v1/admin/ops/suspicious-activity`
- `GET /api/v1/admin/ai/failures`
- `GET /api/v1/admin/audit-events`

## Step 9 recommendation

Build the template interaction loop next: add lightweight save/bookmark and “cooked this” style endpoints so the app can persist what users act on after opening a suggestion or reading an explanation, creating clean first-party feedback signals before deeper personalization work.
