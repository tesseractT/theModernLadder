# The Modern Ladder Backend

Production-grade Step 1 backend foundation for a mobile-first food discovery platform built with Laravel, PostgreSQL, Redis, and an API-first modular monolith architecture.

## Product guardrails

- Food discovery, recipe suggestion, substitutions, pairing, pantry, and general nutrition education.
- Not a medical diagnosis, treatment, or disease-management system.
- No sensitive health-condition modeling in the Step 1 schema.
- User-generated content is prepared for moderation and auditability from the start.

## Stack

- Laravel 13
- PostgreSQL as the primary database
- Redis for cache and queues
- Sanctum installed as the minimal token-auth foundation
- REST API designed for a Flutter client

## Module structure

See [docs/backend/architecture.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/architecture.md) for the full Step 1 architecture note.

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
5. Start the API: `composer dev`
6. Start a worker when needed: `composer queue:work`

Useful commands:

- `composer test`
- `composer lint`
- `composer format`

## Step 1 deliverables

- Laravel API bootstrapped in this repository
- Explicit module boundaries under `app/Modules`
- PostgreSQL and Redis defaults configured for local and production-like development
- Sanctum installed only as auth groundwork
- Lean core schema for the first set of bounded entities
- Basic API scaffolding with versioned routes, requests, resources, and tests
- Documentation for what exists now and what belongs in Step 2

## Step 2 recommendation

Implement the authentication and onboarding slice next: issue and revoke Sanctum tokens, create profile completion flows, and expose authenticated `me`, preferences, and pantry management endpoints behind the new module boundaries.
