# Authentication Guide

Step 2 adds mobile-friendly bearer-token authentication with Laravel Sanctum.

## Base URL

All endpoints remain versioned under `/api/v1`.

## Auth endpoints

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/logout/all`
- `GET /api/v1/me`
- `PATCH /api/v1/me/profile`
- `PATCH /api/v1/me/preferences`

Authenticated product endpoints such as pantry CRUD and `POST /api/v1/me/suggestions` use the same bearer-token flow.
The Step 5 detail endpoint `GET /api/v1/recipes/templates/{recipeTemplate}` uses that same token pattern.

## Token flow for Flutter

1. Register or log in with the auth endpoints.
2. Store the returned token securely on-device, for example with `flutter_secure_storage`.
3. Send the token on authenticated requests using the `Authorization` header:

```http
Authorization: Bearer {token}
Accept: application/json
```

4. Call `POST /api/v1/auth/logout` with the same token to revoke only the current access token.
5. Call `POST /api/v1/auth/logout/all` when the current user needs to revoke every active token issued to their account.

Sanctum is being used in personal-access-token mode for the mobile MVP. Cookie or SPA auth is not required for Flutter.

## Security notes for clients

- auth endpoints and authenticated API responses now send `Cache-Control: no-store, private`
- API responses also include `X-Request-Id`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: no-referrer`
- clients should still treat the bearer token as a secret and store it only in OS-backed secure storage

## Role foundation

The backend now stores a lightweight internal role on each user:

- `user`
- `moderator`
- `admin`

Notes:

- new and existing accounts default safely to `user`
- role checks are currently server-side only and are intentionally not exposed in the mobile auth payload
- the foundation is meant for future moderation and admin prompts, not a full RBAC system in this step

## Register

Request:

```json
{
  "name": "Casey Morgan",
  "email": "casey@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "device_name": "iphone-15"
}
```

Response:

```json
{
  "message": "Registration completed successfully.",
  "token": "1|plain-text-token",
  "token_type": "Bearer",
  "user": {
    "id": "01...",
    "email": "casey@example.com",
    "status": "active",
    "email_verified_at": null,
    "last_seen_at": "2026-03-29T22:00:00+00:00",
    "created_at": "2026-03-29T22:00:00+00:00",
    "updated_at": "2026-03-29T22:00:00+00:00",
    "profile": {
      "display_name": "Casey Morgan",
      "bio": null,
      "locale": "en",
      "timezone": null,
      "country_code": null,
      "created_at": "2026-03-29T22:00:00+00:00",
      "updated_at": "2026-03-29T22:00:00+00:00"
    },
    "preferences": {
      "dietary_patterns": [],
      "preferred_cuisines": [],
      "disliked_ingredients": [],
      "measurement_system": "metric"
    }
  }
}
```

Notes:

- `name` is used to seed `profile.display_name`.
- `device_name` is optional. If omitted, the backend uses `flutter-mobile`.

## Login

Request:

```json
{
  "email": "casey@example.com",
  "password": "Password123!",
  "device_name": "iphone-15"
}
```

Response matches the register shape and issues a new personal access token.

Invalid credentials return Laravel validation-style errors without revealing whether the email exists.

Login protection now has two explicit layers:

- route throttle: `POST /api/v1/auth/login` is still protected by the named Laravel route limiter and returns a safe `429` JSON response when the request ceiling is exceeded
- credential lockout: repeated failed login attempts for the same normalized `email + IP` pair still return a validation-style `422` response on `email`

Relevant env/config values:

- `API_AUTH_LOGIN_RATE_LIMIT_PER_MINUTE`
- `API_AUTH_LOGIN_CREDENTIAL_MAX_ATTEMPTS`
- `API_AUTH_LOGIN_CREDENTIAL_DECAY_SECONDS`

## Current user

`GET /api/v1/me` returns the authenticated user plus profile and food preference payload.

Protected product routes now also reject suspended users even if they still hold an older Sanctum token.

## Profile updates

`PATCH /api/v1/me/profile`

Supported fields in Step 2:

- `display_name`
- `bio`
- `locale`
- `timezone`
- `country_code`

Example:

```json
{
  "display_name": "Casey",
  "locale": "en-GB",
  "timezone": "Europe/London",
  "country_code": "GB"
}
```

Only provided profile keys are updated. Nullable fields such as `bio`, `timezone`, and `country_code` can be cleared by sending `null`.

## Preference updates

`PATCH /api/v1/me/preferences`

Supported fields in Step 2:

- `dietary_patterns`
- `preferred_cuisines`
- `disliked_ingredients`
- `measurement_system`

Example:

```json
{
  "dietary_patterns": ["vegetarian"],
  "preferred_cuisines": ["Japanese", "Levantine"],
  "disliked_ingredients": ["anchovy"],
  "measurement_system": "metric"
}
```

This step intentionally keeps preferences lightweight and food-focused. No medical-condition, diagnosis, treatment, or disease-management data is modeled.

Only provided preference keys are updated. Omitted keys keep their previous stored values.

## Token revocation

`POST /api/v1/auth/logout`

- requires the current bearer token
- revokes only the current Sanctum token
- keeps other active tokens intact

`POST /api/v1/auth/logout/all`

- requires any currently valid bearer token for the user
- revokes every active Sanctum token for that user, including the token used for the request
- returns success for the current request, but previously issued tokens cannot be reused afterward

## Standardized auth errors

Unauthenticated API requests now return:

```json
{
  "message": "Authentication required.",
  "code": "unauthenticated"
}
```

Authenticated but forbidden API requests now return:

```json
{
  "message": "You do not have permission to perform this action.",
  "code": "forbidden"
}
```

The existing `X-Request-Id` behavior still applies to these responses.

## Deferred

- Email verification
- Password reset
- MFA or OTP
- Social login
- Token expiration and device/session management UX beyond simple current-user revoke-all support
- Account deletion
- Recipe recommendation, moderation, notifications, and AI flows
