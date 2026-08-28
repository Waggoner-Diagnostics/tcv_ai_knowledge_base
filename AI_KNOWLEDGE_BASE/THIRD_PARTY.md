# Third-Party Integrations

Five external dependencies plus per-organisation LMS providers. Credentials all live in
`config/services.php` (except S3, which is in `config/filesystems.php`).

| Service | Purpose | Code | Env |
|---|---|---|---|
| **Stripe** | Payments, customers, payment methods, refunds | `StripeService`, `StripeProvider`, `StripePaymentController` | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` |
| **AWS S3** | Test plate images (private, pre-signed URLs) | `SecureImageService`, `UploadTestPlates` command | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` |
| **HubSpot** | Contact / enquiry form | `HubSpotService::submitEnquiry()` ← `ContactController` | `HUBSPOT_ACCESS_TOKEN` |
| **Cloudflare Turnstile** | Bot defence on the public org patient forms | `TurnstileService::verify()` | `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` |
| **LMS providers** | Completion reporting, per organisation | `app/Services/Lms/Providers/*` | stored per-org in `lms_provider_configs.config` |

SDKs: `stripe/stripe-php ^17.4`, `aws/aws-sdk-php ^3.376` + `league/flysystem-aws-s3-v3`.
`nnjeim/world` seeds `countries` / `states`. `barryvdh/laravel-dompdf` renders result PDFs.
`maatwebsite/excel` renders report exports.

---

## Stripe

Called through **two** paths ([CONTEXT/BILLING_CONTEXT.md](CONTEXT/BILLING_CONTEXT.md)):
`StripeService` (direct SDK, used by the deprecated `api/stripe/*` surface and by login's
`createOrGetCustomer`) and `StripeProvider` (the `PaymentProviderInterface` implementation behind
`api/payment/*`). **Grep both** when tracing a Stripe call.

☠️ The webhook cannot work today — the route sits inside `auth:sanctum`, and the signature is verified
against a re-encoded body rather than the raw request. Fulfilment happens synchronously in
`POST api/payment/confirm` instead.

☠️ `createOrGetCustomer()` runs on **every successful login**, with failures swallowed into a log line.
A Stripe outage manifests as slow logins, not as an error.

## AWS S3

Test plates are stored private and served only as pre-signed URLs valid **900 s**, cached server-side
for 880 s. `Storage::disk('s3')->temporaryUrl(...)` with `ResponseContentDisposition: inline` and
`ResponseContentType: image/png`.

☠️ `revokeAccess()` clears the cache, not the URL ([S-11](SECURITY.md#s-11--revokeaccess-does-not-revoke-s3-access)).
☠️ `FILESYSTEM_DISK` defaults to `local`; only `SecureImageService` names `s3` explicitly. Anything that
relies on the default disk writes to the container filesystem, which is not persisted.

## HubSpot

One method, `submitEnquiry()`, called from `ContactController::submit()`
(`POST api/contact`, `auth:sanctum`, `throttle:10,1` — the only throttled route in the app). A failure
returns 500 **with the exception message in the response body**; that is the only place a third-party
error reaches a client verbatim.

## Cloudflare Turnstile

`TurnstileService::verify($token, $ip)` posts to `challenges.cloudflare.com/turnstile/v0/siteverify`.
Called from `OrganizationPatientController` on both public patient-intake endpoints; the SPA supplies
the token via `@marsidev/react-turnstile` and `REACT_APP_TURNSTILE_SITE_KEY`.

☠️ **With no `TURNSTILE_SECRET_KEY` configured the service logs an error and returns
`success: false`** — i.e. it fails **closed**, blocking patient intake. Check this first when org
patient creation stops working in a new environment.

☠️ `App\Rules\TurnstileToken` exists as a validation rule but **is referenced nowhere**. Verification is
done imperatively in the controller instead. Do not assume the rule is protecting a form.

## LMS providers

Per-organisation, configured in `lms_provider_configs` and dispatched through `LmsProviderRegistry`:

| Key | Status | Delivery |
|---|---|---|
| `generic_webhook` | ✅ live | POST to `completion_url` |
| `cornerstone` | ✅ live | OAuth token → xAPI statements to an LRS |
| `healthstream` | ❌ constant + default config only, provider registration commented out | — |
| `scorm` | ❌ constant only | — |

☠️ Provider config — including Cornerstone's `client_secret` — is stored as **plain JSON**
([S-06](SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext)).
☠️ An org can be configured for `healthstream` and launch successfully, then fail at delivery, because
`verifySignature()` prefers it in its sort but no provider is registered
([CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md)).

---

## Failure modes at a glance

| Service down | Symptom |
|---|---|
| Stripe | slow logins; purchases fail at `initialize`/`confirm` |
| S3 | plates return `null` URLs → the test player shows blank plates, logged as *"Failed to generate secure plate URL"* |
| HubSpot | `POST api/contact` → 500 with the upstream message |
| Turnstile | org patient intake blocked (fails closed) |
| LMS endpoint | deliveries retry 30 s → 2 m → 10 m → 1 h → 6 h, then dead-letter — **if a queue worker exists** |

## Adding an integration

Put it behind a service class in `app/Services/`, read credentials from `config/services.php` (never
`env()` outside config — `config:cache` runs at boot and `env()` returns `null` afterwards), and log
failures rather than throwing into a controller that will turn them into an opaque 500.
