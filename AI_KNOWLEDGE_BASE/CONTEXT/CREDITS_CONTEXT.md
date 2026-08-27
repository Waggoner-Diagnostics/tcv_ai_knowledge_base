# Context: Credits

> Load this **instead of** reading the credits subsystem. ~1.4k tokens. Credits are the product's
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
| `credits` | quantity granted |
| `source` | `0 = SOURCE_MANUAL` · `1 = SOURCE_PURCHASE` · `2 = SOURCE_REVOKED` |
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
| `TestInvitationController::sendInvitations()` | at **send** time, per email | 1 per invited address | `test_invitation` |
| `TestAssignmentService` (via `TestController::assignTest()`) | at **assign** time — *unless* `test_invitation_id` is present | 1 | ⚠️ `test_completion` |
| — | never actually at completion | — | — |

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
   rely on `auth:sanctum` alone.
5. **`GET api/credits/{coupon-code}` is unreachable.** `Route::resource('credits', …)` is registered on
   the line *above* it, so `GET credits/{credit}` (the resource `show`) matches first and
   `checkDiscountCodeValidity()` is dead code. See [ROUTES.md](../ROUTES.md#ordering-traps).
6. **`sendInvitations` spends the credits of a `user_id` taken from an unauthenticated request body.**
   [S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time) —
   the highest-impact bug touching this subsystem.
7. **`CreditsController::show($userId)` calls `->get($userId)`**, passing an int where Eloquent expects a
   column list. It is not routed (the resource `show` is), so it is currently unreachable — do not
   "restore" it without fixing the call.
