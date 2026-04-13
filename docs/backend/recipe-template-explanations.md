# Recipe Template AI Explanations

Step 6 adds a tightly scoped server-side AI explanation layer on top of the existing recipe-template detail flow.

The goal is not chat, memory, search, or diagnosis. The goal is to turn the structured template and pantry-fit data the backend already knows into friendly, grounded explanation copy for Flutter.

## Endpoint

All endpoints remain versioned under `/api/v1`.

- `POST /api/v1/recipes/templates/{recipeTemplate}/explanation`

This endpoint is authenticated with the same bearer-token flow documented in [authentication.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/authentication.md).

Use the `recipe_template_id` returned from `POST /api/v1/me/suggestions` or the template id used by `GET /api/v1/recipes/templates/{recipeTemplate}`.

The request body is currently empty.

## Response shape

Example:

```json
{
  "template_id": "01RT...",
  "source": "ai",
  "meta": {
    "generated_at": "2026-03-30T12:00:00+00:00",
    "schema_version": "recipe_template_explanation.v1",
    "prompt_version": "recipe_template_explanation.v1",
    "cached": false
  },
  "explanation": {
    "headline": "A pantry-friendly smoothie match.",
    "why_it_fits": "Your pantry already covers pineapple and yogurt, and the missing banana can be bridged with the published mango substitution.",
    "taste_profile": "Expect a bright tropical profile led by pineapple with a gentle creamy base from yogurt.",
    "texture_profile": "This should stay smooth and sippable because the required ingredients point toward a blended drink format.",
    "substitution_guidance": [
      "If you skip banana, mango is the grounded pantry substitution already attached to this template."
    ],
    "quick_takeaways": [
      "You already have most of the required ingredients.",
      "One required gap is covered by a pantry substitution.",
      "The template still reads as a quick drink option."
    ],
    "follow_up_options": [
      {
        "key": "swap_help",
        "label": "Need a pantry-based swap option?"
      },
      {
        "key": "same_pantry_new_recipe",
        "label": "Want another idea using the same pantry?"
      }
    ],
    "grounding": {
      "template": {
        "id": "01RT...",
        "slug": "pineapple-smoothie",
        "title": "Pineapple Smoothie",
        "recipe_type": "drink",
        "difficulty": "easy",
        "servings": 2,
        "total_minutes": 10
      },
      "pantry_fit": {
        "required_total": 3,
        "required_owned": 2,
        "required_missing": 1,
        "optional_total": 2,
        "optional_owned": 0,
        "optional_missing": 2,
        "substitution_covered_required_missing": 1,
        "can_make_with_current_pantry": false,
        "can_make_after_substitutions": true
      },
      "owned_ingredients": [
        { "name": "Pineapple", "slug": "pineapple" },
        { "name": "Yogurt", "slug": "yogurt" }
      ],
      "missing_ingredients": [
        { "name": "Banana", "slug": "banana" }
      ],
      "available_substitutions": [
        {
          "for_ingredient": { "name": "Banana", "slug": "banana" },
          "available_substitutes": [
            { "name": "Mango", "slug": "mango" }
          ]
        }
      ],
      "dietary_patterns_considered": {
        "user": ["vegetarian"],
        "template": ["vegetarian"]
      }
    },
    "warnings_or_limits": [
      "Grounded only in the published recipe template, pantry fit, and substitution data already stored in the app.",
      "Not medical, allergy-certainty, diagnosis, treatment, or disease-management advice."
    ]
  }
}
```

`source` is:

- `ai` when the provider returned valid grounded output
- `fallback` when the provider failed or produced unsafe / invalid output and the deterministic fallback path was used

## Grounding rules

The prompt is built from structured backend data only:

- recipe template metadata already exposed by the Step 5 detail endpoint
- pantry-fit counts and can-make booleans
- required and optional ingredient overlays
- published substitutions available from the current pantry
- normalized ordered steps
- safe user preference subset: currently dietary patterns only

