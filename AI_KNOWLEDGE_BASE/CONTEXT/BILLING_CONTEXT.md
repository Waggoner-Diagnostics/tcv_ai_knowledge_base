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
| Guard | `auth:sanctum` (✅ fixed `tcv-backend-codefix`, was public) | `auth:sanctum` |
| Endpoints | `create-payment-intent`, `confirm-payment`, `payment-methods`, `payment-methods/set-default`, `payment-methods/{id}` (DELETE) | `setup-intent`, `providers`, `initialize`, `confirm`, `webhook/{provider}` |

✅ **`api/stripe/*` is no longer public.** Every handler starts with `Auth::user()`, and unauthenticated
that is `null` — `StripeService`'s signatures are typed `User $user`, so the call threw a `TypeError`
that the controller's `catch` swallowed into a **500 containing the exception message**: exposed and
non-functional at the same time. `tcv-backend-codefix` (2026-09-04) moved all five routes into the
`auth:sanctum` group in `routes/api.php` (they were registered at the top of the file, alongside
genuinely public routes like `/login`), fixing it at the routing level rather than adding a null check
per method. Treat `api/stripe/*` as the deprecated surface regardless; build on `api/payment/*`.

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

---

## Credit history

`GET api/user/credit-history` → `PaymentController::getCreditHistory()` merges three sources into one
chronological list: admin-assigned grants, Stripe purchases (with the transaction id), and revocations.
It is the only place those three views are reconciled — reuse it rather than re-deriving.

_[not deeply traced]: the ACH / bank-transfer branches of `StripeService`, and `TransactionDetail`'s
exact column semantics._
