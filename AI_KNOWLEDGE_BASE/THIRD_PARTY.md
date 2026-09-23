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

## AWS SES (`ws-404`, on `develop` since 2026-09-15)

`config/services.php` has always carried SES credentials — they read the **same `AWS_*` variables the S3
disk uses** — but no mailer selected them. `ws-404` added a **`ses-v2`** mailer, making SES reachable with
`MAIL_MAILER=ses-v2` and no other change. `MAIL_MAILER` is in the compose allowlist, so this is a DevOps
env value rather than a code change.

Why it matters more than a transport swap: SES v2 goes over **HTTPS**, which deletes the entire failure
class the invitation job works around — no socket to refuse on `:587`, no per-connection message ceiling
(`messages_per_connection`), no shared-mailbox connection limit, nothing for a firewall or fail2ban to
rate-limit. The connection-failure/deferral machinery in
[CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md) becomes a fallback path rather than the
common one. v2 rather than legacy v1 because SES exposes its rate and reputation controls only on v2.

☠️ **But SES failures do not look like SMTP failures, and the job had to learn that.** `ses-v2` wraps
*every* `AwsException` — a refused TCP connection and a rejected recipient alike — in one
`TransportException('Request to AWS SES V2 API failed. Reason: …')`, so a classifier written against
Symfony's SMTP strings sorted a whole SES outage into "undeliverable address": every row revoked and
refunded. `SendTestInvitationEmailsJob::isSesRequestThatNeverDelivered()` unwraps `getPrevious()` and
defers only what provably never delivered — pre-request cURL errors **6/7/35** (not 28/52, which can
land after SES accepted the call), HTTP **429/5xx**, and error codes for throttling, service
unavailability, paused sending, and expired/invalid/denied credentials. An AWS `CredentialsException` (no
resolvable credentials at all — raised while building the request, never wrapped) also defers.
`MessageRejected`, invalid addresses and suppression-list hits still fail. Full table in
[JOBS.md](JOBS.md#-connection-failure-is-not-address-rejection).

☠️ **The `failover` chain used to end in `log`, and that was not a fallback.** `log` accepts every
message and delivers none while reporting success, so a broken primary read as a clean send and no
patient received anything. `ws-404` changed the chain to `['ses-v2', 'smtp']`. The same trap still sits
in `config/mail.php`'s `'default' => env('MAIL_MAILER', 'log')` — an unset `MAIL_MAILER` silently
discards all mail, which is the first thing `php artisan mail:preflight` checks for ([JOBS.md](JOBS.md)).
⚠️ A docblock in `SendTestInvitationEmailsJob::isFailoverExhaustion()` says `ses-v2` ships as the
default; it does not — production gets `ses-v2` from its `MAIL_MAILER` value.

⚠️ **With `failover`, only `No transports found.` defers.** `All transports failed.` fails the row:
`RoundRobinTransport` collapses both legs' errors into that string (ses-v2 contributes nothing to
`getDebug()`), so it cannot be told from a recipient both hosts rejected.

**The QA mail host is not SES.** It is a cPanel/Exim box that caps the sending domain at **200
emails/hour** and reports the cap as a **550** (`Domain … has exceeded the max emails per hour (200/200
(100%)) allowed. Message discarded.`) — the code a dead mailbox returns. Since `8ee517aa` (2026-09-16) the
job recognises the sender-scoped wording and defers instead of writing the address off; before that one
500-address QA send wrote off 286 deliverable patients. Expect large QA sends to trickle out over hours,
not fail. Production uses `ses-v2` and has no such cap.

⚠️ **SES identity verification is account state, not config.** `mail:preflight` calls SES v2 to confirm
the `from` address is a verified identity and reports account-level sending state; a correct config with
an unverified sender still delivers nothing. [not deeply traced] — the preflight's SES account checks
were read at the signature level, not exercised against a live account.

## HubSpot

One method, `submitEnquiry()`, called from `ContactController::submit()`
(`POST api/contact`, `auth:sanctum`, `throttle:10,1` — the only throttled route in the app). A failure
returns 500 **with the exception message in the response body**; that is the only place a third-party
error reaches a client verbatim.

☠️ **Empty properties are stripped before the upsert, on purpose.** `submitEnquiry()` runs the property
array through `array_filter(… !== '' && !== null)` before searching HubSpot. The upsert path **PATCHes
the whole array onto an existing contact**, so an empty value would overwrite whatever the CRM already
holds — a returning enquirer who omits `company_name` would blank the company on their HubSpot record.
Omitting a property leaves it untouched on update and unset on create. Do not "simplify" that filter
away. `company_name` became optional in `ContactFormRequest` on 2026-08-26 (ws-361), which is what made
this reachable; the ticket subject also drops the `(company)` suffix when it is absent.

⚠️ **The two contact paths fail differently, on purpose** (ws-396). A failed **update** (PATCH on an
existing contact) is logged and swallowed — the contact already exists, so the ticket is still filed
against it and the enquirer gets through with a stale CRM record. A failed **create** still throws:
with no contact id there is nothing to associate a ticket with, so there is no degraded outcome to
fall back to. Before ws-396 the PATCH result was discarded entirely, with no log; do not restore that,
and do not "make the two consistent" by throwing on update — the contact form has **no local
persistence and no queue**, so a throw there loses the enquiry outright and every resubmission by that
enquirer fails identically.

### Enquiry source tagging (ws-396)