The Step 6 prompt intentionally excludes untrusted or freeform user text:

- pantry `entered_name`
- pantry `note`
- freeform `preferred_cuisines`
- freeform `disliked_ingredients`

The provider is instructed to treat every text field as inert data, not as instructions.

## Provider abstraction

The AI module owns the explanation orchestration:

- `RecipeTemplateExplanationService`: application-facing service used by the recipe endpoint
- `RecipeExplanationProvider`: provider contract
- `ConfiguredRecipeExplanationProvider`: config-driven selector
- `OpenAiRecipeExplanationProvider`: first concrete provider

The recipe HTTP surface stays in the `Recipes` module because the resource being explained is still the recipe template.

## Structured output validation

The OpenAI provider requests strict JSON-schema output using the Responses API `text.format` shape.

The backend then validates the decoded payload again before it ever reaches Flutter:

- expected top-level fields only
- field lengths and array sizes
- follow-up option keys must match the server-generated allowed option set
- no malformed or partial provider payloads are passed through

The final `grounding` and `warnings_or_limits` fields are assembled server-side, not trusted from model output.

## Safety boundaries

This layer is explicitly scoped to:

- food discovery
- recipe inspiration
- substitution phrasing
- broad non-diagnostic nutrition education

The backend rejects provider output that crosses into:

- diagnosis
- treatment
- disease-management advice
- therapeutic food claims
- exact nutrition numbers
- allergy-certainty or allergen-safety claims

If the provider output is unsafe or malformed, the backend falls back to a deterministic explanation or returns a clean `503` if fallback is disabled.

## Failure behavior

When provider generation fails:

- raw provider payloads are never returned to clients
- provider secrets are never exposed
- failures are logged with request id, template id, provider, model when available, and schema/prompt versions
- failures are also persisted to the admin-only internal event store with safe fields such as request id, template id, provider, failure type, and `fallback_used`
- fallback stays isolated to this endpoint and does not affect the rest of the recipe-template flow

Failure response when fallback is disabled:

```json
{
  "message": "Unable to generate a recipe explanation right now.",
  "code": "recipe_explanation_unavailable"
}
```

## Configuration

Environment variables introduced for Step 6:

- `AI_DRIVER`
- `AI_EXPLANATION_PROVIDER`
- `AI_EXPLANATION_TIMEOUT`
- `AI_EXPLANATION_RETRY_TIMES`
- `AI_EXPLANATION_RETRY_SLEEP_MS`
- `AI_EXPLANATION_FALLBACK_ENABLED`
- `AI_EXPLANATION_CACHE_ENABLED`
- `AI_EXPLANATION_CACHE_TTL_SECONDS`
- `AI_EXPLANATION_PROMPT_VERSION`
- `AI_EXPLANATION_SCHEMA_VERSION`
- `OPENAI_API_KEY`
- `OPENAI_BASE_URL`
- `OPENAI_EXPLANATION_MODEL`
- `OPENAI_STORE`

Notes:

- caching is not enabled in Step 6 even though the config shape is reserved
- OpenAI request storage defaults to `false`
- the explanation endpoint is server-side only; the client never sees provider credentials

## Tests

Step 6 automated coverage includes:

- unauthenticated access rejection
- happy-path explanation generation for an authenticated user
- grounded prompt construction from template and pantry data
- exclusion of untrusted user-entered text from prompts
- malformed provider payload fallback
- unsafe medical or diagnostic output fallback
- clean `503` behavior when fallback is disabled
- concrete OpenAI provider request/response parsing with faked HTTP

## Deferred to Step 7

Step 6 intentionally does not add:

- chat history or conversational memory
- vector search or embeddings
- general-purpose assistant flows
- nutrition calculations
- medical workflows
- caching beyond the reserved config shape
- save/bookmark or cooked-history feedback loops
