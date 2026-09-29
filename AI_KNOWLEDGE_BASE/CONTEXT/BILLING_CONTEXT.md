# Context: Payments & Stripe

> Load this **instead of** reading the payment subsystem. ~1.6k tokens. Money buys **credits**; credits
> buy tests ([CREDITS_CONTEXT](CREDITS_CONTEXT.md)).

## Files
| File | Role |
|---|---|
| `app/Services/StripeService.php` | ⭐ Direct Stripe SDK wrapper — customers, payment methods, intents, ACH |
| `app/Services/PaymentManager.php` | Static provider locator — **has a latching bug, see trap 1** |
| `app/Services/PaymentProviders/PaymentProviderInterface.php` · `BasePaymentProvider.php` · `StripeProvider.php` | The provider abstraction |
| `app/Http/Controllers/PaymentController.php` (280 lines) | Provider-agnostic surface + credit history |
| `app/Http/Controllers/StripePaymentController.php` (410 lines) | Stripe-specific surface |
| `app/Services/DiscountCodeService.php` | Applied during `initializePayment` — [DISCOUNT_CONTEXT](DISCOUNT_CONTEXT.md) |
| `app/Models/Transaction.php` · `TransactionDetail.php` · `UserStripeDetail.php` | Persistence |

## Tables
`transactions` · `transaction_details` · `user_stripe_details` · `credits` · `price_details` · `discount_codes`

---

## Two parallel payment surfaces

This is the thing to understand before touching anything here. **There are two of them**, built at
different times, both live:

