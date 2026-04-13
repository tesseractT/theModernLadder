# Backend Architecture Note

Step 1 established the modular monolith foundation. Step 2 added Sanctum-based mobile authentication plus the initial account surface for `register`, `login`, `logout`, `me`, `profile`, and `preferences`. Step 3 added authenticated ingredient lookup and pantry CRUD as the first real product workflow. Step 4 added deterministic pantry-to-suggestion generation. Step 5 added pantry-aware recipe-template detail plus a rerunnable starter catalog. Step 6 added a grounded server-side AI explanation layer. Step 7 activated the first real contribution and moderation workflow foundation. Step 8 added the first internal admin/ops visibility layer on top of those moderation, audit, and AI-failure signals. Step 10 now adds the first private retention loop for favorites, saved suggestions, bounded recent history, and planner-lite recipe follow-through.

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
- Moderation action history is now append-only so reports and moderator decisions stay reviewable even when the current contribution state changes later.
- Internal admin visibility now uses a small append-only event store rather than parsing flat application logs at request time.

## Current write-path pattern

The most important live write endpoints now follow the same boundary shape:

- `FormRequest` owns normalization and validation only
- module-local payload DTOs carry validated input into application services
- controllers stay thin: authorize, delegate, respond
- services orchestrate persistence and domain-facing decisions without depending on HTTP requests
- resources format responses, including the top-level suggestions payload

This pattern is currently applied to pantry writes, current-user profile/preferences updates, deterministic suggestion generation, and the new retention/planner write paths.

## Explicitly deferred

- AI-driven recommendation logic beyond the grounded explanation layer
- external recipe retrieval
- automated moderation workflow engines, scoring heuristics, and full admin dashboards
- Realtime features and notifications delivery
- Advanced token expiration strategy, MFA, password reset, email verification, and social login
- shopping lists, pantry-gap automation, full meal planning, grocery workflows, or barcode ingestion
- Advanced trust scoring, abuse detection, and gamification rules
- nutrition calculations
- conversational memory, vectors, or general-purpose AI assistance

## Recommended Next Step

Use the new first-party retention signals carefully without changing the deterministic ranking boundary. The best next slice is a small resurfacing layer that helps Flutter highlight favorites, saved suggestions, and active plan items around the existing suggestion/detail experience while keeping privacy controls straightforward.
