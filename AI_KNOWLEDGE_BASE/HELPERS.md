# Helpers

## ☠️ There are **no** global functions

`composer.json` has no `autoload.files` entry, so nothing in this codebase is callable without an
import. [INDEXES/FUNCTION_INDEX.md](INDEXES/FUNCTION_INDEX.md) is empty by design — that emptiness is
the finding.

If you are coming from a codebase with an autoloaded `helpers.php`, do not reach for one. Everything is
a class you import.

## What plays the role instead

| Class | File | What it is |
|---|---|---|
| `App\Helpers\ApiResponse` | `app/Helpers/ApiResponse.php` | ⭐ the JSON envelope — `success()` / `error()` |
| `App\Helpers\TestHelper` | `app/Helpers/TestHelper.php` | test-flow helpers (`getEyeLabel`, `getEyeInstruction`, `extractEyeFromSectionInstruction`) |
| `App\Support\HttpStatus` | `app/Support/HttpStatus.php` | status-code constants |
| `App\Support\TestConstants` | `app/Support/TestConstants.php` | `DEFAULT_TEST_TITLE = 'Adult Diagnostic'` |
| `App\Traits\Searchable` | `app/Traits/Searchable.php` | the shared `?search=` scope |

## `ApiResponse`

```php
ApiResponse::success(int $statusCode, string $messageKey, $data = null, array $meta = []): JsonResponse
ApiResponse::error  (int $statusCode, string $messageKey, $errors = null): JsonResponse
```

`$messageKey` goes through `__()` against `resources/lang/en/api.php` (**78 keys**). A key that doesn't
exist renders as the literal key string — which is how the typo'd `api.resticted` reaches clients today.

Output shape: `{success, status_code, message}` plus `data` or `errors` when non-null.

Two things to know:
- **`$meta` is accepted and then ignored** — it never reaches the response. Don't pass it expecting
  output.
- **The signature has a required parameter after an optional one** (`int $statusCode = 200` before
  `string $messageKey`). Legal but deprecated in PHP 8; always pass both, always as
  `ApiResponse::success(HttpStatus::OK, 'api.key')`.

> **`ws-402` (merged 2026-09-07) adds a trailing `array $replace = []` to both methods**, passed to
> `__($messageKey, $replace)`, so a message can interpolate `:placeholders`. It is the *last* parameter
> on both — on `success()` that means the dead `$meta` sits in front of it, so an interpolating call has
> to pass `[]` for meta first:
> ```php
> ApiResponse::success(HttpStatus::OK, 'api.credits_partially_revoked', $data, [], ['revoked' => 6, 'used' => 4]);
> ```
> Its only caller today is `CreditsController::destroy()` — see
> [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md). The branch also adds 3 keys to `api.php`
> (`credits_deleted`, `credits_partially_revoked`, `credits_already_used`), taking the count to 81.

## `HttpStatus`

`OK` `CREATED` `ACCEPTED` `NO_CONTENT` · `BAD_REQUEST` `UNAUTHORIZED` `FORBIDDEN` `NOT_FOUND`
`CONFLICT` `UNPROCESSABLE` · `SERVER_ERROR`.

Use these, never a bare integer — though note several controllers still pass literals
(`ApiResponse::success(200, …)` in `TestController`). Match the constant style in new code.

## `TestHelper`

Three static methods: `getEyeLabel(string)`, `getEyeInstruction(string)`,
`extractEyeFromSectionInstruction(?string)`.

☠️ The last one **parses the eye out of free text** stored in `test_sections.section_instruction`:

```php
preg_match('/Eye:\s*(OU|OD|OS)/i', $instruction, $matches)
```

So the literal substring `Eye: OS` (or `OD` / `OU`) inside an authored section instruction is
**load-bearing data**. An admin rewording a section instruction can silently change — or erase — which
eye that section reports in `getSessionDetails()` and `getSectionPlatesWithProgress()`. There is no
validation on the instruction text.

## `Searchable`

The trait behind `?search=` on list endpoints. Used by `Credits`, `DiscountCode`, `User`, `Patient`.
Signature in use:

```php
$query->search($search, ['credits', 'expiry_date'], ['transactions' => ['stripe_transaction_id']]);
//              term      own columns                related columns
```

Reuse it instead of hand-writing `where LIKE` — it already handles the relation case.

## Client-side helpers

`TCV-Frontend/src/utils/` is the equivalent surface there: `misc.js` (`lazyWithRetry`, `getAllowedRoutes`),
`dateUtils.js`, `formUtils.js`, `testUtils.js`, `validation.js`, `columns/` and `validationSchema/`.

`testUtils.js` is the SPA's label-mapping single source of truth — `GENDER_OPTIONS` (with
`getGenderLabel()` / `getGenderValue()` derived from it), `getEyeTestedLabel()`, `formatTestCopy()`,
`shouldHideTestEyeSelector()`. Its gender list must agree with `Patient::GENDERS` on the backend
([PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md)).

☠️ `src/utils/calculateColorVisionResult.js` is **dead** — the diagnosis moved to the backend
([FULLSTACK_MAP.md](FULLSTACK_MAP.md)).
