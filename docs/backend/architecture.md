# Backend Architecture Note

Step 1 established the modular monolith foundation. Step 2 added Sanctum-based mobile authentication plus the initial account surface for `register`, `login`, `logout`, `me`, `profile`, and `preferences`. Step 3 added authenticated ingredient lookup and pantry CRUD as the first real product workflow. Step 4 added deterministic pantry-to-suggestion generation. Step 5 added pantry-aware recipe-template detail plus a rerunnable starter catalog. Step 6 now adds a grounded server-side AI explanation layer on top of that structured flow.

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
- Recipe templates now use a minimal `recipe_template_ingredients` relation plus lightweight `recipe_type` and `dietary_patterns` metadata so suggestions stay deterministic and inspectable.
- Recipe templates now also expose ordered `recipe_template_steps` and a lightweight `difficulty` field so suggestion candidates can open into usable detail screens without introducing a full editorial CMS.
- Pantry items support both raw entered names and optional normalized ingredient links.
- Contributions and moderation cases are modeled as structured, auditable records rather than implicit flags.
- Contributor reputation is stored as an aggregate table, not derived logic, so scoring rules can evolve later.

## Explicitly deferred

- AI-driven recommendation logic beyond the grounded explanation layer
- external recipe retrieval
- Moderation workflow engines and admin dashboards
- Realtime features and notifications delivery
- Advanced token expiration strategy, MFA, password reset, email verification, and social login
- Save/bookmark flows, cooked-history tracking, shopping lists, meal planning, grocery workflows, or barcode ingestion
- Advanced trust scoring, abuse detection, and gamification rules
- nutrition calculations
- conversational memory, vectors, or general-purpose AI assistance

## Recommended Step 7

Build the template interaction loop next. The backend now supports suggestion generation, pantry-aware template detail, and grounded explanation copy, so the best next step is to add save/bookmark and lightweight “cooked this” interactions that capture what users actually do with those templates.
