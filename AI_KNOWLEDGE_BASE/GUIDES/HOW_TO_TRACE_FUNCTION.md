# How to Trace a Function or Method

## There are no global functions

`composer.json` has no `autoload.files` entry, so **nothing is callable without an import**. If you see
a bare function call in this codebase it is a PHP builtin or a Laravel helper (`config()`, `auth()`,
`response()`, `now()`, `logger()`, `__()`).

[INDEXES/FUNCTION_INDEX.md](../INDEXES/FUNCTION_INDEX.md) is empty for exactly that reason, and lists the
static classes that play the role instead ([HELPERS.md](../HELPERS.md)).

## 1. Find the method

[INDEXES/METHOD_INDEX.md](../INDEXES/METHOD_INDEX.md) — **773 methods across 196 classes**, grouped by
file, with line number, visibility, parameters and return type.

```bash
grep -n 'submitAnswer' AI_KNOWLEDGE_BASE/INDEXES/METHOD_INDEX.md
grep -n 'getAvailableCredits' AI_KNOWLEDGE_BASE/INDEXES/METHOD_INDEX.md
```

Open the file **at that line**. Do not read the whole class — `OrganizationController` is 876 lines.

## 2. Find who calls it

The extractor records outbound calls per method in `.data/facts.json` (`classes[].methods[].calls`), so
you can build a caller list without grepping the source:

```bash
php -r '$f=json_decode(file_get_contents(".data/facts.json"),true);
foreach($f["classes"] as $c) foreach($c["methods"] as $m)
  if (in_array("submitAnswer", $m["calls"], true))
    printf("%s::%s()  %s:%d\n", $c["name"], $m["name"], $c["file"], $m["line"]);'
```

For a **static** call the entry is qualified (`Credits::getAvailableCredits`); for an instance call it is
the bare method name, so a common name will over-match. Narrow with the class name.

## 3. Is it reachable at all?

Several methods in this codebase are not. Check
[FEATURE_INDEX.md](../FEATURE_INDEX.md#features-that-exist-in-code-but-are-unreachable) before spending
time on:

`CreditsController::checkDiscountCodeValidity()` (route shadowed) ·
`StripePaymentController::refund()/partialRefund()` (routes commented out) ·
`PaymentController::handleWebhook()` (behind `auth:sanctum`) ·
`SecureImageService::getBatchSecurePlateUrls()/uploadPlateToS3()` ·
everything in `app/Http/Controllers/Auth/` · `EnsureTokenIsValid` · `App\Rules\TurnstileToken` ·
`App\Models\Credit`.

On the SPA side, `src/utils/calculateColorVisionResult.js` and
`src/constants/testDescriptionConstants.js` are dead ([FRONTEND.md](../FRONTEND.md)).

## 4. Watch the return type

The method index prints declared return types. Two to internalise:

```php
Credits::getAvailableCredits(int $userId): int|string      // ← 'Unlimited' is a STRING
Credits::getTotalUserCredit($userId)                       // ← same, untyped
```

Every caller must compare `!== 'Unlimited'` before arithmetic
([CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md)).

## 5. Watch for name collisions

| Similar names, different things |
|---|
| `TestService` · `TestExecutionService` · `TestAssignmentService` · `TestResultService` |
| `Credits` (live model) · `Credit` (near-dead, same table) |
| `PaymentController` (`api/payment/*`) · `StripePaymentController` (`api/stripe/*`, deprecated) |
| `StripeService` (direct SDK) · `StripeProvider` (the interface implementation) |
| `Credits::transactions()` is a **hasOne**, `Transaction::details()` is a hasMany |
| `TestInvitationController::resendUnregisteredInvitation()` vs `PatientController::resendTestLink()` |

## 6. Constants and enums

There are **no native PHP enums**. Enumerated values are class constants
([INDEXES/CONSTANTS.md](../INDEXES/CONSTANTS.md)) or bare strings — the LMS session statuses travel as
plain strings in `routes/api.php`, where a typo produces a filter that matches nothing
([INDEXES/ENUM_INDEX.md](../INDEXES/ENUM_INDEX.md)).

## Worked example — "who spends credits?"

```bash
grep -n 'consume' AI_KNOWLEDGE_BASE/INDEXES/METHOD_INDEX.md | grep CreditConsume
# → CreditConsume::consume()  app/Models/CreditConsume.php:…
```
Then the caller query from step 2 gives two sites:
`TestInvitationController::sendInvitations()` and `TestAssignmentService` (×2).
Read [CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md) and you learn the third fact the code doesn't
say: the two paths record **different `event_type` values** for the same economic event.

**Files read: 0 source files** to answer the question; 1 to make the change.
