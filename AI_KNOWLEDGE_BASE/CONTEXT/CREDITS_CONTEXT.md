# Context: Credits

> Load this **instead of** reading the credits subsystem. ~1.7k tokens. Credits are the product's
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
| `app/Policies/CreditsPolicy.php` | Only `delete` returns anything but `false` |
| `app/Console/Commands/SettleNegativeCreditBalances.php` | **`ws-402` only.** `credits:settle-negative-balances`, one-time repair for pre-fix hidden debt |

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
| `credits` | quantity granted — **negative on `ws-402` (unmerged)** for a `SOURCE_ADMIN_REVOKED` counter-entry |
| `source` | `0 = SOURCE_MANUAL` · `1 = SOURCE_PURCHASE` · `2 = SOURCE_REVOKED` · on `ws-402` (unmerged) also `3 = SOURCE_ADMIN_REVOKED` · `4 = SOURCE_ADJUSTMENT` |
| `original_source` | **`ws-402` only, column doesn't exist on `develop`.** Meaningful only on a `SOURCE_REVOKED` row: which grant (Manual/Purchase) funded the test this refund covers. Null otherwise |
| `has_expiry` / `expiry_date` | expiry is opt-in; `expiry_date >= today` to count |
| `is_unlimited_credit` | see below |
| `coupon_code`, `price_per_credit`, `total_price`, `credited_by` | provenance |

### Spend rows (`credit_consume`)
| Column | Meaning |
|---|---|
| `credits_used` | quantity spent |
| `event_type` | `'test_invitation'` or `'test_completion'` |
| `ref_id` | **JSON array** (cast to `array`) of the ids the spend covers |

`CreditConsume::consume()` records **even for unlimited holders**, deliberately, for audit.

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

**`ws-402` (unmerged) adds provenance to that refund row.** Both methods now call
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

### Admin revocation (`DELETE api/credits/{id}`, `CreditsController::destroy()`)

This is the opposite direction from a refund — an admin taking back credits they (or a purchase) granted
— and on `develop` it is a straight `$credits->delete()`: the whole grant row is removed, including
whatever part of it the user had already spent. Every balance in the app is `granted − consumed` clamped
at zero, so deleting a partly-spent grant pushes `granted` below `consumed` and the clamp hides the
resulting deficit — invisible right up until the user's *next* grant or purchase silently pays it off.

**`ws-402` (unmerged) replaces this with `Credits::revokeGrant($grant)`,** which takes back only the
**unspent** part:

- `Credits::getUnusedCreditsForGrant($grant)` reads `Credits::getGrantAllocation($userId)` — a FIFO
  spread of total consumption (tests) *and* prior admin claw-backs across the user's active grants,
  oldest first — to find how much of this specific grant is still unspent.
- Untouched (`unused === credits`) → the row is deleted outright, same as today.
- Partly spent (`0 < unused < credits`) → the row is **kept**, and a new negative
  `SOURCE_ADMIN_REVOKED` row (`credits = -$unused`) is written alongside it. The original grant still
  shows what was given; the counter-entry shows what was taken back. Nothing is ever deleted or edited in
  place.
- Fully spent (`unused === 0`) → nothing to take back. The controller returns **422** ("These credits
  have already been used and can no longer be removed."), not a silent no-op.
- **Unlimited grant** → still deleted outright (there is no "unspent part" of unlimited), but losing it
  turns everything consumed while it was active into a debt against the user's finite credits — the same
  hidden-deficit problem as above, just triggered by losing the *unlimited flag* instead of a finite
  grant. `revokeGrant()` calls `Credits::settleNegativeBalance($userId)` afterward, which writes a
  `SOURCE_ADJUSTMENT` row for exactly the deficit if one now exists.
- **Already-expired grant** → deleted outright; an expired grant already counted for nothing, so there is
  nothing to hand back and nothing to unbalance.
- The whole thing runs inside `DB::transaction()` with `self::where('user_id', $userId)->lockForUpdate()`
  first — holding the user's ledger for the transaction so two concurrent revokes on the same user can't
  both read the same unspent balance and each write a counter-entry for it. No-op on SQLite (no row
  locks), so this protection is real only under MySQL.

`CreditsController::destroy()`'s response now also reports what actually happened
(`{data: {revoked_credits, available_credits}}`, plus a message naming the split when only part of a
grant was taken back), and `GET api/credits` (the list) is enriched the same way: every row now carries
server-computed `used_credits` / `remaining_credits` from `getGrantAllocation()`, so the SPA can show a
grant's real state and gray out / disable revoking what's already gone (`AddCredits.js`,
`addCreditsColumns.js` — new "Utilized" column and "Revoked" status).

**One-time repair for accounts already carrying the old bug's hidden debt:**
`php artisan credits:settle-negative-balances` (dry run by default; `--apply` to write) walks every user
with any `credit_consume` history, and for each whose `consumed > granted` writes a `SOURCE_ADJUSTMENT`
row for the deficit — the same repair `revokeGrant()` now does proactively for the unlimited-grant case.

---

## The client's copy of the balance goes stale

`GET api/user/credits` (`UserController::getUserCredits()`) returns the balance for the **authenticated
caller only** — a Super Admin cannot push a new figure to the affected user, and there is no
broadcasting in this stack. The SPA re-fetches instead: `TCV-Frontend/src/hooks/useCreditsSync.js`
(ws-397, 2026-08-28 — committed, *not yet merged or deployed*) refreshes on mount, on route change, on
tab focus and on a 60 s visible-only interval, so a grant or revoke shows up without a manual page
refresh. Details and its gotchas are in [FRONTEND.md](../FRONTEND.md#the-credit-balance-is-polled-not-pushed).

**`ws-402` (unmerged) extends the admin-facing `AddCredits` grant list, not the balance poll above.** The
per-grant "Utilized" column and "Revoked" status come from the enriched `GET api/credits` response (see
Admin revocation, above) — a page load, not a timer, so it does not add to the polling cost noted below.

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
4. **`CreditsPolicy` returns `false` for everything except `delete`**, and `delete` allows it only for
   `source === SOURCE_MANUAL`. Purchased and revoked grants can never be deleted. Only
   `CreditsController::destroy()` calls `authorize()`; `store()`, `index()` and `show()` do not — they
   rely on `auth:sanctum` alone. **On `ws-402` (unmerged)** `delete` also allows a `SOURCE_REVOKED` row
   whose `original_source === SOURCE_MANUAL` — a refund is deletable only when it traces back to money
   the user never paid for. A denial is **403** on that branch, not 500 (see
   [ERROR_HANDLING.md](../ERROR_HANDLING.md)); `destroy()` itself no longer plain-deletes even a
   Manual/eligible row — see `Credits::revokeGrant()` above.
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
8. **`ws-402` (unmerged): `traceConsumedOrigin()` is a read-only replay, not a stored link — trust it
   accordingly.** It has no way to know which grant funded a test if the test was sent while the user held
   an *unlimited* grant (unlimited draws from no finite grant), so that case — and any other it can't pin
   down — falls back to `SOURCE_PURCHASE`, deliberately the never-admin-deletable answer. Do not read a
   `SOURCE_PURCHASE` `original_source` as proof the user paid Stripe for it; it may just mean the trace
   gave up safely.
9. **`ws-402` (unmerged): the admin-revoke lock is MySQL-only.** `revokeGrant()`'s
   `lockForUpdate()` is a no-op on SQLite, so the "two concurrent revokes on the same user" race it exists
   to close is only actually closed in a MySQL-backed environment (dev/QA/prod) — a SQLite test suite can
   pass while the race still exists.
