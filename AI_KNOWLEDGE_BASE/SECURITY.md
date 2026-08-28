# Security — Posture and Known Gaps

> **What this is.** Findings from *reading* TCV-Backend's auth, routing, session and payment paths at
> `85586469`, re-checked against `develop` at `26ba2022` (2026-08-27). No exploit was attempted and no
> pen test was run. Each finding names the file and line it
> came from so you can re-verify it in one read. Treat severities as this document's judgement, not a
> customer-facing rating.

Findings carry stable `S-nn` IDs so other docs can point at them without restating detail.

---

## What is done well

Worth knowing, so you don't "fix" something that is already correct:

- **Passwords** — `bcrypt` via the `'hashed'` cast, and `PasswordRule::min(8)->mixedCase()->numbers()->symbols()`
  on both reset and change paths. ⚠️ **`->uncompromised()` is on the change path only**
  (`ChangePasswordRequest`); it is commented out in `AuthController::setOrResetPassword()`, so a breached
  password rejected on the profile page is still accepted through the emailed reset link
  ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md)).
- **LMS session tokens are stored hashed.** `LmsLaunchService::createSession()` generates
  `bin2hex(random_bytes(32))` and stores only `hash('sha256', $raw)`; `FlexibleAuthMiddleware` hashes the
  presented token before lookup. A DB leak does not yield usable session tokens.
- **`unique_test_id` is a UUIDv4** (`Str::uuid()` in `TestAssignmentService`), not a sequential id.
- **Email enumeration is handled on the resend paths.** `resendEmailVerificationLink()` and
  `resendVerificationByToken()` both return a fixed neutral response regardless of whether the address
  exists — deliberate, with a comment saying so. (`login()` and `sendResetLinkEmail()` do **not**; see `S-07`.)
- **Password change revokes all tokens** (`AuthController::changePassword()` → `$user->tokens()->delete()`).
- **Test plates are private on S3** and served only through short-lived pre-signed URLs.
- **Cloudflare Turnstile** is wired as a validation rule (`App\Rules\TurnstileToken`) for bot defence.
- **Sanctum tokens expire after 15 minutes** — a short window by any standard.

---

## Findings

### S-01 — Public registration accepts `usertype: 1` (SUPER_ADMIN)

**Severity: critical.**

`POST /api/register` is registered at the top of `routes/api.php` with **no middleware**
([PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md)). It is validated by
[`UserRequest`](../../TCV-Backend/app/Http/Requests/UserRequest.php), whose rules are:

```php
'usertype'       => 'required|integer|in:1,2,4',   // 1 = SUPER_ADMIN
'account_status' => 'required|in:active,inactive,suspended',
```

Nothing restricts *who* may request which value, and `AuthController::register()` passes both straight
into `User::create()`. An anonymous caller can therefore create an **active super-admin account**.

The only friction is that `register()` hard-codes `'email_verified' => 'no'`, and `login()` refuses
unverified accounts — but the attacker controls the inbox the verification link is sent to.

**Fix shape:** force `usertype` and `account_status` server-side on the public path, and keep the
caller-supplied values only for the authenticated admin `POST /api/users` path. Because `UserRequest` is
shared between both, splitting it (or gating on `$this->user()`) is the change.

### S-13 — Public `test-invitations/send` spends any user's credits, 500 emails at a time

**Severity: critical.** — **FIXED 2026-08-26** (`c6beafb8`, ws-371; on `develop` since `26ba2022`).

**What the fix did:** the route moved inside the `auth:sanctum` group in `routes/api.php`, and
`sendInvitations()` dropped `user_id` from its validation rules entirely — the owner is now
`$request->user()`. A body-supplied `user_id` is no longer honoured for anyone, super-admin included.
The `is_resend` branch went with it, so a resend now takes the same credit check as a first send
instead of skipping it. The description below is kept for history.

---

`POST /api/test-invitations/send` was registered at the top of `routes/api.php` with **no middleware**.
[`TestInvitationController::sendInvitations()`](../../TCV-Backend/app/Http/Controllers/TestInvitationController.php)
then validates:

```php
'user_id'  => 'required',                      // ← whose credits to spend, from the body
'emails'   => 'required|array|min:1|max:500',
'test_id'  => 'required|exists:tests,id',
```

`user_id` is taken from the request body by an unauthenticated caller. The method reads that user's
balance with `Credits::getAvailableCredits($validated['user_id'])`, sends up to **500** invitation
emails, and records the spend with `CreditConsume::consume(...)` against them.

It also calls `set_time_limit(0)` first, so there is no execution-time ceiling on the loop.

Two distinct impacts: **credit theft** (drain an arbitrary account's balance) and **mail abuse** (send
branded test invitations to 500 arbitrary addresses per request, attributed to a real customer).

