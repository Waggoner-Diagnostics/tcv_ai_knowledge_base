# Security — Posture and Known Gaps

> **What this is.** Findings from *reading* TCV-Backend's auth, routing, session and payment paths at
> `85586469`, re-checked against `develop` at `52804ee9` (2026-09-07). No exploit was attempted and no
> pen test was run. Each finding names the file and line it
> came from so you can re-verify it in one read. Treat severities as this document's judgement, not a
> customer-facing rating.

Findings carry stable `S-nn` IDs so other docs can point at them without restating detail.

> ✅ **`tcv-backend-codefix` merged into `develop` (indexed 2026-09-07).** `S-02` (partial), `S-03`,
> `S-14` and `S-18` are now **fixed on the branch that ships**, along with session-token hashing, the
> `auth_context` request attribute, the seven named rate limiters, `AddRequestId`, and the
> `entrypoint.sh` boot fixes. Labels below have been flipped accordingly and re-verified against
> `develop` at `52804ee9`.
>
> `ws-401` merged too — its placeholder repair is on `develop` as
> `2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates`.
>
> ⚠️ **`ws-402` has NOT merged.** Findings and prose flagged `ws-402` — including
> [S-19](#s-19) — still describe an unmerged branch. Never mark a finding fixed against an unmerged
> branch without saying so in the same sentence.

---

## What is done well

Worth knowing, so you don't "fix" something that is already correct:

- **Passwords** — `bcrypt` via the `'hashed'` cast, and `PasswordRule::min(8)->mixedCase()->numbers()->symbols()`
  on both reset and change paths. ⚠️ **`->uncompromised()` is on the change path only**
  (`ChangePasswordRequest`); it is commented out in `AuthController::setOrResetPassword()`, so a breached
  password rejected on the profile page is still accepted through the emailed reset link
  ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md)).
- **All four session-credential tables now store their token SHA-256 hashed**, not just LMS.
  `TestSession.session_token` and `organization_patient_sessions.token` were plaintext until
  `tcv-backend-codefix` (2026-09-02) added a one-time hashing migration and switched every lookup site
  (`FlexibleAuthMiddleware`, `TestInvitationController`, `TestResumeController`, `PatientController`,
  `OrganizationController`) to hash the presented token before comparing. A DB leak no longer yields
  usable session tokens on any tier.
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

**Severity: high** — **PARTIALLY FIXED on `develop`** (merged from `tcv-backend-codefix`).
`TestController::callerOwnsPatientTest()` now exists (checked
2026-09-04). The five endpoints in the table below were never covered by that fix either.

**What the partial fix covered:** only `GET api/test-result/{unique_test_id}/download-pdf`, which is
not in the table below but had the same defect. It gained
`TestController::callerOwnsPatientTest()`, binding the certificate to the caller's session. The
privilege gate that shipped alongside it (`organizationAllowsDownload()`) was **not** an ownership
check — it only asked whether *some* organization permits downloads, which every test-taker's own org
answers yes to, and it read `org_id` from request input, so appending `?org_id=<any org with
privilege 3>` defeated it outright. Both are fixed; `org_id` now comes from the credential.

**The five endpoints below are unchanged and still unscoped.**

---

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

