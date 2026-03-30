# Recipe Template Detail Guide

Step 5 adds the recipe-template follow-through layer so Flutter can open a suggestion candidate into a usable, pantry-aware detail view.

## Endpoint

All endpoints remain versioned under `/api/v1`.

- `GET /api/v1/recipes/templates/{recipeTemplate}`

This endpoint is authenticated with the same bearer-token flow documented in [authentication.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/authentication.md).

Use the `recipe_template_id` returned from `POST /api/v1/me/suggestions` when opening a candidate.

## Response shape

Example:

```json
{
  "template": {
    "id": "01RT...",
    "slug": "pineapple-smoothie",
    "title": "Pineapple Smoothie",
    "recipe_type": "drink",
    "difficulty": "easy",
    "summary": "A creamy pineapple smoothie built for quick breakfasts or snacks.",
    "dietary_patterns": ["vegetarian"],
    "servings": 2,
    "prep_minutes": 10,
    "cook_minutes": 0,
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
  "ingredients": {
    "required": [
      {
        "position": 1,
        "ingredient": {
          "id": "01IG...",
          "name": "Pineapple",
          "slug": "pineapple",
          "description": "Sweet tropical fruit with bright acidity."
        },
        "is_required": true,
        "is_owned": true,
        "pantry_item_id": "01PI...",
        "substitutions": []
      }
    ],
    "optional": []
  },
  "steps": [
    {
      "position": 1,
      "instruction": "Add pineapple, banana, and yogurt to a blender."
    }
  ],
  "substitutions": [
    {
      "for_ingredient": {
        "id": "01IG...",
        "name": "Banana",
        "slug": "banana",
        "description": "Creamy fruit that adds sweetness and body."
      },
      "available_substitutes": [
        {
          "pantry_item_id": "01PJ...",
          "ingredient": {
            "id": "01IG...",
            "name": "Mango",
            "slug": "mango",
            "description": "Soft tropical fruit with a rich sweetness."
          },
          "note": "Mango keeps the smoothie sweet and thick."
        }
      ]
    }
  ]
}
```

## Pantry fit behavior

The detail endpoint does not rerun the Step 4 suggestion engine. It applies a direct pantry overlay to a single published recipe template:

- active pantry items are loaded for the authenticated user
- template ingredients are matched by canonical ingredient ID
- each ingredient is marked as owned or missing
- required and optional ingredients remain separated
- available pantry substitutions are attached to missing ingredients when published substitution rules exist

`can_make_with_current_pantry` is true when no required ingredients are missing.

`can_make_after_substitutions` is true when every missing required ingredient is covered by an available pantry substitution, or when nothing is missing in the first place.

## Structured steps

Step 5 adds normalized ordered template steps through `recipe_template_steps`.

The detail endpoint returns:

- `position`
- `instruction`

If a template does not yet have normalized steps, the backend falls back to splitting the legacy `instructions` text into ordered steps.

## Starter catalog workflow

Step 5 adds a small version-controlled starter catalog through [starter_recipe_templates.php](/Users/bennyebere/Desktop/theModernLadder/database/seeders/data/starter_recipe_templates.php) and [StarterRecipeTemplateCatalogSeeder.php](/Users/bennyebere/Desktop/theModernLadder/database/seeders/StarterRecipeTemplateCatalogSeeder.php).

The catalog currently seeds:

- canonical ingredients used by the starter templates
- published recipe templates
- required and optional template ingredients
- ordered steps
- a small set of substitutions
- a small set of pairings used by the deterministic suggestion layer

Load it locally with either:

```bash
php artisan db:seed
```

or

```bash
php artisan db:seed --class="Database\\Seeders\\StarterRecipeTemplateCatalogSeeder"
```

The seeder is intentionally rerunnable and updates existing starter records instead of duplicating them.

## Visibility rules

- the authenticated detail endpoint only exposes published recipe templates
- unpublished templates return `404`
- pantry overlay data is always scoped to the current authenticated user

## Deferred to Step 6

- user save/bookmark or cooked-history actions on templates
- richer feedback loops from template usage
- AI explanations
- nutrition calculations
- community recipe publishing or moderation flows
