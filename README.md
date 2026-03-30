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

See [docs/backend/architecture.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/architecture.md) for the foundation architecture note, [docs/backend/authentication.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/authentication.md) for Flutter auth usage, [docs/backend/pantry.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/pantry.md) for pantry integration, [docs/backend/suggestions.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/suggestions.md) for deterministic suggestions, and [docs/backend/recipe-templates.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/recipe-templates.md) for template detail follow-through.

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
- `AI`: reserved boundary for later server-side AI orchestration

## Local setup

1. Copy `.env.example` to `.env` if needed and update PostgreSQL / Redis credentials.
2. Install dependencies: `composer install`
3. Generate the key: `php artisan key:generate`
4. Run migrations: `php artisan migrate`
5. Load the starter catalog: `php artisan db:seed`
6. Start the API: `composer dev`
7. Start a worker when needed: `composer queue:work`

Useful commands:

- `composer test`
- `composer lint`
- `composer format`

## Implemented so far

- Laravel API bootstrapped in this repository
- Explicit module boundaries under `app/Modules`
- PostgreSQL and Redis defaults configured for local and production-like development
- Sanctum-based register, login, logout, and bearer-token account auth
- Lean core schema for users, profiles, preferences, ingredients, recipes, and moderation foundations
- Authenticated pantry CRUD, ingredient lookup, and deterministic pantry-to-suggestion generation
- Versioned API scaffolding with request validation, resources, and feature tests
- Documentation for Flutter authentication and current backend scope

## Current auth endpoints

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
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

## Step 6 recommendation

Build the template interaction loop next: add lightweight save/bookmark and “cooked this” style endpoints so the app can persist what users act on after opening a suggestion, creating clean first-party feedback signals before any AI or advanced personalization work.
