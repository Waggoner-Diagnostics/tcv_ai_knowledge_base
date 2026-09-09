# Context: Credits

> Load this **instead of** reading the credits subsystem. ~2.3k tokens. Credits are the product's
> currency: one credit ≈ one test.

## Files
| File | Role |
|---|---|
| `app/Models/Credits.php` | ⭐ **The live model.** Grants, balance maths, `addCreditsToUser()` |
| `app/Models/CreditConsume.php` | ⭐ The spend ledger |
| `app/Models/Credit.php` | ⚠️ **Second model on the same table.** Near-dead — see trap 1 |
| `app/Http/Controllers/CreditsController.php` | List, grant, delete, revoke |
| `app/Http/Controllers/PaymentController.php` | `getCreditHistory()` — the unified view |
| `app/Http/Requests/CreditsAddRequest.php` | Grant validation |
| `app/Policies/CreditsPolicy.php` | Only `delete` returns anything but `false`. Since `ws-402` (2026-09-07) it finally reads `$user` — see trap 4 and [S-19](../SECURITY.md#s-19) |
| `app/Console/Commands/SettleNegativeCreditBalances.php` | `credits:settle-negative-balances`, one-time repair for pre-fix hidden debt. On `develop` since 2026-09-07 — **still nothing schedules it**, run it manually |

## Tables
`credits` (grants) · `credit_consume` (spends) · `transactions` / `transaction_details` (Stripe link)

---

## The model: balance is derived, never stored

```
available = Credits::getTotalUserCredit(userId)          // SUM over non-expired grant rows
          − CreditConsume::getTotalConsumed(userId)      // SUM(credits_used)
```

`Credits::getAvailableCredits(int $userId): int|string` clamps at `max(0, …)`.

**There is no balance column.** Every read is two aggregate queries. Never cache the result across a
request, and never "correct" a balance by writing a number — write a grant or a consume row.

### Grant rows (`credits`)
| Column | Meaning |
|---|---|
| `credits` | quantity granted — **negative on `ws-402` (merged 2026-09-07)** for a `SOURCE_ADMIN_REVOKED` counter-entry |
| `source` | `0 = SOURCE_MANUAL` · `1 = SOURCE_PURCHASE` · `2 = SOURCE_REVOKED` · on `ws-402` (merged 2026-09-07) also `3 = SOURCE_ADMIN_REVOKED` · `4 = SOURCE_ADJUSTMENT` |
| `original_source` | **On `develop` since 2026-09-07 (`ws-402`).** Meaningful only on a `SOURCE_REVOKED` row: which grant (Manual/Purchase) funded the test this refund covers. Null otherwise — including on every refund written before the column existed. Cast to `integer` alongside `source`, because `CreditsPolicy::delete()` compares it with `===` and MySQL's PDO can hand an integer column back as a string |
| `has_expiry` / `expiry_date` | expiry is opt-in; `expiry_date >= today` to count — **compared as a DATE, see below** |
| `is_unlimited_credit` | see below |
| `coupon_code`, `price_per_credit`, `total_price`, `credited_by` | provenance |

### Spend rows (`credit_consume`)
| Column | Meaning |
|---|---|
| `credits_used` | quantity spent |
| `event_type` | `'test_invitation'` or `'test_completion'` |
| `ref_id` | **JSON array** (cast to `array`) of the ids the spend covers |

`CreditConsume::consume()` records **even for unlimited holders**, deliberately, for audit.

### ⚠️ Expiry is compared DATE-to-DATE, and that changed behaviour

`expiry_date` is a **DATE** column. Comparing it against a DATETIME (`now()`, or
`now()->startOfDay()`) made a credit dated "expires today" stop counting from 00:00 **on its own expiry
day** — MySQL widens the DATE to midnight so `'2026-09-07' >= '2026-09-07 09:00'` is false, and SQLite
compares the two as strings with the same outcome. The holder lost a full day they were entitled to.

`getTotalUserCredit()` (both the unlimited probe and the finite sum) and
`CreditsController::checkDiscountCodeValidity()` now compare against `now()->toDateString()`, so a
credit or coupon dated today is valid for the whole of that day.

☠️ **This is a production behaviour change, not a portability no-op.** The in-code comment originally
described it as a SQLite fix, which undersold it — the same bug and the same change apply on MySQL.
Anything that reconciles credit balances across the boundary will see the shift. Pinned by
`tests/Feature/Credits/CreditsExpiryBoundaryTest.php`, which walks the day before / first second /
last minute / day after, for both finite and unlimited grants.

---

## `'Unlimited'` is a string

`getTotalUserCredit()` and `getAvailableCredits()` return the **string** `'Unlimited'` when the user has
any live `is_unlimited_credit` grant. Every caller must compare before doing arithmetic:

```php
$credit = Credits::getAvailableCredits($userId);
if ($credit !== 'Unlimited' && $credit < 1) { /* insufficient */ }
```

Forget the guard and PHP coerces `'Unlimited'` to `0` in a numeric comparison — an unlimited customer is
told they have no credits. Both `TestInvitationController` and `TestController::assignTest()` do guard;
copy their shape.

---

## Where credits are spent

| Path | When | Amount | `event_type` recorded |
|---|---|---|---|
| `TestInvitationController::sendInvitations()` | at **queue** time, per email (`ws-404`) | 1 per invited address (always the authenticated caller since 2026-08-26) | `test_invitation` |
| `SendTestInvitationEmailsJob::markFailed()` | **refund**, per undeliverable address (`ws-404`) | +1, as a `SOURCE_REVOKED` grant | — |
| `TestAssignmentService` (via `TestController::assignTest()`) | at **assign** time — *unless* `test_invitation_id` is present | 1 | ⚠️ `test_completion` |
| — | never actually at completion | — | — |

⭐ **Invitation credits are now charged before the email is sent** (`ws-404`). Delivery moved out of the
request, so the whole batch is billed inside the insert transaction and each address that cannot be
delivered is refunded individually by the job. Consequences worth knowing:

- A send that is interrupted (container restart) leaves rows at `email_status = 'pending'` **already
  charged**. `php artisan invitations:send-pending` finishes them; nothing runs it automatically.
- A refunded row is also `is_revoked = true`, which deliberately blocks both resend and cancel — a
  resend would be free and a cancel would refund the same charge twice.
- The refund goes to `User::find($this->userId)`, the invitation's own owner — **not** the caller. This
  is the opposite of `cancelUnregisteredInvitation()`, which credits `auth()->user()` (the trap below).

☠️ **The `event_type` values are misleading.** Both spends happen before the test is taken, but the
direct-assign path records `EVENT_TEST_COMPLETION`. Any report filtering `credit_consume.event_type`
mis-attributes one of the two. Do not "correct" the constant without migrating existing rows — the
history would then be inconsistent in a different way.

The two must not double-charge: `assignTest()` skips the deduction when the request carries a
`test_invitation_id` (merged by `FlexibleAuthMiddleware` from the session), because the invitation
already paid. **If you change how `test_invitation_id` reaches `assignTest`, you change billing.**

### Refunds
Only `CreditsController::revokeCredit()` and `cancelUnregisteredInvitation()` refund, and they do it by
**granting a new row** with `source = SOURCE_REVOKED` — never by deleting a consume row. Refund is
always **1 credit**, even for a both-eyes (two-row) test, because a monocular pair is one purchase.

`revokeCredit()` also sets every test in the group to `abandoned` and expires the invitation so the
patient's link stops working.

**`ws-402` (merged 2026-09-07) adds provenance to that refund row.** Both methods now call
`Credits::traceConsumedOrigin($user, $eventType, $candidateRefIds)` before granting the refund, and store
the result as the new row's `original_source`. `traceConsumedOrigin()` replays FIFO history — it never
reads a stored link, because `credit_consume` only records an aggregate count per event, not which grant
paid for it: find the consumption event the test belongs to, work out how many credits had already gone
out (other consumption, plus prior admin claw-backs) strictly before it, then walk the user's grants
oldest-first to see whose capacity that position falls inside. It falls back to `SOURCE_PURCHASE` — never
admin-deletable — whenever the trace can't be pinned down (e.g. the test was sent while the user held an
unlimited grant, which draws from no finite grant at all); understating what's revocable is the only safe
direction to be wrong in here. The point of recording it: `CreditsPolicy::delete()` (below) uses it to
decide whether *this refund itself* may later be deleted by an admin.

