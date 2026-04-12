# Suggestions API Guide

Step 4 adds the first deterministic pantry-to-suggestion layer. It turns the authenticated user's active pantry ingredients into ranked recipe-template candidates without AI or external APIs.

## Endpoint

All endpoints remain versioned under `/api/v1`.

- `POST /api/v1/me/suggestions`

This endpoint uses the same bearer-token flow documented in [authentication.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/authentication.md).

Suggestion candidates can now be opened through [recipe-templates.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/recipe-templates.md) with `GET /api/v1/recipes/templates/{recipeTemplate}`.

## Request shape

Request body fields:

- `goal` optional: one of `drink`, `breakfast`, `snack`, `dessert`, `light_meal`
- `recipe_type` optional: accepted as an alias of `goal`
- `pantry_item_ids` optional: restrict matching to a chosen subset of the authenticated user's pantry items
- `limit` optional: default `5`, max `10`
- `include_substitutions` optional: default `true`

Example:

```json
{
  "goal": "drink",
  "pantry_item_ids": ["01HQ...", "01HR..."],
  "limit": 3,
  "include_substitutions": true
}
```

`pantry_item_ids` must belong to the current authenticated user and must reference active pantry items.

Request normalization:

- `goal` and the alias field `recipe_type` are trimmed and lowercased
- duplicate or blank `pantry_item_ids` are removed before evaluation
- the `request` object in the response echoes the normalized values actually used by the deterministic engine

## Response shape

Example:

```json
{
  "request": {
    "goal": null,
    "limit": 3,
    "include_substitutions": true,
    "pantry_item_ids": ["01HQ...", "01HR..."]
  },
  "pantry": {
    "count": 3,
    "items": [
      {
        "id": "01HQ...",
        "ingredient": {
          "id": "01AA...",
          "name": "Pineapple",
          "slug": "pineapple",
          "description": "Sweet tropical fruit."
        },
        "entered_name": "Pineapple",
        "quantity": 2,
        "unit": "cups",
        "note": null,
        "expires_on": null,
        "status": "active",
        "created_at": "2026-03-30T09:00:00+00:00",
        "updated_at": "2026-03-30T09:00:00+00:00"
      }
    ]
  },
  "candidates": [
    {
      "id": "recipe_template:01RT...",
      "source": {
        "type": "recipe_template",
        "id": "01RT...",
        "slug": "tropical-smoothie"
      },
      "recipe_template_id": "01RT...",
      "suggestion_type": "drink",
      "title": "Tropical Smoothie",
      "summary": "A fruit-forward smoothie template.",
      "score": 134,
      "score_breakdown": {
        "required_match": 120,
        "optional_match": 0,
        "perfect_required_match": 14,
        "goal_match": 0,
        "substitution_coverage": 0,
        "pairing_signal": 0,
        "missing_without_substitution_penalty": 0
      },
      "reason_codes": [
        "matched_required_ingredients",
        "all_required_available"
      ],
      "matched_ingredients": [
        {
          "pantry_item_id": "01HQ...",
          "ingredient": {
            "id": "01AA...",
            "name": "Pineapple",
            "slug": "pineapple"
          },
          "is_required": true
        },
        {
          "pantry_item_id": "01HR...",
          "ingredient": {
            "id": "01AB...",
            "name": "Banana",
            "slug": "banana"
          },
          "is_required": true
        },
        {
          "pantry_item_id": "01HS...",
          "ingredient": {
            "id": "01AC...",
            "name": "Yogurt",
            "slug": "yogurt"
          },
          "is_required": true
        }
      ],
      "missing_ingredients": [],
      "substitutions": [],
      "pairing_signals": [],
      "preference_compatibility": {
        "is_compatible": true,
        "dietary_patterns_applied": [],
        "template_dietary_patterns": ["vegetarian"],
        "disliked_ingredients_checked": []
      },
      "match_summary": {
        "required_total": 3,
        "required_matched": 3,
        "optional_total": 0,
        "optional_matched": 0,
        "missing_required_count": 0,
        "substitution_covered_missing_count": 0,
        "pairing_signal_count": 0
      }
    }
  ],
  "meta": {
    "count": 1
  }
}
```

If no useful candidates are available, the API returns an empty `candidates` array plus a short `message`.

## How candidates are generated

- active pantry items are loaded for the authenticated user
- pantry items are matched against canonical ingredient IDs, not freeform `entered_name`
- published recipe templates are evaluated against their required and optional template ingredients
- optional `goal` filters templates by `recipe_type`
- dietary-pattern preferences can exclude templates that do not explicitly declare compatibility
- required disliked ingredients exclude a template
- published substitutions can cover missing required ingredients when the user already has the substitute ingredient in their pantry
- published pairings can add small support signals for partially matched templates

## Scoring model

Step 4 uses a transparent integer score:

- `+40` for each matched required ingredient
- `+8` for each matched optional ingredient
- `+14` when all required ingredients are already available
- `+10` when a filtered goal matches the candidate template type
- `+8` for each missing required ingredient covered by an available pantry substitution
- `+3` for each pairing signal, capped per candidate
- `-18` for each missing required ingredient without a substitution

Candidates are sorted by:

1. total score descending
2. required ingredients matched descending
3. missing required ingredients ascending
4. title ascending

## Preference handling in Step 4

The deterministic engine currently uses:

- `dietary_patterns`
- `disliked_ingredients`

`preferred_cuisines` is intentionally not used yet because recipe templates do not carry cuisine metadata in Step 4.

## Template data assumptions

Recipe templates participate in suggestions only when they have:

- published status
- at least one required template ingredient
- a curated `recipe_type` when goal filtering is needed

Step 4 tests build a tiny starter catalog around templates such as smoothie, salsa, bowl, and yogurt mix. A broader curated template catalog is deferred.

## Deferred to Step 6

- user save/bookmark or cooked-history interactions after detail view
- nutrition calculations
- AI explanations or natural-language generation
- advanced ranking, personalization, or recommendation learning