| | Legacy: `api/stripe/*` | Current: `api/payment/*` |
|---|---|---|
| Controller | `StripePaymentController` | `PaymentController` |
| Abstraction | none — calls `StripeService` directly | `PaymentManager` → `PaymentProviderInterface` |
| Guard | ✅ `auth:sanctum` on `develop` since 2026-09-07 ([S-17](../SECURITY.md#s-17--five-stripe-payment-endpoints-were-public-on-develop) fixed); previously fully public | `auth:sanctum` |
| Endpoints | `create-payment-intent`, `confirm-payment`, `payment-methods`, `payment-methods/set-default`, `payment-methods/{id}` (DELETE) | `setup-intent`, `providers`, `initialize`, `confirm`, `webhook/{provider}` |

✅ **`api/stripe/*` is guarded on `develop` since 2026-09-07** — all five routes moved into the
`auth:sanctum` group ([S-17](../SECURITY.md#s-17--five-stripe-payment-endpoints-were-public-on-develop)),
which is what took public `api/*` from 20 to 15.

☠️ **What it looked like before is worth remembering, because the failure mode was deceptive.** The five
routes sat outside every middleware group alongside genuinely public routes like `/login`. Every handler
starts with `Auth::user()`, and unauthenticated that is `null` — `StripeService`'s signatures are typed
`User $user`, so the call threw a `TypeError` that the controller's `catch` swallowed into a **500
containing the exception message**: exposed and non-functional at the same time. Only a `TypeError` stood
between an anonymous caller and these endpoints, which is why nobody noticed. The fix was made at the
routing level rather than by adding a null check per method — follow that shape.

Treat `api/stripe/*` as the deprecated surface regardless; build on `api/payment/*`.

⚠️ **That split is why `ws-480`'s unlimited-credit refusal first did not bite.** It was added to
`StripePaymentController::createPaymentIntent()` alone, on the surface nothing calls; it now sits on all
four routed handlers across both surfaces — trap 9 below. **Any rule that has to hold server-side on the
purchase path needs a copy on each surface**, and on both the initialize and confirm halves of `api/payment/*`.

`app/Services/PaymentProviders/` also carries commented-out routes for `partialRefund` / `refund` in
`routes/api.php` — refunds exist in the controller but are **not routed**.

---

## The current flow

```
POST api/payment/setup-intent            → StripeProvider::createSetupIntent()   (save a card)
GET  api/payment/providers               → PaymentManager::getAvailableProviders()
POST api/payment/initialize  {provider, amount, credits, discount_code?, …}
        ├─ DiscountCodeService::validate() + ::calculate()
        └─ provider->initializePayment()  → PaymentIntent / client_secret
POST api/payment/confirm     {provider, …}
        └─ provider->confirmPayment()     → on success: Transaction + Credits grant (SOURCE_PURCHASE)
POST api/payment/webhook/{provider}      → provider->handleWebhook()
POST api/payment/complete-free-order     → $0 orders only, no Stripe  (ws-451, unmerged — trap 10)
```

`StripeService::createOrGetCustomer()` is also called on **every successful login**
([AUTH_CONTEXT](AUTH_CONTEXT.md)) — failures are logged and swallowed.

Supported methods (`StripeProvider::getSupportedMethods()` / the `createPaymentIntent` validation):
`card`, `digital_wallet`, `ach`, `bank_transfer`, `amazon_pay`, `google_pay`, `apple_pay`.

---

## ☠️ Traps

### 1. `PaymentManager` latches on the first provider, permanently
```php
public static function initialize(?string $selectedProvider = null): void
{
    if (self::$initialized) return;          // ← never re-enters
    …
    self::$initialized = true;
}
```
`self::$initialized` and `self::$providers` are **static**. The first call wins for the whole PHP
process:
- `getProviders()` calls `getAvailableProviders()`, which reads config directly and works — but
  `getActiveProviders()` calls `initialize()` with **no** argument, which sets `$initialized = true`
  while registering **nothing**, so every later `getProvider($name)` returns `null`.
- Under php-fpm each request is a fresh process, so this mostly hides. Under a queue worker, Octane, or
  any long-lived runtime it becomes a cross-request bug.

If you add a second provider, fix this first.

### 2. The Stripe webhook cannot work today
Two independent reasons:

- **The route is inside the `auth:sanctum` group.** `POST api/payment/webhook/{provider}` is registered
  among the authenticated routes, and Stripe cannot present a Sanctum bearer token. Stripe's delivery
  gets a 401.
- **The signature is verified against a re-encoded body.**
  ```php
  \Stripe\Webhook::constructEvent(
      json_encode($data),                       // $data === $request->all()
      request()->header('stripe-signature'),
      … webhook_secret
  );
  ```
  Stripe signs the **raw request bytes**. `json_encode(json_decode($raw))` is not byte-identical (key
  order, escaping, whitespace), so `constructEvent` rejects it. The correct source is
  `$request->getContent()`.

- And even on success, `handleWebhook()` **only logs** the event — no fulfilment, no credit grant, no
  transaction update. Everything is driven by the synchronous `confirm` call instead.

Conclusion: **credits are granted in the `confirm` request path, not by a webhook.** If the browser dies
between Stripe's charge and `POST api/payment/confirm`, the customer is charged and gets no credits, and
nothing reconciles it. Know this before designing anything that assumes webhook fulfilment.

### 3. `config('services')` is scanned as if every entry were a payment provider
`PaymentManager::getAvailableProviders()` iterates **all** of `config/services.php` and tries
`App\Services\PaymentProviders\{Ucfirst(name)}Provider`. Today only `StripeProvider` exists, so only
`stripe` matches — but adding a service called e.g. `square` to that config file would silently
enrol it as a payment provider if a matching class ever appeared. Keep provider config and third-party
credentials mentally separate even though they share one file.

### 4. Money types are inconsistent
`transactions.amount` / `refunded_amount` are `decimal:2` (good). `organizations.registration_fee_paid`
is cast to **`float`** (bad). `DiscountCodeService::validate()` takes `float $amount`. Do not introduce
new float money; follow the `decimal:2` cast.

### 5. Refund endpoints exist but are not routed
`partialRefund()` and `refund()` are implemented in `StripePaymentController`, with FormRequests
(`PartialPaymentRequest`, `RefundPaymentRequest`), but their routes are **commented out** in
`routes/api.php`. Refunds today are a Stripe-dashboard operation. Uncommenting them puts two unguarded
money-moving endpoints on a public group — move them under `auth:sanctum` first.

### 6. `new_balance` in the confirm response is always `null`
`StripeProvider::confirmPayment()` returns `'new_balance' => $user->credits`. `User` has no `credits`
column, attribute or relation — the balance is derived
([CREDITS_CONTEXT](CREDITS_CONTEXT.md)). The field is therefore `null` on every success.
A client must re-read `GET api/user/credits` instead of trusting it.

Related naming hazard in the same path: `createTransactionRecord()` receives the **payment method id**
under the key `payment_intent_id` and maps it to `payment_method`. It is correct, just badly named —
don't "fix" it by swapping the values.

### 7. `paymentCallback` is a `routes/web.php` route
`GET /payment/callback` (named `payment.callback`) is the only non-`api/` route with a controller. It is
public and session-based, unlike everything else. See [ROUTES.md](../ROUTES.md).

### 8. ☠️ The SPA checkout gates the Pay button on `billingInfo`, and a *validation error* lives in it

The billing form is client-only state — one `billingInfo` object in
`pages/UserPannel/CheckOutPage/Checkout.js`, handed whole to `components/PaymentForm/PaymentForm.js`,
which computes `isBillingComplete` from it and disables Pay. Nothing on the backend validates these
fields; they become Stripe `billing_details` on the intent and nothing else.

**`ws-407` (on `ws-407`, not yet on `develop`) made Phone optional there**, and the shape of the fix is
the part to remember:

- `isBillingComplete` no longer requires `billingInfo.phone`; it requires `!billingInfo.phoneError`.
  So `billingInfo` now mixes field values with a **validation flag under the same roof**, and any new
  `*Error` key you add to that object is invisible to the gate unless you also add it here. A blank
  phone is valid; a half-typed one blocks Pay.
- ☠️ **Three places write `billingInfo.phone`, and every one of them must normalize *and* re-validate.**
  The initial `useState` (from `user?.phone_no`), the pre-fill effect (which re-runs whenever `user` or
  `countries` changes), and the change handler. The PR review of 2026-09-11 caught the first two doing
  neither: a profile phone of `123` reached Stripe as `billing_details.phone` with `phoneError`
  `undefined`, and since most users never touch the field, that was the *common* path, not the edge one.
  The pre-fill also has to set `phoneError` explicitly rather than let `...prev` carry the old one
  forward, or a stale error sits under a valid number and keeps Pay disabled.
- The rules themselves live in **`src/utils/validation.js`**, not in the component:
  `normalizePhone()` (clean + cap), `getPhoneError()` (`''` or the message), `phoneForSubmit()`
  (submit/persist shape), and the `PHONE_MIN_DIGITS` / `PHONE_MAX_DIGITS` / `PHONE_MAX_LENGTH` /
  `PHONE_ERROR_MESSAGE` constants. Settings ▸ Profile uses the same three functions —
  **do not add a second phone rule**, in either screen.
- Two caps, both enforced by **truncation, never by rejecting the change**: 15 **digits** (E.164) and
  20 **characters** (`users.phone_no` is a `varchar(20)` and `UpdateProfileRequest` validates `max:20`;
  `maxLength` on the input is `PHONE_MAX_LENGTH` so the attribute and the handler cannot disagree).
  The digit cap counts digits so the separators in `+1 (234) 567-8900` don't eat into the allowance —
  but heavy formatting can still hit 20 characters first, and widening the column is the only way past
  that. ⚠️ The pre-fix handler did `if (digitCount > 15) return prev`, which **froze the field**: once
  state held more than 15 digits (reachable only via the un-validated pre-fill above), every
  single-character edit was rejected too, because deleting one character from 20 digits still leaves 19.
  React then restored the DOM value, so the input looked broken with no error on screen.
- `phone` is sent as `phoneForSubmit(billingInfo.phone) || undefined`. Stripe rejects
  `billing_details.phone: ""` — omit the key, don't send an empty string. `phoneForSubmit` is what makes
  the guard correct: separator-only input like `()` is zero digits, so it passes validation as "blank",
  but it is still **truthy** and used to travel to Stripe as a phone number. It collapses to `''` at the
  boundary only — the field keeps what was typed, because blanking state on every digit-less keystroke
  would swallow a leading `(`.
- ⚠️ The phone branch does its own `setBillingInfo(prev => …)` and **returns early**. Adding another
  field that needs derived state means a second early return, not an extra key on a shared `updates`
  object — the old shape, which computed `updates` outside the updater, was the impure-updater bug this
  replaced.
- Guard rail: `src/utils/validation.test.js` (12 tests) covers both regressions above — the frozen
  field and the separator-only value — plus the caps and the blank-is-valid rule. Run it before touching
  any of them ([TESTING.md](../TESTING.md)).

The form pre-fills from `auth.user` (`user.phone_no`, `user.state_id`, …), which is why the profile-save
sync in trap 9 of [AUTH_CONTEXT](AUTH_CONTEXT.md#-traps) matters here.

Nothing on the backend enforces a phone **format** on either path: `UpdateProfileRequest` has
`['nullable', 'string', 'max:20']` and no format rule, and the payment path never reads `phone` at all.
The SPA helper is the only guard, which is why it is shared rather than per-screen.

### 9. ☠️ The unlimited-purchase refusal is on the deprecated surface (`ws-480`)

> ✅ **Closed 2026-09-17** — the refusal now covers all four routed purchase handlers; see *Status
> 2026-09-17* below. The heading is kept because other pages link to this anchor, and the trap it names
> is still the one to learn from: a rule on `api/stripe/*` alone binds nothing.

`ws-480` (PR #254, merge `10a8ae73`, on `develop` 2026-09-18; the sha `8d247f8c` cited here is the
pre-review state) stops an unlimited-credit account from buying
more credits. **As first written** the server-side half was a single early return in
`StripePaymentController::createPaymentIntent()` — which is the trap; see *Status 2026-09-17* below for
the shape it ended up in. The refusal itself reads:

```php
if (Credits::hasUnlimited($user->id)) {
    Log::info('Blocked credit purchase for unlimited-credit account', ['user_id' => $user->id]);
    return response()->json(['message' => 'Your account has unlimited credits, …'], 422);
}
```

**Returned, not thrown** — deliberately. The method's `catch` turns any exception into a 500 *"Payment
failed"*, which reads to a customer as an outage rather than a deliberate refusal. Any other refusal
added to these handlers has to take the same shape, and the identity comparison has to stay `===` against
the string ([CREDITS_CONTEXT](CREDITS_CONTEXT.md#unlimited-is-a-string)).

☠️ **That method is `POST api/stripe/create-payment-intent` (`API-091`) — the legacy surface, which the
SPA does not call.** Per *Two parallel payment surfaces* above, buying credits in the portal runs
`POST api/payment/initialize` → `POST api/payment/confirm` on `PaymentController`, and **as first written
neither had an unlimited check**. So the guard could not fire on the path that takes money: the
enforcement a customer actually met was `CreditPage`/`Checkout` hiding the flow
([FRONTEND.md](../FRONTEND.md#credit-purchase-gate-ws-480)), and a request that skipped the SPA
still reached `BasePaymentProvider::createTransactionRecord()`, which wrote the grant and the
transaction as usual.

### Status 2026-09-17 — closed on the branch; the refusal now covers all four routed money paths

Found in review of `ws-480` and fixed on the same branch as **`b081b618`** ("ws-480 PR review change",
pushed to `origin/ws-480`). The shape is now:

| Handler | Route | Why it needs its own copy |
|---|---|---|
| `PaymentController::initializePayment()` | `POST api/payment/initialize` | the live purchase path — this is the one the SPA calls |
| `PaymentController::confirmPayment()` | `POST api/payment/confirm` | separate request; **only confirm writes the grant**, via `BasePaymentProvider::createTransactionRecord()` |
| `StripePaymentController::createPaymentIntent()` | `POST api/stripe/create-payment-intent` | legacy, no SPA caller, still routed under `auth:sanctum` |
| `StripePaymentController::confirmPayment()` | `POST api/stripe/confirm-payment` | legacy, still routed, calls `Credits::addCreditsToUser()` directly |

Three points carried out of that fix:

**The predicate is now `Credits::hasUnlimited(int $userId): bool`**, not a `=== 'Unlimited'` comparison at
each call site. It wraps the `is_unlimited_credit` + `scopeActive()` query that `getTotalUserCredit()`
already ran, and `getTotalUserCredit()` now calls it too. Use it for any new "is this account unlimited?"
decision — four hand-rolled copies of a "does this grant count?" test is precisely the arrangement that
let `scopeActive()` and `hasExpired()` drift apart ([CREDITS_CONTEXT](CREDITS_CONTEXT.md#-traps)). The
string comparison is still the rule for anything reading `getAvailableCredits()` itself
([CREDITS_CONTEXT](CREDITS_CONTEXT.md#unlimited-is-a-string)).

**Both `confirm` copies log at `warning`, not `info`, and carry the `payment_intent_id`.** Reaching a
confirm handler means the intent may already have succeeded at Stripe, so a refusal there can leave a
charge with no credits granted against it — the narrow race where an admin grants unlimited mid-checkout.
Refusing is still right (the credits would be meaningless), but the charge has to be findable for a
refund, hence the intent id in the log line.

**The `api/payment/confirm` guard sits ahead of the discount re-validation**, so a refused purchase does
not also write `billing.checkout_discount_*` audit rows for a checkout that never happened.

⚠️ **`StripePaymentController::confirmACHPayment()` is deliberately *not* guarded.** It writes a grant,
but it is **not registered in `routes/api.php`** — unreachable dead code. If it is ever routed, it needs
the same copy or it reopens the hole.

✅ The webhook is not a hole: `StripeProvider::handleWebhook()` only logs the event, it never grants.

☠️ **The sibling gate was left open.** `CreditPage`/`Checkout` block purchasing while **impersonating**
as well as for unlimited grants, and that half is still client-side only — no payment handler, route or
middleware checks impersonation, so an impersonating admin can still drive a real purchase on the
impersonated account. Same shape as the bug fixed above, different flag; out of scope for `ws-480`.

✅ Now tested: `tests/Feature/Billing/UnlimitedCreditPurchaseRefusedTest.php` covers all four routed
handlers, asserts via a mocked `PaymentProviderInterface` that the provider is **never reached** (the
refusal must land before any Stripe work), and pins two negative cases — an ordinary account with a
balance is still allowed to buy, and an *expired* unlimited grant does not refuse. The SPA still has no
`CreditPage` or `Checkout` test.

⚠️ **`ws-451` adds a fifth handler that writes a purchase grant**, `PaymentController::completeFreeOrder()`
(trap 10). It carries its own `Credits::hasUnlimited()` refusal, returned as a 422, but it is **not** in
`UnlimitedCreditPurchaseRefusedTest`. Its own test file covers that case.

### 10. A 100% discount code cannot go through Stripe (`ws-451`, unmerged)

> ⚠️ **On `ws-451` only**: backend `584eb335`, frontend `d8b2435`, both pushed to `origin/ws-451`,
> **not on `develop`** as of 2026-09-23. The generated indexes do not list the new route yet. Do not
> regenerate from the branch.

**The bug.** `initialize` and `confirm` both validate `amount => min:1`, and Stripe will not create a
$0 intent. When a code covered the whole order, *Total Due* read `$0.00`, the SPA still offered
`Pay $0.00`, and the click failed with *"The amount field must be at least 1."* No credits were granted.

**The fix is a separate path, not a $0 intent:**

```
POST api/payment/complete-free-order  {credits, discount_code}   (auth:sanctum)
        └─ PaymentController::completeFreeOrder()
             ├─ Credits::hasUnlimited()  → 422
             ├─ price from price_details (tier where from <= credits <= to) → 422 if no tier
             ├─ DB::transaction + lockForUpdate() on the discount_codes row
             │    ├─ DiscountCodeService::validate($user, $code, $serverSubtotal, $credits)
             │    ├─ final_amount > 0  → 422 "This order still requires payment."
             │    ├─ Credits::addCreditsToUser(… amount 0, SOURCE_PURCHASE)
             │    └─ Transaction::saveUserTransaction(… id 'free_<uuid>', amount 0, status 'succeeded')
             ├─ audit: billing.checkout_discount_applied + billing.payment_succeeded
             └─ any exception → rolled back, 500, audit billing.payment_failed (ws-451 PR review)
```

☠️ **The subtotal is priced on the server, and that is the point of the design.** `confirmPayment()`
re-validates the discount against the **client's** `original_amount`. That is survivable there because
Stripe really charges the card. On a path that charges nothing, a client could claim a $10 subtotal for
750 credits and redeem a `$10 off` code for free credits. `completeFreeOrder()` takes only `credits` and
`discount_code`, and it reads the price from `price_details`, the same tiers `CreditPage` shows. Do not
add an `amount` or `original_amount` parameter to it.

- **The record has to look like a paid order.** `status = 'succeeded'` and
  `transaction_details.discount_code_id` are what `DiscountCode::countUses()` counts, so a free order
  uses up `max_uses` / `max_uses_per_user` exactly as a paid one does. `payment_method_type` is
  `'discount_code'`. `CreditPage`'s `formatPaymentMethod()` has no label for it and its fallback renders
  it as *Discount Code*. `stripe_transaction_id` is `free_<uuid>` because the column is unique.
- **The code row is locked** (`lockForUpdate()` inside the transaction, before `validate()`), so two
  concurrent free orders cannot both pass the last remaining use. This covers the free path only.
  [DISCOUNT_CONTEXT trap 3](DISCOUNT_CONTEXT.md#-traps) still applies to the Stripe path.
- A credit count that falls outside every tier is refused (422). `CreditPage` falls back to the first
  tier's price in that case. The server does not.

**SPA (`components/PaymentForm/PaymentForm.js`):**
- `isFreeOrder = Boolean(appliedDiscount) && Number(amount) <= 0`. The button reads **Complete Order**
  and calls `completeFreeOrder` (`slices/payment/paymentSlice.js`) instead of `createPaymentIntent`.
  The saved-card and `PaymentElement` sections are hidden. On success the form dispatches
  `fetchUserCredits()` and navigates to `/user-panel/credit` with `{paymentSuccess: true, credits}`,
  which is the same landing state `PaymentStatus` uses. `PaymentStatus` is not involved.
- `isBelowMinimum` (`0 < amount < MIN_CHARGE_AMOUNT`, which is `1` and mirrors the backend rule)
  **disables** the button and shows *"The minimum card payment is $1.00."* A code that leaves only cents
  due has no path at all: Stripe cannot take it and the free path refuses it.
- The `isBillingComplete` gate (trap 8) **still applies** to a free order, even though no billing field
  is sent anywhere on that path.

- The `ws-451` PR review added the `billing.payment_failed` row on an unexpected failure, which matches
  what `StripeProvider::confirmPayment()` writes. Before that, a failed free order appeared only in
  `laravel.log`. The review found no gap in pricing, locking or grant parity with the paid path.
  Refunds are not a risk for `free_` rows, because the refund routes are commented out.

✅ Tested: `tests/Feature/Credits/FreeOrderCheckoutTest.php`, 10 tests ([TESTING.md](../TESTING.md)).

---

## Credit history

`GET api/user/credit-history` → `PaymentController::getCreditHistory()` merges three sources into one
chronological list: admin-assigned grants, Stripe purchases (with the transaction id), and revocations.
It is the only place those three views are reconciled — reuse it rather than re-deriving.

### ☠️ `type` is a closed vocabulary the SPA must mirror

Each entry carries a `type`, and the consumer renders it through a lookup map with a **raw-string
fallback** (`TYPE_LABEL[entry.type] ?? entry.type` — `CreditPage.js`). A value the map doesn't know is
therefore shown to the *customer* verbatim, as a badge reading literally `admin_revoked`. There is no
error and nothing fails; it just looks broken.

| `type` | Emitted for | Label |
|---|---|---|
| `purchase` | Stripe purchase | Purchase |
| `admin_assigned` | admin grant | Admin Assigned |
| `revoked` | refund (`SOURCE_REVOKED`) | Revoked |
| `admin_revoked` | **`ws-402`** — admin claw-back counter-entry | Admin Removed |
| `adjustment` | **`ws-402`** — ledger-balancing entry | Balance Adjustment |

**Adding a `type` is a two-repo change**, and a third edit besides: `TYPE_LABEL` in `CreditPage.js`
*and* a `&--type-<value>` rule in `CreditPage.scss`, or the badge renders unstyled. `ws-402` added the
last two rows and shipped the backend half first — the labels and styles were added later, in review.

⚠️ **`CreditHistory.js` is not the consumer.** It is dead mock UI; the live page is
`pages/UserPannel/CreditPage/CreditPage.js`. Editing the wrong one is a silent no-op.

Note the two new types carry `amount: '0.00'` (rendered as `—`) because a counter-entry and an
adjustment move credits, not money.

_[not deeply traced]: the ACH / bank-transfer branches of `StripeService`, and `TransactionDetail`'s
exact column semantics._