The two "prior outflow" queries inside it are **deliberately asymmetric**. `$priorConsumed` breaks a
same-timestamp tie on `id` (`created_at <` OR `created_at =` AND `id <`) because both sides are
`credit_consume` rows. `$priorAdminRevoked` cannot: those are `credits` rows being compared against a
`credit_consume` row, two independent id sequences with nothing meaningful to order across. It uses
`created_at <=` instead, counting a same-second claw-back as prior. That only ever pushes `$position`
further down the grant list, toward the `SOURCE_PURCHASE` fallback; under-counting would pull it back
onto an earlier Manual grant and mark a refund deletable that isn't. Do not "tidy" this into a matching
`id` tiebreak — there is no shared sequence to tiebreak on.

### Admin revocation (`DELETE api/credits/{id}`, `CreditsController::destroy()`)

This is the opposite direction from a refund — an admin taking back credits they (or a purchase) granted
— and on `develop` it is a straight `$credits->delete()`: the whole grant row is removed, including
whatever part of it the user had already spent. Every balance in the app is `granted − consumed` clamped
at zero, so deleting a partly-spent grant pushes `granted` below `consumed` and the clamp hides the
resulting deficit — invisible right up until the user's *next* grant or purchase silently pays it off.

**`ws-402` (merged 2026-09-07) replaces this with `Credits::revokeGrant($grant)`,** which takes back only the
**unspent** part:

