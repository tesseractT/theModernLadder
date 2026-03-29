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

See [docs/backend/architecture.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/architecture.md) for the foundation architecture note and [docs/backend/authentication.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/authentication.md) for Flutter auth usage.

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

## Implemented so far

- Laravel API bootstrapped in this repository
- Explicit module boundaries under `app/Modules`
- PostgreSQL and Redis defaults configured for local and production-like development
- Sanctum-based register, login, logout, and bearer-token account auth
- Lean core schema for users, profiles, preferences, ingredients, recipes, and moderation foundations
- Versioned API scaffolding with request validation, resources, and feature tests
- Documentation for Flutter authentication and current backend scope

## Current auth endpoints

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`
- `PATCH /api/v1/me/profile`
- `PATCH /api/v1/me/preferences`

## Step 3 recommendation

Implement pantry CRUD next so authenticated users can add pantry items, normalize them against ingredients, and give the Flutter client its first real product workflow on top of the new account/auth foundation.
