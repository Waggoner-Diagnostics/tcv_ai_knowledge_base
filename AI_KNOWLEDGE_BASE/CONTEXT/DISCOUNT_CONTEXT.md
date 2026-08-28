# Context: Discount Codes

> Load this **instead of** reading the discount subsystem. ~900 tokens.

## Files
| File | Role |
|---|---|
| `app/Services/DiscountCodeService.php` | ⭐ `validate()`, `calculate()`, `syncRestrictions()`, `countUses()` |
| `app/Http/Controllers/DiscountCodeController.php` (255 lines) | CRUD + `stats`, `formOptions`, `validateCode`, `toggle`, `codeAvailable` |
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
GET   api/discount-codes/code-available     ?code=…&ignore_id=…   (added 2026-08-26)
POST  api/discount-codes/validate
PATCH api/discount-codes/{discount_code}/toggle
      api/discount-codes            ← apiResource (index/store/show/update/destroy)
```
The four literal paths are registered **before** the `apiResource`, which is what keeps
`/stats`, `/form-options`, `/code-available` and `/validate` from being swallowed by
`GET discount-codes/{discount_code}`.
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

## Client-side form rules (`DiscountCodeModal.jsx`, `ws-392`)

The admin create/edit drawer enforces its own bounds *before* the API sees them. None of this is
mirrored server-side — `StoreDiscountCodeRequest` only checks `numeric, min:0.01` — so these are UI
guard-rails, not a contract. A non-SPA client can still post values the drawer would refuse.

**Bounds come from the pricing table, not from constants.** `getOrderBounds(priceTiers)` derives:
- `min` = cheapest tier's `from × price_per_credit`
- `max` = dearest tier's `to × price_per_credit`

Because the rate falls as volume rises, **neither bound can be read off credits or price alone** — each
has to come from the tier it belongs to. Those two figures bound both the **fixed** discount value and
`minimum_order_amount`, and are quoted in each field's placeholder so the hint cannot drift from the
rule. With no tiers loaded both fall back (`min:0`, no max). Percentages are bounded by constants
instead: **0.1 – 100**.

☠️ **Keystroke filtering enforces the ceiling only — never the floor.** `exceedsLimits()` refuses a
keystroke that would exceed the max, add a third decimal, or run past the length of the ceiling written
out (`$4,500.00` → 8 characters is a typo). A floor is deliberately *not* applied while typing: `0.1`
has to be reachable by typing `0` then `0.`, and `29.60` by passing through `2` and `29`. The floor is
reported by the Yup schema on submit. Adding a floor to `exceedsLimits()` makes the smallest valid
discount impossible to enter.

The decimal count is taken from the **raw text**, not the parsed number — `29.605` parses to an ordinary
float and by then the extra digit is invisible. The length cap lives here because
`<input type="number">` ignores `maxLength`.

☠️ **Switching discount type resets the whole form**, keeping only `code` (and its duplicate warning):
20% off and $20 off are different offers, and every limit, date and restriction was chosen against the
type being abandoned. `resetForm()` also clears `touched`/`errors`, which is what stops a stale
"Cannot exceed 100%." surviving the switch. **`BLANK_VALUES` must therefore list every field the form
owns** — add a field without adding it there and that field silently survives a type switch.

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

**Deleting a discount code releases its name for reuse** — reversed on `ws-392` (2026-08-27), and this
is the opposite of how it behaved on `develop`:

| | `develop` (≤ 2026-08-26) | **`ws-392`** |
|---|---|---|
| `discount_codes.code` index | **unique**, spanning trashed rows | plain index — unique dropped by `2026_08_27_000001_drop_unique_index_on_discount_codes_code` |
| Where uniqueness is enforced | the database | **validation only**: `Rule::unique(…)->whereNull('deleted_at')` in `StoreDiscountCodeRequest` *and* `UpdateDiscountCodeRequest` |
| `codeAvailable()` scope | `withTrashed()` — a deleted code stayed reserved forever | default scope — **live rows only** |

☠️ **Uniqueness is no longer enforced by the database on this branch.** Nothing but the FormRequests
stops two live rows sharing a code — any writer that skips them (a seeder, a console command, a direct
`DiscountCode::create()`) can now create a duplicate that redemption will resolve arbitrarily. Keep the
`whereNull('deleted_at')` rule on **both** requests; dropping it from either reopens the hole.

The `down()` migration only applies while no code is shared by a live and a trashed row — once a name
has been reused after a delete, the rollback fails until one side is renamed or purged.

`codeAvailable()` still answers `available: true` for an empty `code`, and `ignore_id` still excludes the
row being edited.

5. **Validation happens twice on different inputs.** `POST api/discount-codes/validate` validates against
   a client-supplied `amount`/`credits`; `POST api/payment/initialize` validates again with the real
   figures. Only the second one is authoritative — never grant a discount from the first call's result.