**Fix shape (as applied):** the route moved inside the `auth:sanctum` group and `user_id` now comes
from `$request->user()`. The shipped fix went further than this sketch — it removed the body-supplied
`user_id` outright rather than keeping it for super-admins.

### S-02 — Test-session endpoints never check that the caller owns the test

**Severity: high.**

`FlexibleAuthMiddleware` proves the caller holds *some* valid session. It merges `test_session_id` /
`lms_session_id` / `org_id` / `patient_id` into the request — but the controllers then take
`unique_test_id` **from the URL** and never compare it to the session:

| Endpoint | Method | Ownership check |
|---|---|---|
| `GET api/test-session/{unique_test_id}` | `TestController::getTestSession()` | none |
| `GET api/test-session/{unique_test_id}/section/{section_id}/plates` | `getSectionPlates()` | none |
| `GET api/test-session/{unique_test_id}/plate/{test_answer_id}/url` | `getPlateUrl()` | plate↔test only |
| `GET api/test-result/{unique_test_id}` | `getTestResult()` | none |
| `POST api/tests/perform` | `performTest()` → `TestExecutionService::submitAnswer($testAnswerId, …)` | none |

`submitAnswer()` resolves the answer by `TestAnswer::findOrFail($testAnswerId)` alone — an integer,
not a UUID.

**Practically:** any holder of any valid session token can read another patient's result, or submit
answers into another patient's test, given the id. UUIDs make `unique_test_id` unguessable; the
**integer `test_answer_id` in `performTest` is not**. The UUID is doing authorisation's job.

**Fix shape:** derive the permitted `unique_test_id` from the session in `FlexibleAuthMiddleware`
(it already loads `LmsSession->unique_test_id`) and assert it in the service, not the controller.

### S-14 — `patients/{id}` show/update/destroy have no ownership scoping

**Severity: high** — this one exposes patient data (name, DOB, email, zipcode, gender).

`Route::resource('patients', PatientController::class)` sits inside the **`FlexibleAuthMiddleware`**
group, so all four token tiers reach it — including a bare invitation `TestSession`.

[`PatientController`](../../TCV-Backend/app/Http/Controllers/PatientController.php) scopes only `index()`:

```php
public function index()   { $patients = Patient::where('user_id', $user->id)->get(); }   // ✅ scoped
public function show($id) { $patient = Patient::findOrFail($id); }                       // ❌ any id
public function update(PatientUpdateRequest $request, $id) { … }                         // ❌ any id
public function destroy($id) { $patient = Patient::findOrFail($id); $patient->delete(); }// ❌ any id
```

`patients.id` is a sequential integer, so enumeration is trivial.

**A second defect in the same method:** `update()` calls `$request->all()`, not `$request->validated()`
— so the FormRequest's filtering is bypassed and every `$fillable` column is writable. `user_id` **is
fillable**, which means `PUT api/patients/{id}` with `{"user_id": <other>}` reassigns the patient to a
different account.

