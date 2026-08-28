# Context: Discount Codes

> Load this **instead of** reading the discount subsystem. ~900 tokens.

## Files
| File | Role |
|---|---|
| `app/Services/DiscountCodeService.php` | ⭐ `validate()`, `calculate()`, `syncRestrictions()`, `countUses()` |
| `app/Http/Controllers/DiscountCodeController.php` (234 lines) | CRUD + `stats`, `formOptions`, `validateCode`, `toggle` |
| `app/Models/DiscountCode.php` · `DiscountCodePriceTier.php` · `DiscountCodeUser.php` | |
| `app/Http/Requests/StoreDiscountCodeRequest.php` · `UpdateDiscountCodeRequest.php` · `ValidateDiscountCodeRequest.php` | |
| `app/Services/Reports/DiscountCodeReportService.php` · `app/Exports/DiscountCodeReportExport.php` | Reporting |

## Tables
`discount_codes` · `discount_code_price_tiers` · `discount_code_users` · `price_details` · `transaction_details`

---

## Routes (all `auth:sanctum`)
```
GET   api/discount-codes/stats
GET   api/discount-codes/form-options
POST  api/discount-codes/validate
PATCH api/discount-codes/{discount_code}/toggle
      api/discount-codes            ← apiResource (index/store/show/update/destroy)
```
The three literal paths are registered **before** the `apiResource`, which is what keeps
`/stats`, `/form-options` and `/validate` from being swallowed by `GET discount-codes/{discount_code}`.
**Preserve that order** — it is deliberate, and the `credits` group gets it wrong
([ROUTES.md](../ROUTES.md#ordering-traps)).

---

## Validation order (`DiscountCodeService::validate`)

Each check returns immediately with its own HTTP status, so the *first* failure is what the user sees:

| # | Check | Status |
|---|---|---|
| 1 | code exists (`strtoupper(trim($code))`, `deleted_at` null) | 404 |
| 2 | `is_active` | 400 |
| 3 | `starts_at` not in the future | 400 |
| 4 | `expires_at` not in the past | 400 |
| 5 | `amount >= minimum_order_amount` | 400 |
| 6 | credit quantity matches an attached price tier — **only if tiers are attached** | 400 |
| 7 | user **is in** `discount_code_users` → **rejected** | 403 |
| 8 | global `max_uses` not reached (`countUses($id)`) | 400 |
| 9 | per-user `max_uses_per_user` not reached (`countUses($id, $userId)`) | 400 |

Codes are matched **upper-cased and trimmed**. Store them upper-cased or lookups miss.

---

## ☠️ Traps

1. **The two restriction lists have opposite polarity.**
   - `priceTiers` is an **allow** list: attached tiers *permit* only those credit packages.
   - `users` is an **exclusion** list: `$discount->users->contains('id', $user->id)` → **fail**, with the
     message *"This discount code is not available to your account."*

   Both are populated by one method called `syncRestrictions($discount, $userIds, $priceTierIds)` from one
   admin form field named `user_ids`. Given the naming and the tier behaviour, the user list looks
   **inverted** (an allow-list implemented as a deny-list) — but the code as written denies listed users,
   and this KB does not assume which was intended. **Confirm the product intent before changing it**; the
   fix is one `!` and it flips who can use every existing code.

2. **Usage is counted from `transaction_details`, not from a counter.** `countUses()` aggregates, so
   `max_uses` is only as accurate as the transaction records. A payment that charged but never reached
   `POST api/payment/confirm` ([BILLING_CONTEXT](BILLING_CONTEXT.md)) does not count toward the limit.

3. **The usage check is not atomic.** Between `validate()` and `confirmPayment()` nothing holds a lock, so
   two concurrent redemptions can both pass a `max_uses` check on the last remaining use.

4. **`value` and `minimum_order_amount` are `decimal:2` casts** — they arrive as strings from Eloquent.
   `$amount < $discount->minimum_order_amount` works via PHP's numeric-string comparison, but do not
   assume you are holding a float.

5. **Validation happens twice on different inputs.** `POST api/discount-codes/validate` validates against
   a client-supplied `amount`/`credits`; `POST api/payment/initialize` validates again with the real
   figures. Only the second one is authoritative — never grant a discount from the first call's result.