**Severity: high** — ✅ **FIXED on `develop`** (merged from `tcv-backend-codefix`). Historically `show()` was `Patient::findOrFail($id)` with no scoping and
`update()` still reads `$request->all()`
([PatientController.php:107-123](../../TCV-Backend/app/Http/Controllers/PatientController.php#L107)),
so the `user_id` reassignment below is live too.

**What the fix on that branch did:** `show()` and `update()` now go through a new
`PatientController::callerOwnsPatient()`, which mirrors `TestController`'s check and reads the
middleware-resolved credential rather than request input. `destroy()` is staff-only — a test-taker's
own session can no longer delete the patient record it is bound to. All three return
`api.patient_not_found` (404, not 403: the caller is not entitled to learn whether the id exists).
`update()` switched to `$request->validated()` and explicitly `unset($validated['user_id'])`, so a
patient can no longer be reassigned to another account. The description below is kept for history.

⚠️ **Two consequences of that fix shape, both handled 2026-09-07 — carry them if you copy it.**

*`validated()` silently narrows the writable column set to whatever the FormRequest lists.* This bit
**twice**, and the second one was live:

- `test_condition` is in `Patient::$fillable` but had no rule at all, so it became unwritable through
  `PUT`. Unnoticed because the SPA does not send it. Rule added
  (`sometimes|nullable|integer|in:1,2,3` — the domain the column's own comment documents, unrelated to
  the `test_conditions` table, which describes test flow).
- ☠️ `zipcode` **was being sent by the SPA and silently discarded.** The rule said `zip_code` while the
  column, the fillable key and `usePatientForm.js:150` all say `zipcode`, so `validated()` dropped it —
  no 422, no log. `PatientAddRequest` spells it correctly, so *create* kept working and only *update*
  broke, which is why it survived review: the field appeared to work end to end.

**Whenever you swap `all()` for `validated()`, diff `$fillable` against the request's rules — in both
directions.** `tests/Feature/Patients/PatientUpdateFieldsTest.php` now does exactly that as a standing
assertion, so the next misspelling fails a test instead of shipping; a per-field test would not have
generalised.

*The ownership check needs an identity the session can actually carry.* The binding lives in the new
`test_sessions.patient_id`, and rows predating it cannot be backfilled: an org-added-patient session
records no invitation, `test_sessions` has no other column referencing a patient, and
`organization_patient_sessions` — the one place the pairing was ever known — holds no reference back to
the session. Guessing by timestamp could bind a session to the *wrong* patient, which is worse than
losing it. Left alone such a session keeps authenticating while publishing `patient_id = null`, so
ownership-checked endpoints 404 while everything else keeps working — half-broken, and differently per
endpoint. The migration now expires those rows instead, making the outcome one deterministic thing.
Bounded by the 2 h session TTL, so it only touches sessions in flight at deploy; invitation-backed
sessions are untouched, their identity still resolving through the invitation. Pinned by
`tests/Feature/Authorization/TestSessionPatientIdMigrationTest.php`.

---

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

### S-18 — `assignTest` / `getActiveTest` let a session act on another organization's patient

**Severity: high** — ✅ **FIXED on `develop`** (merged from `tcv-backend-codefix`) — `callerOwnsPatient()`, the `auth_context` request attribute and
`tests/Feature/Authorization/` are all absent from `develop` (checked 2026-09-04). Distinct from `S-02` above: that
finding is about the five `unique_test_id`-keyed endpoints; this one is about the `patient_id`-keyed
surface (`POST api/tests/assign`, `POST api/tests/check-active`), which had no ownership check of any
kind — not even the informational, request-input version.

**What the fix does (on that branch):** both methods call the same `TestController::callerOwnsPatient()` used by
`PatientController` ([S-14](#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping)), reading
`FlexibleAuthMiddleware::context()` rather than trusting `$request->input('patient_id')`. Before the
fix, any tier-2/3/4 session — including one org's own test-taker session — could pass an arbitrary
`patient_id` belonging to a *different* organization and either read that patient's active-test state or
assign a new test against it, consuming the other organization's credits without consent. Both endpoints
sit inside the `FlexibleAuthMiddleware` group, so all four credential tiers could reach them.
`tests/Feature/Authorization/SessionOwnershipTest.php` pins the forged-`patient_id` case and the
legitimate org-added-patient path (which the naive fix could otherwise regress, since that tier's
sessions carry no invitation).

### S-03 — `sendResumeEmail` mails a resume link for any test to any address

**Severity: high** — ✅ **FIXED on `develop`** (merged from `tcv-backend-codefix`). Both `TestResumeController::callerOwnsPatientTest()` and the
`test_sessions.patient_id` column the fix depends on exists on `develop` (checked 2026-09-04).

**What the fix on that branch did:** `TestResumeController::callerOwnsPatientTest()` now reads the resolved
credential instead of request input. The earlier attempt at this check still fell through to a
body-supplied `patient_id` whenever `test_invitation_id` was null — which is *every org-added
patient session* — so the hole stayed open on the exact tier it mattered on. Sessions are now bound
to a patient via the new `test_sessions.patient_id` column and compared against
`$patientTest->patient_id`. The description below is kept for history.

---

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

✅ **Fixed in `ws-417` (2026-09-03).** `User::markEmailAsVerified()` now sets `email_verified = 'yes'`
alongside `email_verified_at`, so the signed-link route no longer strands a user.

The lockout it caused: `markEmailAsVerified()` set **only** `email_verified_at`, so a user verifying
through `GET api/verify-email/{id}/{hash}` → `verifyEmail()` got the timestamp but kept
`email_verified = 'no'`, and `login()` gates on the string column. It also nulls
`email_verification_token`, so `resendVerificationByToken()` could not rescue them either. `ws-417`
removed the last fallback — `login()` used to mail a fresh token on every unverified attempt — which is
what turned a latent inconsistency into a dead end and forced the fix.

☠️ **The two columns still exist and can still drift.** They were not collapsed. Anything writing one
must write the other; a single `verified` column remains the real fix.

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

### S-16 — Every client shares one IP: rate limits and IP restriction are both inert

**Severity: high.** **Open** — diagnosed 2026-09-02, fix written but **deliberately not shipped**
(see *Fix shape* below).

php-fpm sits behind the `backend-nginx` container, and nginx passed only the stock `fastcgi_params`,
so `REMOTE_ADDR` was **always the nginx container's address**. Laravel had no trusted-proxy
configuration at all, so `$request->ip()` returned that same proxy address for every request on the
platform. Two consequences:

- The five rate limiters added for `login`, `register`, `password-reset`, `signature-verify` and
  `bulk-invitations` all key on `$request->ip()`, so they shared **one global bucket**. Six login
  attempts in a minute from anywhere locked out every user everywhere. (The `plate-url` limiter keys
  on the session token instead and was never affected — that asymmetry is what gave the bug away.)
- `RestrictIpMiddleware` compared that proxy address against `restricted_ips`, so the IP-restriction
  feature could never match a real client. It was a security control that silently did nothing.

**Fix shape — and why the two halves must ship together.** nginx must forward
`HTTP_X_FORWARDED_FOR` using `$proxy_add_x_forwarded_for`, which *appends* the real peer so an
upstream chain survives and a client-supplied header cannot pose as the trusted hop. Laravel must
then call `trustProxies()` in `bootstrap/app.php`.

☠️ **Shipping the Laravel half alone is worse than shipping neither.** nginx auto-forwards client
request headers to php-fpm as `HTTP_*` params. If Laravel trusts the nginx container (a private
address) but nginx does not rewrite the header, a client-sent `X-Forwarded-For` is believed verbatim
— so an attacker can rotate it per request to bypass every rate limiter completely and evade the
`restricted_ips` blocklist. That is a *worse* position than today's single shared bucket. The written
fix was held back on 2026-09-02 for exactly this reason: the nginx side was not being deployed.

### Status 2026-09-12 — the Laravel half has landed, fail-closed

`bootstrap/app.php` on `develop` now calls `trustProxies()`, but **only when `TRUSTED_PROXIES` is a
non-empty comma-separated CIDR list**; the default is empty and the call is skipped entirely. So the
half-shipped state above is *not* what got deployed — with the var unset, behaviour is byte-for-byte
what it was, and `S-16` is unchanged in effect. The gate is what made it safe to merge ahead of nginx.

☠️ **Do not set `TRUSTED_PROXIES` yet.** The nginx side does not currently satisfy the fix shape.
`TCV-Frontend/nginx.conf:41-42` has:

```nginx
set_real_ip_from 0.0.0.0/0;      # ← trusts EVERY peer
real_ip_header   X-Forwarded-For;
```

so nginx overwrites `$remote_addr` from a **client-supplied** `X-Forwarded-For`, and
`proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for` (`:54`, `:63`) then appends that already
forged value. A client sending `X-Forwarded-For: 1.2.3.4` reaches Laravel as `1.2.3.4, 1.2.3.4`. Trust
the hop today and the attacker picks their own IP — precisely the "worse than neither" case.

Two things must change before the var is set: narrow `set_real_ip_from` to the real upstream CIDR, and
set `TRUSTED_PROXIES` to that same CIDR (never `*`).

⭐ Note `proxy_set_header X-Real-IP $realip_remote_addr` — `$realip_remote_addr` is the peer address
*before* the `real_ip` rewrite, so `X-Real-IP` is the one genuinely trustworthy header on this path
today. Laravel's `trustProxies()` is configured for the `X_FORWARDED_*` set and does **not** read it.

⭐ **There is a third precondition nobody has hit yet, because the variable has no plumbing.**
`TRUSTED_PROXIES` appears in exactly one place in the backend repo — the `env()` call in
`bootstrap/app.php` — and is **not** in the `environment:` allowlist of `docker-compose.yml` or
`docker-compose-dev.yml`. Compose injects only the keys listed there, so the variable cannot currently
reach the container at all; an env-file edit alone is a no-op. Adding the compose entry is a code
change in `TCV-Backend` and has to land before any value takes effect. It must also arrive as a real
container env var rather than a `.env` line — `entrypoint.sh:34` runs `config:cache` on boot and the
code reads `env()` (it must; `config()` is unavailable that early). See
[CONFIGURATION.md](CONFIGURATION.md) and [ENVIRONMENT.md](ENVIRONMENT.md).

⚠️ **This is good news for the ordering, not bad.** It means `S-16` cannot be half-fixed by accident:
until someone deliberately adds the compose entry, the fail-closed default holds on its own.

If a `TRUSTED_PROXIES` env override is added, parse it as `trim(...) ?: <default>` rather than
`env('TRUSTED_PROXIES', <default>)` — docker-compose substitutes an *empty string* for an unset
variable, and `env()` returns that empty string instead of the default, leaving an empty proxy list
that means "trust no proxy" and silently reinstates the bug.

**Interim mitigation on `tcv-backend-codefix`, and what it does *not* cover.** Rather than wait for
the nginx half, the limiters were re-keyed through `AppServiceProvider::callerKey()`, which leads with
the identifier the endpoint already carries (the account's email, the org id, the caller's user id) and
appends the IP. That removes the platform-wide bucket — one account's attempts can no longer lock out
another — and becomes per-client automatically if nginx is ever fixed, with no code change.

☠️ **The residual gap: there is now no global ceiling on authentication attempts.** The key is
`email|ip`, and the email half is attacker-supplied, so N distinct emails buy N independent 5/min
budgets. Password *spraying* and credential stuffing — one attempt against each of many accounts — are
therefore unthrottled, where before they hit the shared bucket. This is a deliberate trade: a
self-inflicted platform-wide outage was the more certain harm, and per-account keying is the standard
shape. It is not a complete answer, and the honest fix is still the nginx half above, after which a
real per-IP limiter can sit alongside the per-account one.

Adding a global limiter *now*, before nginx forwards the real peer, would recreate the exact outage
this replaced — every caller shares one address, so any global ceiling is a platform-wide kill switch.
Do not add one until `$request->ip()` means something.

A seventh limiter, `send-resume-email` (3/min), was added 2026-09-07: it mails a caller-supplied
address and keeps no resend counter, so an unthrottled session holder could loop it as an email relay.
It keys on the session/bearer token — the caller's own identity on that tier — not on the target
address, which the caller chooses and could vary freely. It was the one email sender left unthrottled
in a branch that had already added `throttle:bulk-invitations` and `throttle:password-reset`.
`RateLimitScopeTest` now asserts every `throttle:<name>` a route references resolves to a registered
limiter, because that mismatch otherwise surfaces only when a live caller hits the route.

### S-19 — `DELETE api/credits/{id}` let any authenticated user mutate a stranger's ledger

**Severity: high** — ✅ **the `delete()` half is fixed on `develop`** since 2026-09-07 (`ws-402`,
PR #213): the policy now checks `$user->isSuperAdmin()` before it looks at the source.
⚠️ **`index()` is still unscoped** — it reads `user_id` straight from the request, so the
read side of this finding remains open. Do not close S-19 until that is fixed too.

`CreditsPolicy::delete()` decided purely on `$credits->source` and **never read its `User $user`
parameter**. [`routes/api.php:179`](../../TCV-Backend/routes/api.php#L179) registers
`Route::resource('credits', …)` inside the `auth:sanctum` group opened at line 118 — authentication
only, no role middleware, no `Gate::before`. So any logged-in customer could
`DELETE api/credits/{id}` against a grant belonging to anyone, by id. `/add-credits` is a Super Admin
page in the SPA, so this was never intended access — the gate simply did not exist server-side.
`CreditsController::index()` has the same shape: it reads `user_id` from the request and never scopes
it to the caller.

**Why it is worse on `ws-402` than the equivalent gap on `develop`.** Two changes widen it:

- the allow-list grows — a `SOURCE_REVOKED` row whose `original_source === SOURCE_MANUAL` is now
  deletable too, where before only `SOURCE_MANUAL` was;
- `destroy()` stopped being a row delete. It now writes negative `SOURCE_ADMIN_REVOKED`
  counter-entries, can write a `SOURCE_ADJUSTMENT` row, and can trigger `settleNegativeBalance()`.
  An unauthorized caller therefore **mutates** another user's ledger rather than merely removing a row
  from it — and every one of those writes is designed to be permanent, because the whole subsystem
  treats history as append-only.

**Fix (on `ws-402`):** `delete()` now returns `false` unless `$user->isSuperAdmin()`, checked *before*
the source rules — who first, then what. Covered by
`CreditRevocationTest::test_a_customer_cannot_revoke_another_users_grant()` and
`…_the_grant_owner_cannot_revoke_their_own_grant_either()`, both of which also assert that no
counter-entry or adjustment row was written.

⚠️ **`index()` is not fixed.** Listing another user's grants — now including per-grant
`used_credits` / `revoked_credits` / `remaining_credits` — still only requires being logged in.
Distinct from [S-04](#s-04--revokecredit-idor-abandons-any-test), which covers `revokeCredit()`.

### S-20 — The QA automation endpoints are an account-takeover surface gated only by `APP_ENV`

**Severity: high (by design, and currently contained)** — added to `develop` 2026-09-08 (PR #223,
`add-apis-for-automation`). **Not a bug report**: the surface is deliberate, the gating is thought
through, and the risk is documented in the code itself. It is here because it is the single most
dangerous thing on `develop` if one environment variable is wrong.

`api/qa/*` — six routes, **no `auth:sanctum`, no `FlexibleAuthMiddleware`, no token of any kind**:

```
POST api/qa/user-state          POST api/qa/password/token   POST api/qa/email/token
POST api/qa/user/reset-state    POST api/qa/password/set     POST api/qa/email/verify
```

`password/token` and `email/token` hand back a **live token plus the exact URL the email would have
contained**, for any address the caller names; `password/set` and `email/verify` jump an account
straight to the end state. Anyone who can reach these on a host can take over **any account on it**,
including a Super Admin, without credentials.

**Two gates, and you need to understand why there are two:**

1. `routes/api.php` only registers the group `if (QaAutomationController::enabled())`.
2. `QaAutomationController::__construct()` re-checks on **every request**.

The second is not redundant. `entrypoint.sh` runs `php artisan route:cache`, so a cache built in QA
would otherwise carry these routes into whatever environment ran that image ([DEPLOYMENT.md](DEPLOYMENT.md)).
The constructor binds the gate to the **running** `APP_ENV` rather than the build-time one. It raises a
**404, not a 403** — outside QA these must be indistinguishable from routes that were never registered
— and it does so via `abort(response()->json(…))` rather than `abort(404)`, because this project's
`Handler` flattens a `NotFoundHttpException` on a JSON request into a **500** while returning an
`HttpResponseException`'s response as-is ([ERROR_HANDLING.md](ERROR_HANDLING.md)). Both of those choices
are load-bearing; do not "simplify" either.

`ENVIRONMENTS = ['qa', 'testing']` — `testing` is present so the feature test can exercise the routes.
**Production is safe only for as long as `APP_ENV` is right on every host**, so:

- ☠️ **`APP_ENV=qa` on a production box is a full compromise**, not a config smell. Check it during any
  deploy or environment-cloning work ([CONFIGURATION.md](CONFIGURATION.md)).
- ☠️ **Never add an environment to `ENVIRONMENTS`**, and never widen it to `local` for convenience.
- ⚠️ **These routes are invisible to this KB's generated indexes.** `tools/extract.php` reads
  `artisan route:list` from a working tree whose `APP_ENV` is not `qa`, so `api/qa/*` appears in
  **neither** [API_ENDPOINT_INDEX](INDEXES/API_ENDPOINT_INDEX.md) **nor**
  [PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md). The "15 of 161 public" headline is the count
  **for a non-QA environment**; on a QA box it is 21, and the audit will never tell you that. This is
  the one place where the derived views are structurally blind — do not read a clean
  `PUBLIC_ROUTE_AUDIT` as evidence about a QA host.

`tests/Feature/Qa/QaAutomationTest.php` (17 tests) is the only guard, and its most important case is
the one asserting the group 404s outside QA. Treat that test as load-bearing.

### S-17 — Five Stripe payment endpoints were public on `develop`

**Severity: medium** — ✅ **FIXED on `develop`** (2026-09-07, merged from `tcv-backend-codefix`). Found
2026-09-04 by the first `develop` regeneration since 2026-08-19. The five routes now sit inside
`auth:sanctum`; the regenerated [PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md) reports **15 of 158**
public endpoints, down from 20, and the scanner's `R-B00` fired only in the safe direction on every run
of that branch. (That headline is now **16 of 162** after the Audit Trail routes landed — see the note
below for the one addition.) The description below is kept for history.

⭐ **2026-09-12 — one new public endpoint, reviewed and accepted.** The `develop` merge added
`POST api/distributor-enquiry` (`API-030`, `DistributorController@submit`), taking the public count
15 → 16. It is public by intent — a marketing enquiry form on the unauthenticated site — and it is
built defensively: `throttle:10,1`, a `DistributorEnquiryFormRequest` for validation, and it forwards
to HubSpot with `allowUpdatingExistingContact: false`, so a caller cannot PATCH a stranger's contact
record by claiming their email. Nothing persists locally (HubSpot is the system of record), so only a
*failed* forward writes an audit row. No action needed; listed here so the count change is not read as
a regression. Compare `ContactController`, which follows the same pattern.

---

They used to be registered at the top of `routes/api.php`, **before** the `FlexibleAuthMiddleware` group
and the `auth:sanctum` group — so they took no middleware at all:

```
POST   api/stripe/create-payment-intent
POST   api/stripe/confirm-payment
GET    api/stripe/payment-methods
POST   api/stripe/payment-methods/set-default
DELETE api/stripe/payment-methods/{payment_method_id}
```

Every handler opens with `$user = Auth::user();` and passes the result straight into
[`StripeService`](../../TCV-Backend/app/Services/StripeService.php), whose methods are all typed
`User $user` (`getPaymentMethods` :363, `setDefaultPaymentMethod` :371, `removePaymentMethod` :401,
`createStripePaymentIntent` :246).

**What actually happens today:** with no token, `Auth::user()` is `null`, so the typed parameter throws
a **`TypeError` on entry to the service** — before `paymentMethods->detach()` or any other Stripe call
runs. `TypeError` extends `Error`, not `Exception`, so the controllers' `catch (\Exception $e)` does
**not** catch it and it reaches `Handler.php` as a 500 ([ERROR_HANDLING.md](ERROR_HANDLING.md)).

So there is **no exploit path right now** — but the safety is accidental:

- It rests entirely on those `User` type hints. Widen one to `?User` and
  `DELETE api/stripe/payment-methods/{payment_method_id}` becomes an unauthenticated
  `paymentMethods->detach($id)` on any `pm_…` id an attacker knows.
- A caller with an expired token gets a **500**, not a 401 — Sanctum's 15-minute expiry makes this a
  routine client state, and the SPA cannot tell "log in again" from "the payment system broke".
- Five unauthenticated routes into the Stripe integration are reachable for probing.

**Fix:** move the five lines inside the `auth:sanctum` group and read the user from
`$request->user()`. **This fix already exists** on `tcv-backend-codefix`, where the same routes sit at
lines 131-135 *inside* that group — it is one of the 17 unmerged commits on that branch.

⚠️ **The prose had this right; the generated audit did not.** [ROUTES.md](ROUTES.md) (Zone 1),
[API_INDEX.md](API_INDEX.md) and [BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) have all described these
five as public and broken for some time. But the 2026-09-02 sync was generated from
`tcv-backend-codefix`, where they sit inside `auth:sanctum`, so
[PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md) listed 15 endpoints and these five were **absent** —
the index contradicted the prose for two days. `verify.php`'s prose-count check is what surfaced it
(prose said 20, the index said 15). Trust the audit only when `facts.json`'s `git` block says
`develop`.

## Summary table
| ID | Finding | Severity | Where |
|---|---|---|---|
| `S-01` | Public registration accepts `usertype: 1` | **critical** | `UserRequest` · `AuthController::register()` |
| `S-13` | ✅ **fixed 2026-08-26** — public invitation send spent any user's credits (≤500 emails) | ~~critical~~ | `TestInvitationController::sendInvitations()` |
| `S-02` | No test-ownership check on session endpoints — **partially fixed on `develop`**; five endpoints still unscoped | **high** | `TestController` · `TestExecutionService` |
| `S-03` | ✅ **fixed on `develop`** — `sendResumeEmail` now binds to the caller's credential | ~~high~~ | `TestResumeController` |
| `S-14` | ✅ **fixed on `develop`** — ownership-scoped, and `update()` uses `validated()` | ~~high~~ | `PatientController` |
| `S-18` | ✅ **fixed on `develop`** — both read the unforgeable `auth_context` | ~~high~~ | `TestController` |
| `S-19` | `CreditsPolicy::delete()` ignored `$user` — any authenticated user could delete/mutate anyone's credit ledger. ✅ **delete gated on `develop`** 2026-09-07 (`ws-402`); ⚠️ **`index()` still unscoped**, so the finding stays open on the read side | **high** | `CreditsPolicy` · `routes/api.php:179` |
| `S-20` | `api/qa/*` — six unauthenticated account-takeover helpers, registered only when `APP_ENV` ∈ (`qa`, `testing`). Deliberate and double-gated, but invisible to the generated route indexes | **high** (contained) | `Qa/QaAutomationController` · `routes/api.php` |
| `S-04` | `revokeCredit` IDOR (abandons any test) | medium | `CreditsController::revokeCredit()` |
| `S-05` | Static org launch signature + permanent `APP_KEY` fallback | medium | `OrganizationController::verifySignature()` |
| `S-06` | LMS provider secrets stored plaintext; signing key readable | medium | `LmsLaunchService` · `LmsAdminController` |
| `S-07` | `login()` Bearer short-circuit skips account gates | medium | `AuthController::login()` |
| `S-08` | `email_verified` vs `email_verified_at` disagree — ✅ **fixed `ws-417`**, columns not collapsed | medium | `User` · `AuthController` |
| `S-15` | Terminal LMS tokens keep **read** access by design; mutations 409 via `lms.status` | low | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware` |
| `S-09` | `stopImpersonation` deletes nothing | low | `AuthController::stopImpersonation()` |
| `S-10` | Global IP middleware, uncached DB hit per request | low | `RestrictIpMiddleware` |
| `S-11` | `revokeAccess()` leaves the S3 URL live | low | `SecureImageService` |
| `S-12` | Trace/message leak outside production | low | `Exceptions\Handler` |
| `S-16` | Proxy IP makes all rate limits one global bucket and `RestrictIpMiddleware` inert. Laravel half **landed on `develop` 2026-09-12, fail-closed** (`trustProxies()` gated on `TRUSTED_PROXIES`, default empty ⇒ still inert). ☠️ Do not set the var until `nginx.conf`'s `set_real_ip_from 0.0.0.0/0` is narrowed | **high** | `nginx.conf` · `bootstrap/app.php` |
| `S-17` | ✅ **fixed on `develop`** — the five Stripe routes moved inside `auth:sanctum`; public `api/*` fell 20 → 15 | ~~medium~~ | `routes/api.php` · `StripePaymentController` |

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

_Verified 2026-08-19 against `TCV-Backend` `develop` (`85586469`); findings dated 2026-09-02 re-verified
2026-09-04 against `tcv-backend-codefix` (`f96382ea`)._
