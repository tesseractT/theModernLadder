# Pantry API Guide

Step 3 adds the first authenticated product workflow: canonical ingredient lookup plus user pantry CRUD. Step 4 builds on that pantry state with deterministic suggestions documented in [suggestions.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/suggestions.md).

## Endpoints

All endpoints remain versioned under `/api/v1`.

- `GET /api/v1/ingredients/search?q={query}`
- `GET /api/v1/me/pantry`
- `POST /api/v1/me/pantry`
- `PATCH /api/v1/me/pantry/{pantryItem}`
- `DELETE /api/v1/me/pantry/{pantryItem}`

All Step 3 endpoints require the same bearer token flow documented in [authentication.md](/Users/bennyebere/Desktop/theModernLadder/docs/backend/authentication.md).

## Ingredient lookup

`GET /api/v1/ingredients/search?q=pineapple`

Purpose:

- power the Flutter ingredient picker when adding pantry items
- search canonical ingredient names and aliases
- always return canonical ingredient rows

Optional query params:

- `q`: required search string, minimum 2 characters
- `limit`: optional result cap, default `10`, max `25`

Example response:

```json
{
  "data": [
    {
      "id": "01...",
      "name": "Pineapple",
      "slug": "pineapple",
      "description": "Sweet tropical fruit.",
      "matched_alias": "Fresh Pineapple"
    }
  ]
}
```

Lookup notes:

- canonical names and aliases are searched with a simple SQL `LIKE` pattern
- duplicate alias rows are collapsed into one canonical ingredient result
- only published canonical ingredients are returned

## Pantry item model

Each pantry item stores:

- `user_id`
- `ingredient_id`
- `entered_name`
- `quantity` nullable
- `unit` nullable
- `note` nullable
- `expires_on` nullable
- timestamps
- soft delete timestamp

`entered_name` is currently seeded from the canonical ingredient name at creation time.

## List pantry items

`GET /api/v1/me/pantry`

Returns the authenticated user's active pantry items only, ordered by most recently updated first.

Example response:

```json
{
  "data": [
    {
      "id": "01...",
      "ingredient": {
        "id": "01...",
        "name": "Pineapple",
        "slug": "pineapple",
        "description": "Sweet tropical fruit."
      },
      "entered_name": "Pineapple",
      "quantity": 2.5,
      "unit": "cups",
      "note": "For smoothies",
      "expires_on": "2026-04-15",
      "status": "active",
      "created_at": "2026-03-29T22:00:00+00:00",
      "updated_at": "2026-03-29T22:10:00+00:00"
    }
  ],
  "links": {},
  "meta": {}
}
```

The endpoint uses the existing API pagination conventions and supports `per_page`.

## Add pantry item

`POST /api/v1/me/pantry`

Request:

```json
{
  "ingredient_id": "01...",
  "quantity": 2.5,
  "unit": "cups",
  "note": "For smoothies",
  "expires_on": "2026-04-15"
}
```

Response:

```json
{
  "message": "Pantry item added successfully.",
  "pantry_item": {
    "id": "01...",
    "ingredient": {
      "id": "01...",
      "name": "Pineapple",
      "slug": "pineapple",
      "description": "Sweet tropical fruit."
    },
    "entered_name": "Pineapple",
    "quantity": 2.5,
    "unit": "cups",
    "note": "For smoothies",
    "expires_on": "2026-04-15",
    "status": "active",
    "created_at": "2026-03-29T22:00:00+00:00",
    "updated_at": "2026-03-29T22:00:00+00:00"
  }
}
```

## Duplicate handling

Step 3 uses a single active pantry item per user per canonical ingredient.

Behavior:

- adding an ingredient that is already active in the user's pantry returns `422`
- the validation error is attached to `ingredient_id`
- deleting a pantry item soft-deletes it, so the same canonical ingredient can be added again later

This is intentional for MVP clarity. Merge or quantity-aggregation behavior is deferred.

## Update pantry item

`PATCH /api/v1/me/pantry/{pantryItem}`

Supported fields:

- `quantity`
- `unit`
- `note`
- `expires_on`

The canonical `ingredient_id` is not editable in Step 3.

## Remove pantry item

`DELETE /api/v1/me/pantry/{pantryItem}`

Pantry removal uses the existing soft-delete setup on `pantry_items`. The item is removed from active list responses without being hard-deleted.

## Authorization behavior

- pantry endpoints are authenticated with Sanctum bearer tokens
- all pantry reads and writes are scoped to the authenticated user
- cross-user pantry item update and delete attempts return `404` because the item lookup is owner-scoped

## Deferred to Step 5

- richer recipe-template detail retrieval after a candidate is chosen
- broader curated starter template catalog
- AI explanations
- grocery lists, barcode scanning, analytics, or nutrition calculations
