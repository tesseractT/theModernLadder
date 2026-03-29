# Backend Architecture Note

Step 1 established the modular monolith foundation. Step 2 builds on that by adding Sanctum-based mobile authentication plus the initial account surface for `register`, `login`, `logout`, `me`, `profile`, and `preferences`.

## Step 1 goal

Step 1 establishes a Laravel modular monolith foundation for a Flutter-facing REST API. The focus is infrastructure, module boundaries, lean domain modeling, and safe defaults for future expansion.

## Module structure

The backend uses `app/Modules/<Module>` boundaries.

- `Domain`: Eloquent models, enums, and future value objects or policies
- `Application`: reserved for actions, jobs, listeners, and orchestration in later steps
- `Http`: controllers, requests, resources, and middleware
- `Routes`: route composition only

Cross-cutting concerns live in `Shared` so the domain modules do not depend on one another through ad hoc helpers.

## Infrastructure defaults

- PostgreSQL is the default database connection.
- Redis is the default cache and queue backend.
- Redis cache and queue databases are separated.
- Sanctum is installed only as the token-auth foundation.
- The API is versioned under `/api/v1`.
- API responses default to JSON, and rate limiting is enabled through Laravel's API limiter backed by Redis.

## Schema decisions

- ULIDs are used across core domain tables for stable external identifiers and better distributed-system ergonomics later.
- Profiles and user preferences are separated from the auth-focused `users` table.
- Ingredients, pairings, substitutions, and recipe templates include lightweight publication state so trust and moderation can grow without reworking the schema.
- Pantry items support both raw entered names and optional normalized ingredient links.
- Contributions and moderation cases are modeled as structured, auditable records rather than implicit flags.
- Contributor reputation is stored as an aggregate table, not derived logic, so scoring rules can evolve later.

## Explicitly deferred

- Recommendation logic, AI calls, and nutrition computation
- Moderation workflow engines and admin dashboards
- Realtime features and notifications delivery
- Advanced token expiration strategy, MFA, password reset, email verification, and social login
- Recipe ingredient line modeling, shopping lists, or meal planning
- Advanced trust scoring, abuse detection, and gamification rules

## Recommended Step 3

Build pantry CRUD next. That gives authenticated users a meaningful first product workflow, lets Flutter persist pantry state, and sets up ingredient normalization without pulling recommendation logic or AI into scope too early.
