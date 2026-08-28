# API — Shape and Conventions

The exhaustive table is generated: [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md)
(**176 endpoints**) and [INDEXES/PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md)
(**20 of 176 endpoints are public**). This page is the shape.

## Base

All API routes are prefixed **`api/`** (applied by `withRouting(api: routes/api.php)`), plus two
non-`api/` routes in `routes/web.php`. The SPA is served at `/app`, so a full URL looks like
`https://host/api/tests/perform`.

## Endpoints by area

| Prefix | Count | Guard | Notes |
|---|---|---|---|
| `api/tests/*`, `api/test-session/*`, `api/test-result/*` | ~20 | `FlexibleAuthMiddleware` | the test player's surface |
| `api/tests`, `api/tests/{id}/…` (resources) | ~35 | `auth:sanctum` | test authoring: conditions, answers, sections, plates |
| `api/users`, `api/user/*`, `api/profile`, `api/password/*` | ~20 | mixed | see [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) |
| `api/patients` (resource) | 7 | `FlexibleAuthMiddleware` | ⚠️ unscoped — [S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping) |
| `api/organizations`, `api/organization/*` | ~12 | mixed | `verify-signature` is public |
| `api/credits`, `api/user/credits`, `api/patient-tests/*/revoke-credit` | ~9 | `auth:sanctum` | |
| `api/payment/*` | 5 | `auth:sanctum` | the current payment surface |
| `api/stripe/*` | 5 | **public** | ⚠️ deprecated and broken — [BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) |
| `api/discount-codes/*` | 10 | `auth:sanctum` | includes `GET code-available` (inline uniqueness check; `withTrashed()` on the indexed tree — a deleted code's name stays reserved. Becomes **live rows only** if `ws-392` merges) |
| `api/test-invitations/*`, `api/test-invitation/*`, `api/test/*` | ~8 | mixed | `test-invitations/send` is now `auth:sanctum` — [S-13](SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time) fixed 2026-08-26. `verify-code` / `check-validity` / `test/resume` stay public by design |
| `api/admin/lms/*` | 8 | `auth:sanctum` | no role check |
| `api/reports/*`, `api/super-admin/dashboard` | 4 | `auth:sanctum` | no role check |
| `api/dropdown/*`, `api/countries-with-states`, `api/restricted-ips`, `api/price-details` | ~12 | mixed | reference data |
| `api/test-email-templates/*`, `api/user-email-template` | 6 | `auth:sanctum` | |
| `api/contact` | 1 | `auth:sanctum` + `throttle:10,1` | the **only** throttled route |

## Request conventions

- **JSON in, JSON out.** `Content-Type: application/json`, `Accept: application/json`.
- **Auth**: `Authorization: Bearer <token>`. Session-token routes also accept `X-Session-Token`.
- **Validation**: a FormRequest where one exists ([REQUESTS.md](REQUESTS.md)); otherwise inline.
- **Pagination**: `?limit=` (default 10) plus `?sort_by=`, `?sort_order=`, `?search=`. Sort fields are
  allow-listed per controller — an unknown `sort_by` silently falls back to `created_at`.
- **Search** goes through the `Searchable` trait ([HELPERS.md](HELPERS.md)).

## Response conventions — and the eight shapes

The intended envelope:

```json
{ "success": true, "status_code": 200, "message": "…", "data": { } }
{ "success": false, "status_code": 422, "message": "…", "errors": { } }
```

But **eight different shapes** are actually emitted, from `ApiResponse`, the exception handler, three
middleware and several hand-built controller responses. The full table is in
[ERROR_HANDLING.md](ERROR_HANDLING.md#response-shapes). A client cannot key on a single field; the SPA
checks several.

**New endpoints must use `ApiResponse`.**

## Status codes you will actually see

| Code | When |
|---|---|
| 200 / 201 | success |
| 401 | no/invalid token, expired session, restricted IP with a token |
| 402 | insufficient credits |
| 403 | restricted IP without a token — **and almost nothing else** |
| 409 | LMS session status mismatch |
| 410 | expired resume token |
| 422 | validation failure |
| **500** | everything else, **including what should be 403, 404, 405 and 429** |

That last row is the one to internalise ([ERROR_HANDLING.md](ERROR_HANDLING.md)).

## Versioning

**There is none.** No `api/v1`, no `Accept` versioning, no deprecation headers. The `api/stripe/*` group
is the de-facto "old version" and is still live and public. Any breaking change to a response shape is a
coordinated deploy with `TCV-Frontend` ([FULLSTACK_MAP.md](FULLSTACK_MAP.md)).

## Documentation

No OpenAPI spec, no Swagger, no Postman collection in the repo. The generated
[API_ENDPOINT_INDEX](INDEXES/API_ENDPOINT_INDEX.md) is the closest thing to an API reference — and
unlike a hand-written spec, it cannot drift, because it is re-derived from the route file.
