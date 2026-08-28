# Security Review Gate

Run this **in addition** to [REVIEW_CHECKLIST.md](REVIEW_CHECKLIST.md) when the PR touches any of:

> routes · authentication · authorization · patients · credits · payments · organisations / LMS
> launch · email or resume tokens · anything reading an id from the request

The 14 standing findings are in [SECURITY.md](../SECURITY.md) with stable `S-nn` ids. This page is the
*gate* — what to block on.

---

## Blocking checks

### 1. Did a route lose its guard?

```bash
composer review -- --repo=backend --base=develop --head=<branch>
```

`R-B00` at **CRITICAL** means a route that was guarded on the merge base is public on this branch.
Guarding is **positional** in `routes/api.php`, so this is invisible in a line-by-line diff — a route
can become public because a `});` moved, with no change to the route's own line.

**Block until the author confirms it is intentional.**

### 2. Is a new endpoint public by accident?

`R-B00` at **HIGH** = a new public endpoint. Ask: does this genuinely precede any credential
(login, a token exchange)? 21 endpoints are already public — several of them shouldn't be
([S-01](../SECURITY.md), [S-13](../SECURITY.md)).

### 3. Is an id trusted from the request?

The recurring defect class here. Block if the PR adds a handler that takes `unique_test_id`,
`patient_id`, `user_id`, `test_answer_id` or an org id **from the URL or body** and acts on it without
proving the caller owns it.

```php
// ❌ the pattern behind S-02, S-03, S-04, S-13, S-14
$patient = Patient::findOrFail($id);
$credit  = Credits::getAvailableCredits($request->input('user_id'));

// ✅
$patient = Patient::where('user_id', auth()->id())->findOrFail($id);
$credit  = Credits::getAvailableCredits(auth()->id());
```

Remember `FlexibleAuthMiddleware` already merges `patient_id`, `org_id` and `unique_test_id` into the
request for the session tiers — **use those**, not the URL.

### 4. Mass assignment

`$request->all()` in a controller (`R-B01`) defeats the FormRequest and makes every `$fillable` column
writable — `user_id` included, which reassigns ownership. Block.

### 5. Secrets

- `R-B05` / `R-F05`: a credential literal in source. **Block, and rotate the key** — it is in git
  history from the moment it is pushed.
- `R-X01`: a real `.env` in the diff.
- `REACT_APP_*` values ship to the browser. So does anything with a `NEXT_PUBLIC_` prefix.
- `R-B06`: logging a token or a whole request body. `sendVerificationEmailForUser()` already logs a live
  24-hour verification credential — do not add a second one. [LOGGING.md](../LOGGING.md)

### 6. Privilege boundaries

- A new `tokenCan('…')` without the matching ability in `AuthController::login()` **inverts** the check:
  it fails for super admins (explicit 9-item list) and passes for customers (`['*']`).
  [POLICIES.md](../POLICIES.md)
- A new admin surface with only `auth:sanctum` is reachable by every role. `api/admin/lms/*`,
  `api/reports/*` and `super-admin/dashboard` already are — don't extend the pattern.

### 7. Injection & data exposure

- `DB::raw()` with `$` interpolation (`R-B07`) → bind parameters.
- New error responses must not echo `$e->getMessage()` to the client. Outside `production` the handler
  already does ([S-12](../SECURITY.md)); `ContactController` does it explicitly.

---

## Non-blocking, but ask

- **Rate limiting.** Does the new endpoint send mail, cost money, or allow guessing? Only one route in
  the app is throttled today.
- **Token lifetime & storage.** New credentials should be hashed at rest (the LMS session token is the
  example done right; invitation, session and resume tokens are stored plaintext).
- **Expiry.** New tokens need a TTL *and* someone to enforce it — nothing is scheduled, so expiry is
  checked at read time only ([JOBS.md](../JOBS.md)).
- **Idempotency** on anything that spends a credit or charges a card.

---

## Fixing a standing finding

If the PR fixes an `S-nn`:

1. Confirm the fix covers **every** call site, not the one in the ticket. `S-02` spans five endpoints;
   `S-14` spans three methods.
2. Mark it **fixed with the date** in [SECURITY.md](../SECURITY.md) — keep the entry, don't delete it.
   The history is what stops it being reintroduced.
3. Check the summary table at the bottom of that file too.
4. Re-run `composer regenerate` if routes moved.

## Introducing a new finding

If review turns up a security problem the KB doesn't have, add it to
[SECURITY.md](../SECURITY.md) with the next `S-nn`, the file it came from, and the severity — even if
the PR isn't the cause. That file is the project's running risk register, and it is only useful if it
stays complete.

---

## Context worth having

- **Auth is opt-in.** A route outside both middleware groups is public. There is no deny-by-default.
- **`FlexibleAuthMiddleware` accepts four token kinds.** Anything in that group is reachable by an
  invitation session, not just a logged-in user.
- **403 and 404 arrive as 500.** You cannot infer "the guard worked" from a 500 in a manual test.
- **Sanctum tokens live 15 minutes**, which bounds — but does not remove — the impact of a leaked token.
- **There is no audit trail.** `pricing_audit_logs` covers pricing changes only. Credit grants,
  impersonation, patient deletion and test abandonment are recorded nowhere but the application log.
  Factor that into "how would we detect this?"