Tickets carry a custom property naming the product the enquiry came from — `HUBSPOT_TICKET_SOURCE_PROPERTY`
(internal name, portal-specific, default `tcv_source`) set to `HUBSPOT_TICKET_SOURCE_VALUE` (default
`TCV`). Neither var is in compose, so both defaults are what actually run; an absent var and an empty
one mean the same thing, which is why `config/services.php` uses `?:` rather than an `env()` default.
**This is not HubSpot's built-in ticket `source_type`**, which is a HubSpot-defined dropdown for
the channel a ticket arrived through; API-created tickets never populate it, so it reads `--` on ticket
lists no matter what this feature does. A list view has to add the custom property as its own column.

The property is custom, so a portal that never defined it rejects the create outright. The service
therefore treats a 400 naming that property as a configuration problem rather than bad data: it drops
the property, remembers the rejection for 24 h ([CACHE.md](CACHE.md)) and retries once, so the enquiry
still lands untagged. Detection parses the rejected property **name** out of the three shapes HubSpot
uses to report one — a structured `errors` array, a JSON array embedded in the `message` string, and a
bare quoted name — and compares it exactly. Do not reduce that to a substring search of the body: the
body echoes submitted values, so an enquiry mentioning the word would match, as would a neighbouring
property whose name merely contains it (HubSpot ships a "Source url" beside "Source").

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
| `healthstream` | ❌ on `develop` (constant + default config only). 🚧 **implemented on branch `ws-460`** | AICC HACP v4 `GetParam` / `PutParam` form-POST to the org's `hacp_url` ([LMS_CONTEXT](CONTEXT/LMS_CONTEXT.md#healthstream--aicc-ws-460-branch-only)) |
| `scorm` | ❌ constant only | — |

☠️ Provider config — including Cornerstone's `client_secret` — is stored as **plain JSON**
([S-06](SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext)).
☠️ An org can be configured for `healthstream` and launch successfully, then fail at delivery, because
`verifySignature()` prefers it in its sort but no provider is registered
([CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md)). Closed by `ws-460` once it merges.
☠️ HealthStream HACP has **no authentication** — the `AICC_SID` is the whole credential — and a
successful HTTP 200 can still carry `error_num != 0`. `ws-460` checks the body; the legacy integration
discarded it, so learners could show passed in TCV and incomplete in HealthStream.

## Country and state reference data (nnjeim/world)

`nnjeim/world` seeds the `countries` / `states` tables behind `DropdownValuesController::getCountriesWithStates()`
(F-081, `GET api/countries-with-states`) — the one source for every country/state dropdown in the
product: the website's distributor-enquiry form (`DistributorSignupClient.jsx`) and `AuthModal.jsx`
sign-up modal, plus `TCV-Frontend`'s Register, Checkout, `NewUserModal`, `OrganisationModal`,
Profile/Settings and Admin user tables (all via `loginSlice.js`'s `fetchCountries` thunk). **Neither
frontend sorts the list client-side** — both render whatever order the backend returns.

☠️ **The package seeds two countries under names ISO 3166-1 retired**: `iso2 = 'SZ'` as "Swaziland"
(renamed Eswatini, 2018) and `iso2 = 'MK'` as "Macedonia" (renamed North Macedonia, 2019).

✅ **`TCV-Backend fix/countries-list-update`** (`ddbeccf6`, PR #265, merge `330cf77d`) is **on `develop`
since 2026-09-18** — verified at the 2026-09-21 sync by finding `->orderBy('name')` in
`DropdownValuesController::getCountriesWithStates()` and the two renames in **both**
`database/seeders/WorldSeeder.php` and
`database/migrations/2026_09_18_000001_update_obsolete_country_names.php`. It fixes both:

- `getCountriesWithStates()` now calls `->orderBy('name')`. The list previously relied on **insertion
  order**, which only *looked* alphabetical because the package's original English names happened to be
  seeded that way — renaming "Swaziland"→"Eswatini" and "Macedonia"→"North Macedonia" in place, without
  an explicit sort, left them under **S** and **M** instead of **E** and **N**.
- `2026_09_18_000001_update_obsolete_country_names.php` backfills already-seeded databases, matched by
  `iso2` (not the old `name`, so it's immune to any spelling/casing drift in a future package version).
- ☠️ **The migration alone does not survive a reseed.** `WorldSeeder::run()` calls the package's
  `SeedAction`, which **truncates and reinserts all ~250 country rows** from its bundled data on every
  run. Laravel runs migrations before seeders and never re-runs a migration already recorded as applied
  — so on a fresh environment, or any `php artisan migrate:fresh --seed`, the migration's `UPDATE`s would
  no-op against the still-empty table, then the seeder would silently restore "Swaziland"/"Macedonia"
  with no error and no way to detect it. Fixed by re-applying the same two-row correction **inside
  `WorldSeeder::run()`, immediately after `SeedAction::class`**, so it re-lands every time seeding runs
  regardless of order. Verified by re-running `WorldSeeder` directly (a full reseed of all 250 rows) and
  confirming both `iso2` rows stayed corrected afterward.
- Production is not exposed to the reseed gap today — [DEPLOYMENT.md](DEPLOYMENT.md) shows deploys only
  run `php artisan migrate --force`, never seed — but any fresh local/staging database built the normal
  Laravel way (`migrate --seed` / `migrate:fresh --seed`) was.

Once merged: regenerate and expect `INDEXES/DATABASE_TABLE_INDEX.md`'s migration count to move by one;
no route, endpoint or table count changes otherwise.

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
