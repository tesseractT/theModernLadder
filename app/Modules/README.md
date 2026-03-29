# Module Conventions

Each module owns its HTTP entrypoints, domain models, enums, and future application services. Keep cross-module calls thin and explicit.

Recommended layout:

- `Domain`: Eloquent models, enums, policies, value objects
- `Application`: actions, jobs, listeners, orchestration
- `Http`: controllers, requests, resources
- `Routes`: API route composition only

`Shared` contains only cross-cutting primitives that are safe to reuse across modules without leaking business ownership.
