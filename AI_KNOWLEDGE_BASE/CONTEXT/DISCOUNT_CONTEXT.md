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

**Bounds come from the pricing table, not from constants.** An order of N credits falls in exactly one
tier and costs `N × that tier's rate`, so `getOrderBounds(priceTiers)` searches **every** tier for each
bound:
- `min` = `Math.min(...tiers.map(t => t.from × t.price_per_credit))`
- `max` = `Math.max(...tiers.map(t => t.to × t.price_per_credit))`

☠️ **Neither bound can be read off the first or last tier.** Nothing in `validatePricingTiers` requires
the rate to fall as volume rises, so a mid-table tier can bill more than the top one — on the seeded
table the middle tier's `999 × $8.85 = $8,841.15` beats the top tier outright. Taking the tier with the
largest `to` gets today's seed wrong by $2.95 and a re-rated table by hundreds.

☠️ **An open-ended top tier has no ceiling at all.** `to >= 99999` is the sentinel `CreditPage` renders
as “∞”; there is no dearest order behind it, so `max` comes back **`null`** and every field it would
have bounded is left unbounded above. Callers must test `max !== null` rather than compare against it.

The two bounds are **not** interchangeable, and this is the distinction the field split turns on:

| Field | Floor | Ceiling |
|---|---|---|
| `minimum_order_amount` | `bounds.min` — a threshold under the cheapest order is a no-op, always met | `bounds.max` — above the dearest order it is never met |
| **fixed** `value` | **none beyond `0.01`** | `bounds.max` — more could never be redeemed in full |

☠️ **Do not floor the fixed discount to `bounds.min`.** That figure bounds what a customer *spends*,
not what can be taken *off* it — flooring it makes “$10 off” impossible to create and locks every legacy
code under the floor out of being re-saved at all, even when the admin only touched the description. The
API agrees: `value` is `min:0.01` there, with no tie to the price table. Percentages are bounded by
constants instead: **0.1 – 100**. With no tiers loaded everything falls back (`min:0`, no max).

Each bound is quoted in its field's placeholder so the hint cannot drift from the rule — **without the
`$`**, because the `dc-adorn` chip is already showing one a few pixels to the left. `money()` carries the
symbol and belongs in error messages; `amount()` drops it and belongs inside the money fields.

☠️ **Keystroke filtering enforces the ceiling only — never the floor.** `exceedsLimits()` refuses a
keystroke that would exceed the max, add a third decimal, or run past the length of the ceiling written
out (`$4,500.00` → 8 characters is a typo). A floor is deliberately *not* applied while typing: `0.1`
has to be reachable by typing `0` then `0.`, and `29.60` by passing through `2` and `29`. The floor is
reported by the Yup schema on submit. Adding a floor to `exceedsLimits()` makes the smallest valid
discount impossible to enter.

The decimal count is taken from the **raw text**, not the parsed number — `29.605` parses to an ordinary
float and by then the extra digit is invisible. The length cap lives here because
`<input type="number">` ignores `maxLength`.

☠️ **`minimum_order_amount` has two spellings of “none”: blank and a stored `0`.** The column defaults
to `0` and the modal writes `0` back whenever the field is left empty, so `0` is what every code carries
that no threshold was ever chosen for. Both are transformed to `null` *before* the bounds apply
(`blankOrZeroToNull`) and a stored `0` is loaded back as a blank field showing “No minimum” — otherwise
reopening such a code fails validation on a field nobody touched. For the same reason the native `min`
attribute stays at `0` whatever the tiers say: Formik does not set `noValidate`, and a `min` of `$29.60`
would have the browser block the submit before the schema ever saw the `0`.

☠️ **Switching discount type resets the whole form**, keeping only `code` (and its duplicate warning):
20% off and $20 off are different offers, and every limit, date and restriction was chosen against the
type being abandoned. `resetForm()` also clears `touched`/`errors`, which is what stops a stale
"Cannot exceed 100%." surviving the switch. **`BLANK_VALUES` must therefore list every field the form
owns** — add a field without adding it there and that field silently survives a type switch. Because the
reset reaches well past the two type-specific fields — it reactivates the code, clears its dates and
empties the **user deny-list**, widening who may redeem — it is confirmed through `window.confirm()`
whenever the form has anything to lose, and done silently when it does not.

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
is the opposite of how it behaved on `develop`.

> ☠️ **`ws-392` is not the indexed tree.** This KB is generated from `ws-398` (= `develop` + the
> intersex-gender commit), so the **`develop`** column below is what the indexes describe. The branch is
> still open and unmerged — read the `ws-392` column as "if ws-392 merges".
> See [README](../README.md#-ws-398-delta--what-is-indexed-and-what-is-no-longer).

| | `develop` (≤ 2026-08-26) | **`ws-392`** |
|---|---|---|
| `discount_codes.code` index | **unique**, spanning trashed rows | plain index (`2026_08_27_000001`) **+ `discount_codes_code_active_unique`** (`2026_08_31_000001`) — unique over live rows only |
| Where uniqueness is enforced | the database | the database, **narrowed to live rows**; `Rule::unique(…)->whereNull('deleted_at')` in `StoreDiscountCodeRequest` *and* `UpdateDiscountCodeRequest` supplies the 422 |
| `codeAvailable()` scope | `withTrashed()` — a deleted code stayed reserved forever | default scope — **live rows only** |

☠️ **The FormRequests are the message, not the guarantee.** `Rule::unique` is a SELECT with no lock and
no transaction around `DiscountCode::create()`, so two concurrent `POST api/discount-codes` can both
pass it. What stops the second row is `discount_codes_code_active_unique`: a writer that skips the
FormRequests (a seeder, a console command, a direct `DiscountCode::create()`) gets a `QueryException`
instead of a duplicate. Keep the `whereNull('deleted_at')` rule on **both** requests — dropping it from
either turns a friendly 422 into an unhandled 500, and widening it past `deleted_at` re-reserves
deleted names. Covered by `tests/Feature/DiscountCodes/DiscountCodeUniquenessTest.php`.

Redemption looks the code up with `->orderBy('id')` (`DiscountCodeService::validate`). At most one live
row can hold a name, so the order is belt-and-braces — but `countUses()` budgets `max_uses` per **row
id**, so an unordered `first()` would mean an arbitrary usage counter if a duplicate ever did appear.

The two migrations are ordered so the table is never left unconstrained: `2026_08_27_000001` only adds
the plain index, then `2026_08_31_000001` creates the live-only unique index **and drops the blanket one
last**. `down()` reverses in the same order, restoring the blanket index before removing its replacement.
That restore is the step that can fail: it only applies while no code is shared by a live and a trashed
row, so once a name has been reused after a delete, rolling back `2026_08_31_000001` needs one side
renamed or purged first. `2026_08_27_000001` rolls back cleanly either way.

`codeAvailable()` still answers `available: true` for an empty `code`, and `ignore_id` still excludes the
row being edited.

5. **Validation happens twice on different inputs.** `POST api/discount-codes/validate` validates against
   a client-supplied `amount`/`credits`; `POST api/payment/initialize` validates again with the real
   figures. Only the second one is authoritative — never grant a discount from the first call's result.