**Fix shape:** scope by `auth()->id()` (or the session's `patient_id`) in all three methods, and switch
`update()` to `$request->validated()`.

### S-03 — `sendResumeEmail` mails a resume link for any test to any address

**Severity: high.**

`POST api/test/send-resume-email` sits behind `FlexibleAuthMiddleware`, then
[`TestResumeController::sendResumeEmail()`](../../TCV-Backend/app/Http/Controllers/TestResumeController.php)
validates only:

```php
'unique_test_id' => 'required|string|exists:patient_tests,unique_test_id',
'email'          => 'required|email|max:255',
```

Both come from the request body, and neither is checked against the caller's session. A resume token is
a **7-day** credential that `POST api/test/resume` (public) exchanges for a fresh 2-hour `TestSession`.
So one valid session plus a known `unique_test_id` mails a working test-takeover link to an
attacker-controlled inbox.

**Fix shape:** take `unique_test_id` from the session, and the destination address from the patient
record — not from the body.

### S-04 — `revokeCredit` has no ownership check

**Severity: medium.**

`POST api/patient-tests/{identifier}/revoke-credit` (`auth:sanctum`) →
`CreditsController::revokeCredit()` looks the test up by identifier and then abandons it:

```php
$group->each(fn($test) => $test->update(['status' => PatientTest::STATUS_ABANDONED]));
```

The refunded credit correctly goes to `$patientTest->patient->user` — the *owner*, not the caller — but
nothing stops **any authenticated user** from abandoning **any** in-progress test and expiring its
invitation. It is a destructive IDOR, not a theft one.

### S-05 — Organisation launch signatures are static, permanent bearer credentials

**Severity: medium.**

`POST api/organization/verify-signature` (public) validates
`hash_hmac('sha256', (string) $orgId, $signingKey)` — over the **org id alone**. No nonce, no timestamp,
no expiry. The value is embedded in the org's stored `test_url`, so anyone who ever sees that URL can
mint unlimited LMS sessions for that org, forever, until the signing key is rotated.

Compounding it, the "retroactive healing" branch accepts a signature computed with the **global
`APP_KEY`** whenever the per-org check fails. The comment calls this a "one-time fallback", but no flag
records that it was used — the branch stays reachable on every subsequent request. A single leaked
`APP_KEY` therefore forges a launch signature for **every** org.

See [CONTEXT/ORGANIZATION_CONTEXT.md](CONTEXT/ORGANIZATION_CONTEXT.md).

### S-06 — LMS provider secrets are stored in plaintext

**Severity: medium.**

`LmsLaunchService::buildDefaultConfig()` ends with:

```php
// Store as plain JSON for now; Phase 4 encrypts these
return json_encode($defaults);
```

For `TYPE_CORNERSTONE` that JSON holds `client_id`, `client_secret`, `token_url` and `lrs_url`. The
`signing_key` column is likewise a plain `Str::random(128)`. `LmsAdminController::revealSigningKey()`
returns it to any `auth:sanctum` caller — with **no policy check** on that route.

### S-07 — `login()` short-circuits on a Bearer token, skipping every account gate

**Severity: medium.**

[`AuthController::login()`](../../TCV-Backend/app/Http/Controllers/AuthController.php) lines 43–63 run
*before* validation:

```php
$accessToken = PersonalAccessToken::findToken($token);
if ($accessToken && (!$accessToken->expires_at || !$accessToken->expires_at->isPast())) {
    return response()->json(['status' => true, 'message' => 'Already logged in', …]);
}
```

That path never re-checks `account_status`, `trashed()`, or `email_verified`. A user suspended or
soft-deleted *after* their token was issued keeps getting a success response from `/api/login` for the
remainder of the token's life. (The 15-minute expiry bounds the damage.)

Separately, `login()`'s failure responses distinguish "no such user" (`api.unauthorized`) from
"restricted" (`api.resticted` — note the typo in the key) from "unverified", which is an enumeration
oracle. The resend endpoints deliberately avoid this; `login()` does not.

### S-08 — Two email-verification systems that disagree

**Severity: medium (correctness + lockout).**

Two independent representations of "verified" exist on `users`:

| Field | Written by | Read by |
|---|---|---|
| `email_verified` (`'yes'`/`'no'` string) | `verifyEmailByToken()` | **`login()`'s gate** |
| `email_verified_at` (timestamp) | `markEmailAsVerified()`, `verifyEmailByToken()` | `hasVerifiedEmail()`, `MustVerifyEmail` |

`User::markEmailAsVerified()` sets **only** `email_verified_at`. So a user who verifies through the
signed-link route (`GET api/verify-email/{id}/{hash}` → `verifyEmail()`) gets `email_verified_at` set
but `email_verified` still `'no'` — and is then **permanently unable to log in**, because `login()`
gates on the string column.

Any change here must update both columns or collapse them to one.

### S-09 — `stopImpersonation` never deletes the impersonation token

**Severity: low (functional bug with a security consequence).**

```php
$impersonatorId = collect($abilities)->first(fn($a) => str_starts_with($a, 'impersonated-by:'));
PersonalAccessToken::where('tokenable_id', $id)
    ->whereJsonContains('abilities', "impersonated-by:{$impersonatorId}")
    ->delete();
```

`$impersonatorId` is the **whole ability string** (`"impersonated-by:5"`), so the `whereJsonContains`
argument becomes `"impersonated-by:impersonated-by:5"` and matches nothing. The impersonation token
survives "stop impersonating" until it expires on its own.

The extraction should be `substr($ability, strlen('impersonated-by:'))` — or simply match on the
ability string directly.

### S-10 — `RestrictIpMiddleware` runs a DB query on every request, and exempts logout

**Severity: low.**

Appended **globally** in `bootstrap/app.php`, so every request — authenticated or not, including
unmatched routes — issues `RestrictedIp::where('ip_address', $ip)->exists()`. There is no caching. It
is both a performance floor and a hard dependency on the DB being reachable before any route runs.

It explicitly lets `api/logout` through, which is intentional (so a newly-restricted user can still
clean up their token) but means the restriction is not absolute.

### S-11 — `revokeAccess()` does not revoke S3 access

**Severity: low.**

`SecureImageService::revokeAccess()` calls `Cache::forget()` and nothing else. The pre-signed URL
already handed to the browser stays valid for its full `TEST_PLATE_URL_VALIDITY_SECONDS` (900 s). A
plate URL captured during a test remains fetchable for 15 minutes after the plate is answered. Given
plates are the test's intellectual property, treat the cache TTL as the *only* real control and size it
accordingly.

### S-12 — Non-production error responses leak messages and stack traces

**Severity: low (environment-dependent).**

`app/Exceptions/Handler.php` returns `$exception->getMessage()` — plus the full trace when
`app.debug` is on — for any JSON request outside `production`. Since `APP_ENV` for the shared dev/QA
environments is not `production`, those environments expose internals to anyone who can reach them.
See [ERROR_HANDLING.md](ERROR_HANDLING.md).

### S-15 — Terminal LMS session tokens still authenticate

**Severity: low — reclassified 2026-08-21 (`0a6d7c22`, ws-361). This is now a deliberate design, not a
defect.** The earlier reading of this finding was wrong; it is corrected here rather than deleted.

`FlexibleAuthMiddleware` still does not check session status — it checks `token_expires_at` only, so a
`reported`/`failed` session's bearer token keeps authenticating until that timestamp passes. **That is
on purpose (PR #180).** Delivery is queued, so a session can flip to `reported` while the patient is
still sitting on the result page; blocking terminal sessions at the auth layer broke the result page.

The compensating control is at the route layer, not the auth layer: every state-mutating LMS route
carries `lms.status:<allowed states>` (`LmsSessionStatusMiddleware`, registered in `bootstrap/app.php`),
and **no terminal status appears in any allow list**. A finished session that tries to drive the test
flow gets **409 `SESSION_STATUS_MISMATCH`**.

Two passing tests pin both halves of the trade-off:
- `LmsLaunchTest::test_terminal_session_can_still_read_result_related_endpoints` — `organization/privileges`
  and `organization/redirect-url` must stay readable.
- `LmsLaunchTest::test_terminal_session_cannot_advance_the_test_flow` — `POST api/tests/assign` must 409.

*(The previously documented `test_terminal_session_is_blocked` no longer exists. It asserted the
opposite behaviour and was removed, not fixed — do not "restore" it.)*

**Residual risk, and it is real:** a terminal token remains a valid read credential for the
`FlexibleAuthMiddleware`-guarded endpoints until `token_expires_at`. Narrowing that means shortening
the token lifetime on completion, not reinstating an auth-layer block.

---

## Summary table
| ID | Finding | Severity | Where |
|---|---|---|---|
| `S-01` | Public registration accepts `usertype: 1` | **critical** | `UserRequest` · `AuthController::register()` |
| `S-13` | ✅ **fixed 2026-08-26** — public invitation send spent any user's credits (≤500 emails) | ~~critical~~ | `TestInvitationController::sendInvitations()` |
| `S-02` | No test-ownership check on session endpoints | **high** | `TestController` · `TestExecutionService` |
| `S-03` | `sendResumeEmail` accepts arbitrary test + address | **high** | `TestResumeController` |
| `S-14` | `patients/{id}` unscoped + `update()` uses `$request->all()` | **high** | `PatientController` |
| `S-04` | `revokeCredit` IDOR (abandons any test) | medium | `CreditsController::revokeCredit()` |
| `S-05` | Static org launch signature + permanent `APP_KEY` fallback | medium | `OrganizationController::verifySignature()` |
| `S-06` | LMS provider secrets stored plaintext; signing key readable | medium | `LmsLaunchService` · `LmsAdminController` |
| `S-07` | `login()` Bearer short-circuit skips account gates | medium | `AuthController::login()` |
| `S-08` | `email_verified` vs `email_verified_at` disagree | medium | `User` · `AuthController` |
| `S-15` | Terminal LMS tokens keep **read** access by design; mutations 409 via `lms.status` | low | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware` |
| `S-09` | `stopImpersonation` deletes nothing | low | `AuthController::stopImpersonation()` |
| `S-10` | Global IP middleware, uncached DB hit per request | low | `RestrictIpMiddleware` |
| `S-11` | `revokeAccess()` leaves the S3 URL live | low | `SecureImageService` |
| `S-12` | Trace/message leak outside production | low | `Exceptions\Handler` |

---

## Rules for new code

1. **Never trust an id from the request when a session already implies it.** Derive it from the session.
2. **Guard by default.** A route added outside the two middleware groups is public — 20 already are
   ([PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md)). Check the audit after every route change.
3. **Return `ApiResponse::error(HttpStatus::…)` explicitly** for authorisation failures. If you rely on
   an exception, the handler turns it into a 500 and the client cannot distinguish it from a crash.
4. **Do not add a fifth mail mechanism** or a second response envelope — see
   [ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md).

---

_Verified 2026-08-19 against `TCV-Backend` `develop` (`85586469`)._