- `Credits::getUnusedCreditsForGrant($grant)` reads `Credits::getGrantAllocation($userId)` — a FIFO
  spread of total consumption (tests) *and* prior admin claw-backs across the user's active grants,
  oldest first — to find how much of this specific grant is still unspent.
- Untouched (`unused === credits`) → the row is deleted outright, same as today.
- Partly spent (`0 < unused < credits`) → the row is **kept**, and a new negative
  `SOURCE_ADMIN_REVOKED` row (`credits = -$unused`) is written alongside it.

  ☠️ **The counter-entry inherits the grant's `has_expiry` / `expiry_date`, and must.**
  Written with `has_expiry = false` it outlived the grant it offsets: `scopeActive()` kept the −N
  forever while the +N dropped out on its expiry date, so the account's active total went negative by
  exactly the revoked amount and the user's next purchase silently vanished into the deficit. Grant 10
  with an expiry, spend 3, revoke 7 → correct today, **−7** the day the grant expires. That is the
  hidden-debt mechanism this method exists to eliminate, reintroduced by the fix for it — and
  `settleNegativeBalance()` is no safety net, because it is manual, one-shot, and this deficit is
  spurious rather than historical, so settling it would mint credits from nothing. Keep the pair
  symmetric: both count, or neither does. Pinned by
  `test_a_partial_revoke_does_not_leave_a_deficit_once_the_grant_expires()`. The original grant still
  shows what was given; the counter-entry shows what was taken back. Nothing is ever deleted or edited in
  place.
- Fully spent (`unused === 0`) → nothing to take back. The controller returns **422** ("These credits
  have already been used and can no longer be removed."), not a silent no-op.
- **Unlimited grant** → still deleted outright (there is no "unspent part" of unlimited), but losing it
  turns everything consumed while it was active into a debt against the user's finite credits — the same
  hidden-deficit problem as above, just triggered by losing the *unlimited flag* instead of a finite
  grant. `revokeGrant()` calls `Credits::settleNegativeBalance($userId)` afterward, which writes a
  `SOURCE_ADJUSTMENT` row for exactly the deficit if one now exists.
- **A grant that doesn't count toward the balance** → deleted outright; it already counted for nothing,
  so there is nothing to hand back and nothing to unbalance.

  ☠️ **This branches on `Credits::countsTowardBalance()`, not `hasExpired()` — the two are not
  opposites.** A row with `has_expiry = 1` and `expiry_date = NULL` (which `CreditsAddRequest` accepted
  until `required_if` was added) falls between them: `scopeActive()` excludes it, because
  `NULL >= today` is NULL and neither branch matches, so it never appears in `getGrantAllocation()` and
  its unused count defaults to `0`; while `hasExpired()` calls it *live*, because
  `&& $this->expiry_date` short-circuits before the date compare. Branching on `hasExpired()` here
  therefore skipped the delete and fell through to `$unused <= 0` → **422 "These credits have already
  been used and can no longer be removed"** on a grant nobody had ever spent. Permanently undeletable,
  and a regression against `develop`, which just deleted the row. `countsTowardBalance()` is the
  row-level mirror of `scopeActive()`; keep the two in step, and prefer it anywhere the real question
  is "does removing this row change the balance?"
- The whole thing runs inside `DB::transaction()` with `self::where('user_id', $userId)->lockForUpdate()`
  first — holding the user's ledger for the transaction so two concurrent revokes on the same user can't
  both read the same unspent balance and each write a counter-entry for it. No-op on SQLite (no row
  locks), so this protection is real only under MySQL.

`CreditsController::destroy()`'s response now also reports what actually happened. Both branches go
through `ApiResponse`, so they carry the standard `{success, status_code, message}` envelope:

| Outcome | Status | Message key | `data` |
|---|---|---|---|
| Whole grant removed | 200 | `api.credits_deleted` | `{revoked_credits, available_credits}` |
| Only the unspent part taken | 200 | `api.credits_partially_revoked` — `":revoked"` / `":used"` | `{revoked_credits, available_credits}` |
| Nothing left to take | 422 | `api.credits_already_used` | — |

Those three keys are new in `resources/lang/en/api.php` (+3 on the indexed tree's 78). The partial
message interpolates, which is why `ApiResponse::success()` / `error()` gained a trailing
`array $replace = []` passed straight to `__()` — see [HELPERS.md](../HELPERS.md#apiresponse).

⭐ **The partial message is the whole point of the feature, so it has to reach the user.** The grant row
is still on screen with a counter-entry beside it; a flat "Credits deleted successfully" toast would
contradict what the admin is looking at. That means the SPA delete thunk must not discard the response —
see the `createPaginatedCrudSlice` note in [FRONTEND.md](../FRONTEND.md#redux).

`GET api/credits` (the list) is enriched the same way: every row carries server-computed
`used_credits` / `revoked_credits` / `remaining_credits` from `getGrantAllocation()`, so the SPA can show a grant's real
state and gray out / disable revoking what's already gone (`AddCredits.js`, `addCreditsColumns.js` — new
"Utilized" column and "Revoked" status).

⭐ **`used` and `revoked` are reported separately** (`used_credits` / `revoked_credits`, alongside
`remaining_credits`). Both draw a grant down identically and the revocation maths only ever needs
`remaining` — but only `used` is something the *user* did. They were once summed into a single `used`
figure, so a grant of 10 where the user spent 3 and an admin clawed back 7 displayed "10 utilized" and
a disabled Delete tooltip reading *"Already used by the user"*. That is a factual claim about a
person's behaviour, and it was wrong. `getGrantAllocation()` now runs two FIFO passes over the same
ordering — consumption first, then claw-backs against what each grant has left — which yields exactly
the `remaining` the single-pool version did, so `revokeGrant()` is unaffected.

⚠️ **Consumption recorded under a lapsed unlimited grant is still spread across finite grants.**
`getGrantAllocation()` excludes `is_unlimited_credit` rows from the grant pool but draws on *all-time*
`credit_consume`, so an account that spent 10 under unlimited and is then granted 10 finite credits
sees that brand-new grant reported `used: 10, remaining: 0`, and revoking it returns 422. This is
**consistent, not corrupt** — `getAvailableCredits()` already returns 0 for that account, and deleting
the grant would push granted below consumed, which is the bug this whole branch exists to prevent. It
is trap 10's unlimited case; the remedy is `credits:settle-negative-balances`, not a change here. On
`develop` the row simply deleted, which is why it can look like a regression.

☠️ **Both fields are `null`, not `0`, on a row `getGrantAllocation()` never allocated against** — an
unlimited grant, or an expired one (the allocation walks `active()` grants only). `null` means "not
computed"; `0` would claim the grant was never touched. An expired grant of 500 that was spent in full
is the case that matters: reporting `0 used / 0 remaining` made the confirm dialog read *"0 of 0 credits
have been utilized. Delete this record and remove the remaining 0?"*. The SPA mirrors the distinction —
the Utilized column renders `—` and the dialog says the record no longer counts toward the balance
rather than quoting numbers. Any new consumer must treat null and zero as different answers.

**One-time repair for accounts already carrying the old bug's hidden debt:**
`php artisan credits:settle-negative-balances` (dry run by default; `--apply` to write) walks every user
with any `credit_consume` history, and for each whose `consumed > granted` writes a `SOURCE_ADJUSTMENT`
row for the deficit — the same repair `revokeGrant()` now does proactively for the unlimited-grant case.

A pre-fix revocation is not the only way an account gets there. **Expiry produces the identical
deficit**: consumption never decays, but a grant stops counting the day it expires, so a grant that was
spent and then expired — an expired *unlimited* grant included — leaves the same hole with no admin
involved. Those accounts are meant to be settled here too.

---

## The client's copy of the balance goes stale

`GET api/user/credits` (`UserController::getUserCredits()`) returns the balance for the **authenticated
caller only** — a Super Admin cannot push a new figure to the affected user, and there is no
broadcasting in this stack. The SPA re-fetches instead: `TCV-Frontend/src/hooks/useCreditsSync.js`
(ws-397, merged into `develop` 2026-08-31) refreshes on mount, on route change, on
tab focus and on a 60 s visible-only interval, so a grant or revoke shows up without a manual page
refresh. Details and its gotchas are in [FRONTEND.md](../FRONTEND.md#the-credit-balance-is-polled-not-pushed).

**`ws-402` (merged 2026-09-07) extends the admin-facing `AddCredits` grant list, not the balance poll above.** The
per-grant "Utilized" column and "Revoked" status come from the enriched `GET api/credits` response (see
Admin revocation, above) — a page load, not a timer, so it does not add to the polling cost noted below.

⚠️ **`getAvailableCredits()` logs a warning when the balance is negative — throttled to once an hour
per user.** It has to be: this is the hottest path in the subsystem (the poll above hits it every 60s
per open tab), and the accounts that trip it are exactly the ones still awaiting
`credits:settle-negative-balances`, so an unthrottled warning is one line per tab per minute,
indefinitely, per affected account. The throttle is a `Cache::add` on
`credits:negative-balance-warned:{userId}`. It is a standing condition, not an event — if you need
per-occurrence detail, log from the write paths instead of loosening this.

☠️ **That polling multiplies the derived-balance cost.** Every call is two aggregate `SUM`s — there is no
balance column and the result must not be cached — so each open portal tab now costs one such pair per
minute on top of its normal traffic. Any work that makes `getAvailableCredits()` heavier (extra joins,
per-grant expiry logic) is now paid on a timer, not just on page load. Cheapening it is the fix;
lengthening `POLL_INTERVAL_MS` only trades freshness away.

---

## ☠️ Traps

1. **Two models, one table.** `App\Models\Credits` (live) and `App\Models\Credit` (3 fillable columns,
   no business methods) both resolve to the `credits` table. Everything real uses `Credits`. Writing
   through `Credit` bypasses `addCreditsToUser()`'s defaults (`source`, `credited_by`, `total_price`) and
   silently produces a malformed grant. Use `Credits`.
2. **The balance check is not atomic.** `assignTest()` reads the balance inside a transaction, and the
   code comments admit it "reduces (but does not eliminate) the race window". There is no row lock and
   no `SELECT … FOR UPDATE` on the aggregate. Two concurrent assigns can both pass a 1-credit check.
3. **`Credits::transactions()` is a `hasOne`** despite the plural name, and it joins on `ref_id`
   (`hasOne(Transaction::class, 'ref_id')`). Do not assume a collection.
4. **`CreditsPolicy` returns `false` for everything except `delete`.** On `develop`, `delete` allows it
   only for `source === SOURCE_MANUAL` — and ☠️ **decides purely on the row, never reading `$user`**,
   while `Route::resource('credits', …)` carries only `auth:sanctum`. Any authenticated user can
   therefore delete anyone's Manual grant by id: [S-19](../SECURITY.md#s-19). Only
   `CreditsController::destroy()` calls `authorize()`; `store()`, `index()` and `show()` do not.

   **On `ws-402` (merged 2026-09-07)** the policy checks `$user->isSuperAdmin()` **first**, then the source
   rules — who, then what. `delete` also allows a `SOURCE_REVOKED` row whose
   `original_source === SOURCE_MANUAL`, so a refund is deletable only when it traces back to money the
   user never paid for. A denial is **403** on that branch, not 500 (see
   [ERROR_HANDLING.md](../ERROR_HANDLING.md)); `destroy()` itself no longer plain-deletes even a
   Manual/eligible row — see `Credits::revokeGrant()` above.

   ⚠️ **`index()` is still unscoped even on `ws-402`** — it reads `user_id` from the request, so any
   authenticated caller can list another user's grants along with the new per-grant usage figures.
5. **`GET api/credits/{coupon-code}` is unreachable.** `Route::resource('credits', …)` is registered on
   the line *above* it, so `GET credits/{credit}` (the resource `show`) matches first and
   `checkDiscountCodeValidity()` is dead code. See [ROUTES.md](../ROUTES.md#ordering-traps).
6. ~~**`sendInvitations` spends the credits of a `user_id` taken from an unauthenticated request body.**~~
   ✅ **Fixed 2026-08-26** —
   [S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time).
   The route is now `auth:sanctum` and the spend is always billed to `$request->user()`; `user_id` is no
   longer a validated input. **A short balance still truncates instead of failing** — `sendInvitations()`
   sends only the first `credit` addresses and returns 200, so a caller can ask for 500 and be charged
   for 3 without an error.
7. **`CreditsController::show($userId)` calls `->get($userId)`**, passing an int where Eloquent expects a
   column list. It is not routed (the resource `show` is), so it is currently unreachable — do not
   "restore" it without fixing the call.
8. **`ws-402` (merged 2026-09-07): `traceConsumedOrigin()` is a read-only replay, not a stored link — trust it
   accordingly.** It has no way to know which grant funded a test if the test was sent while the user held
   an *unlimited* grant (unlimited draws from no finite grant), so that case — and any other it can't pin
   down — falls back to `SOURCE_PURCHASE`, deliberately the never-admin-deletable answer. Do not read a
   `SOURCE_PURCHASE` `original_source` as proof the user paid Stripe for it; it may just mean the trace
   gave up safely.

   📌 **The code and this note used to disagree, and the code was wrong (fixed 2026-09-07).** Excluding
   `is_unlimited_credit` rows from the FIFO walk is not sufficient on its own: for a user holding
   unlimited *and* a finite Manual grant, the walk landed inside the Manual grant and returned
   `SOURCE_MANUAL` — marking the refund admin-deletable for a test that grant never funded, which
   overstates what is revocable. `traceConsumedOrigin()` now checks whether an unlimited grant was
   active at the event's timestamp and short-circuits to `SOURCE_PURCHASE` if so. Detectable only while
   that unlimited row still exists — an admin-revoked one is hard-deleted, and then the walk applies as
   before. Pinned by `test_unlimited_wins_over_a_finite_grant_the_test_never_drew_on()`.
9. **`ws-402` (merged 2026-09-07): the admin-revoke lock is MySQL-only.** `revokeGrant()`'s
   `lockForUpdate()` is a no-op on SQLite, so the "two concurrent revokes on the same user" race it exists
   to close is only actually closed in a MySQL-backed environment (dev/QA/prod) — a SQLite test suite can
   pass while the race still exists.
10. ☠️ **`ws-402` (merged 2026-09-07): the deficit maths compares active grants to all-time consumption, and that
    is correct. Do not "fix" it.** `settleNegativeBalance()` and `credits:settle-negative-balances` both
    compute `CreditConsume::getTotalConsumed()` (all-time, no expiry notion) minus
    `getTotalUserCredit()` (`active()` grants only). It reads like a bug — two different populations —
    and it has already been reported as one: *"grants free credits to healthy accounts"*, on the
    reasoning that a grant spent in full and then expired shows a deficit while owing nothing.

    It owes plenty. Consumption never decays but the grant stops counting at expiry, so the spend stays
    on the books with nothing behind it, and `getAvailableCredits()` — the same two sums — clamps the
    result at zero. Measured on a grant of 10, spent in full, then expired:

    ```
    active granted 0 · all-time granted 10 · consumed 10 → balance 0
    user then buys 5                                     → balance 0   ← the purchase is eaten
    ```

    The `SOURCE_ADJUSTMENT` row is what stops that, and it is always **exactly** the deficit, so it
    settles the balance to zero and never above it — no spendable credit is created at the moment it
    runs. Counting expired grants on the grant side instead (the suggested "fix") leaves the hole open:
    the deficit computes to 0, nothing is written, and the next purchase still disappears. The same
    applies to `revokeGrant()`'s unlimited branch — netting an expired grant off there under-credits by
    that grant's amount and re-opens the hole it exists to close. Locked down by
    `CreditRevocationTest::test_settle_command_repairs_a_grant_spent_before_it_expired()` and
    `…_does_not_hand_back_credits_that_expired_unspent()`.
