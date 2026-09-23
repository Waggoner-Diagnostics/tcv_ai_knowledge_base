# Context: LMS Integration (launch → session → delivery)

> Load this **instead of** reading `app/Services/Lms/`. ~2k tokens. This is the newest and most
> deliberately-built subsystem in the codebase — and the only one with real test coverage.

## Files
| File | Role |
|---|---|
| `app/Services/Lms/LmsLaunchService.php` | ⭐ Session creation, the **status state machine** |
| `app/Services/Lms/LmsDeliveryService.php` | ⭐ Enqueue completion / section progress, dead-letter replay |
| `app/Services/Lms/LmsProviderRegistry.php` | Name → provider instance |
| `app/Services/Lms/Providers/GenericWebhookProvider.php` · `CornerstoneProvider.php` | The two live providers |
| `app/Services/Lms/XapiStatementBuilder.php` | xAPI statements for Cornerstone's LRS |
| `app/Services/Lms/Contracts/*` | `LmsProviderInterface`, `LmsIdentity`, `LmsLaunchContext`, `DeliveryResult` |
| `app/Jobs/ProcessLmsDeliveryJob.php` | The delivery worker (manual retry/backoff) |
| `app/Listeners/HandleLmsNotificationOnCompletion.php` · `HandleLmsSectionProgressOnCompletion.php` | Event → enqueue |
| `app/Providers/LmsServiceProvider.php` | ⭐ Registry wiring **and** the only explicit `Event::listen` calls |
| `app/Http/Controllers/LmsAdminController.php` | Admin: configs, keys, dead letters, delivery status |
| `app/Http/Middleware/LmsSessionStatusMiddleware.php` | The `lms.status:` gate |

> 🚧 **`ws-460` (HealthStream AICC) — on branch `ws-460` in `TCV-Backend` and `TCV-Frontend`, NOT on
> `develop` as of 2026-09-23.** It adds the files below. Everything in this pack marked `ws-460`
> describes that branch; the generated indexes still describe `develop` and will not show any of it
> until it merges and the KB is regenerated.
>
> | File (`ws-460`) | Role |
> |---|---|
> | `app/Services/Lms/Providers/HealthStreamProvider.php` | AICC HACP v4: `GetParam` at launch, `PutParam` at completion |
> | `config/lms.php` | `delivery_dispatch` — `after_response` (default) or `queue` |
> | `app/Console/Commands/DeliverPendingLmsDeliveries.php` | `lms:deliver-pending` — the retry path when there is no worker |
> | `app/Console/Commands/ProvisionHealthStreamOrg.php` | `lms:provision-healthstream` — creates the org's provider config |
> | `app/Http/Middleware/EnsureSuperAdmin.php` | `super.admin` alias, now on `api/admin/lms/*` |

## Tables
`lms_provider_configs` · `lms_sessions` · `lms_delivery_queue` · `lms_delivery_tokens` ·
`organization_patient_sessions` (legacy predecessor)

## Tests
`tests/Feature/Lms/` — launch, admin, delivery, section progress, xAPI batching. **This is the only
subsystem with a real suite**; run it before and after any change here ([TESTING.md](../TESTING.md)).
`ws-460` adds `HealthStreamProviderTest.php` (HACP round-trips against a faked endpoint) and extends
`LmsAdminTest.php` with the super-admin gate. ⚠️ `phpunit.xml` sets `LMS_DELIVERY_DISPATCH=queue` on
that branch: with `QUEUE_CONNECTION=sync` that runs deliveries inline, whereas the real default
(`after_response`) fires on HTTP termination, which never happens inside a test body. So the suite does
**not** exercise the after-response path.

---

## The state machine

`LmsSession::status` advances **forward only**. `LmsLaunchService::advanceStatus()` compares an ordinal
map and silently ignores anything that isn't strictly greater:

| Status | Ordinal | Reached when |
|---|---|---|
| `launched` | 0 | signature verified, session created |
| `identity_resolved` | 1 | LMS identity mapped to a patient |
| `form_submitted` | 2 | patient form stored |
| `test_assigned` | 3 | `PatientTest` created |
| `test_completed` | 4 | `TestCompleted` fired |
| `reported` | 5 | delivery succeeded |
| `failed` | **5** | delivery dead-lettered |

`TERMINAL_STATUSES = [reported, failed]`.

☠️ **`reported` and `failed` share ordinal 5.** Once a session reaches either, it can never move to the
other — a dead-lettered session that is later replayed successfully stays `failed`. If you add a status,
insert it with a *new* ordinal and check nothing collides.

### The `lms.status:` gate
`routes/api.php` decorates several routes with e.g. `->middleware('lms.status:test_assigned')`.
`LmsSessionStatusMiddleware` reads `$request->attributes->get('lmsSession')` and returns **409
`SESSION_STATUS_MISMATCH`** when the status isn't in the allow-list.

☠️ **It enforces nothing when there is no `LmsSession`.** `FlexibleAuthMiddleware` only sets that
attribute for tier-3 (LMS) tokens, so on invitation, resume, legacy-org and Sanctum sessions the gate is
a **pass-through**. Read `lms.status:` as "if this is an LMS session, it must be in state X" — never as
a general precondition.

---

## Launch

```
Org's test_url  →  POST api/organization/verify-signature { org_id, signature, … }   ← PUBLIC
   ├─ pick the most capable active LmsProviderConfig  (cornerstone > healthstream > generic_webhook)
   ├─ HMAC-SHA256(org_id, signing_key)  — or APP_KEY as a permanent fallback   [S-05]
   └─ LmsLaunchService::createSession()
         raw   = bin2hex(random_bytes(32))     ← returned to the client, never stored
         stored= hash('sha256', raw)           ← lms_sessions.session_token
         status= launched,  token_expires_at = now + provider TTL (120 or 180 min)
```

The raw token then arrives on every subsequent call and is matched by tier 3 of
`FlexibleAuthMiddleware`. See [ORGANIZATION_CONTEXT.md](ORGANIZATION_CONTEXT.md) for the URL itself.

☠️ **`launch_nonce` is generated and `nonce_consumed_at` is set to `now()` in the same
`create()` call** — the nonce is born consumed, so there is no replay protection today. Do not assume
it exists.

---

## Delivery

`TestCompleted` / `TestSectionCompleted` → listener → `LmsDeliveryService::enqueue*()` → a
`lms_delivery_queue` row → `ProcessLmsDeliveryJob::dispatch()`.

### Queue-row lifecycle
`pending` → `in_flight` → `delivered` | `failed` → `dead_letter`

`ProcessLmsDeliveryJob` sets `$tries = 1` and does **all** retry logic by hand: it increments
`attempt_count`, then re-dispatches itself with a `delay()` (not `release()`) on the backoff schedule
**30 s → 2 m → 10 m → 1 h → 6 h**. After the last step the row becomes `dead_letter`, which the job then
refuses to touch until an admin replays it.

### Dispatch mode (`ws-460`, branch only)
On `develop` every enqueue goes to the `lms` queue, so without the `workers` profile **nothing is ever
delivered** ([QUEUES.md](../QUEUES.md)). `ws-460` routes all three enqueue paths (completion, section
progress, dead-letter replay) through `LmsDeliveryService::dispatchDelivery()`, which forks on
`config('lms.delivery_dispatch')` / `LMS_DELIVERY_DISPATCH`:

| Mode | Default? | First attempt | Retries |
|---|---|---|---|
| `after_response` | ✅ | runs in the web process after the response is sent (same trick as invitations, `ws-404`) | **not re-dispatched** — the row stays `pending` with `next_retry_at`; `lms:deliver-pending` performs the retry |
| `queue` | | `lms` queue, `afterCommit()` | delayed re-dispatch, as before |

☠️ **In `after_response` mode the backoff ladder only runs if the scheduler runs.** `lms:deliver-pending`
is scheduled every five minutes in `bootstrap/app.php`, but the scheduler is `backend-scheduler`, which
is behind the `workers` profile like the queue worker. An environment with neither gets the *first*
attempt and **no retries**: one transient HealthStream failure leaves the learner passed in TCV and
missing from their transcript, with the row sitting at `pending` forever. Run
`php artisan lms:deliver-pending` by hand (`--dry-run` lists what is due) until the profile is on.

⚠️ `LMS_DELIVERY_DISPATCH` only reaches the container because `ws-460` also adds it to the backend
`environment:` block of **both** compose files. Setting it in a deploy `.env` on `develop` does nothing.

⚠️ After-response delivery holds a php-fpm child for the outbound call, which is why the HealthStream
defaults cap `delivery_timeout_seconds` at 30. `lms:deliver-pending` is safe beside a real worker: the
job row-locks and short-circuits `delivered` / `dead_letter`.

**Idempotency** is enforced at enqueue time, not delivery time: `enqueueCompletion()` refuses a second
row for the same session + event, and `enqueueSectionProgress()` derives a deterministic key from
session + section so the same section can never enqueue twice.

### Admin surface (all `auth:sanctum`, all under `api/admin/lms`)
`GET/POST provider-configs` · `GET provider-configs/{id}/signing-key` · `POST provider-configs/{id}/rotate-key` ·
`GET dead-letters` · `POST dead-letters/{id}/replay` · `POST dead-letters/{id}/dismiss` · `GET delivery-status`

☠️ **None of these carry a policy check** — `auth:sanctum` alone. Any authenticated user of any
`usertype` can read an org's signing key ([S-06](../SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext)).

🚧 **`ws-460` gates the whole group with `->middleware('super.admin')`** (`EnsureSuperAdmin`, 403
`api.forbidden`; runs behind `auth:sanctum`, so a guest still gets 401). Branch only — **still open on
`develop`**. ☠️ And the gate is only as strong as `usertype`: while
[S-22](../SECURITY.md#s-22--any-signed-in-account-can-promote-itself-to-super-admin-through-put-apiusersid) stands, any signed-in account can make itself a super admin and walk straight
through it.

---

## Providers

Registered in `LmsServiceProvider::register()` on a singleton `LmsProviderRegistry`:

| Key | Class | Delivery |
|---|---|---|
| `generic_webhook` | `GenericWebhookProvider` | POST to `completion_url`, `auth_type` from config |
| `cornerstone` | `CornerstoneProvider` | OAuth token → xAPI statements to an LRS via `XapiStatementBuilder` |
| `healthstream` | `HealthStreamProvider` (🚧 `ws-460` only) | On `develop`: **not implemented** — constant + default config, registration commented out as "Phase 3". On `ws-460`: registered; AICC HACP v4 form-POST to the per-org `hacp_url` (see below). |
| `scorm` | — | Constant only. No provider. |

Adding a provider is a three-step change: implement `LmsProviderInterface`, register it in
`LmsServiceProvider`, add its defaults to `LmsLaunchService::buildDefaultConfig()`. Nothing else.

☠️ **`verifySignature()` can select a config whose provider is not registered.** Its sort prefers
`cornerstone > healthstream > generic_webhook`, and `healthstream` has no registered provider — so an org
configured for HealthStream launches fine and then fails at delivery. *(Closed by `ws-460` once it merges;
still true of `develop`.)*

### HealthStream / AICC (`ws-460`, branch only)

HACP is form-encoded request, INI-style response (`error_num=` / `error_text=` / `[CORE]` …), **no auth**:
the launch's `AICC_SID` is the only session key.

- **Launch.** `verifySignature()` passes `$request->all()` to `validateLaunch()`, which reads the SID from
  `AICC_SID` / `aicc_sid` / `session_id` and calls **`GetParam`** for `student_id` + `student_name` (or the
  legacy `user_full_name`). Timeouts 8 s / 5 s connect. **It never throws** — a HealthStream outage starts
  the test without an identity. No SID at all is also allowed (an admin opening the raw URL) but gets a
  `no-aicc-sid:<uuid>` placeholder, and its completion **dead-letters immediately** (`PAYLOAD_BUILD_ERROR`)
  instead of burning the retry ladder.
- **Completion.** **`PutParam`** with a CRLF `[CORE]` block: `lesson_location`, `score` (plate %, from
  `result_json.summary` summed across paired eyes — **not** `result_json['score']`, which doesn't exist;
  omitted when there are no plates), `time` (real `HH:MM:SS`; legacy hardcoded `00:00:00`) and
  `lesson_status`. `lesson_status` is **`completed`** by default — HealthStream applies its own mastery
  cutoff — unless the org sets `report_pass_fail`.
- **Success is `error_num=0` in the body**, not HTTP 200. Anything else fails as `HACP_<n>` /
  `HACP_NO_STATUS` and enters the retry ladder. `provider_ref_id` = the SID, truncated to 255 so a column
  overflow can't fail the job *after* HealthStream accepted the score (→ double report on retry).
- ⭐ **The endpoint is always the org's configured `hacp_url`, never the launch's `AICC_URL`.** Trusting
  the query string would let anyone point TCV's score POSTs at their own server. Don't "fix" this to
  match legacy.
- **Name prefill.** The `GetParam` name lands in `lms_context['external_user_name']`;
  `getPatientForm()` returns it as `prefill: {first_name, last_name}` (split on the **first** whitespace
  only, so "van der Berg" survives; `[]` for every non-LMS tier). The SPA uses it only where the org's
  own verification data left the field empty. A convenience, never an identity claim.
- **Frontend (`ws-460`).** `OrganizationPatient.js` now reads `AICC_SID` / `AICC_URL` **case-
  insensitively** — HealthStream sends upper, the AICC spec writes lower. Reading one spelling loses the
  SID silently: the learner passes and the result dead-letters. `VerifiedDefaultUser.js` merges
  `response.data.prefill` into the form.

**Provisioning** — `php artisan lms:provision-healthstream [--org=] [--hacp-url=] [--ttl=180]
[--rotate-key] [--show-key] [--dry-run]`. Auto-detects the org from `organization_configs.is_healthstream`
when exactly one has it. Refuses if the org has no **active** `organization_configs` row (the launch would
404) or no `allowed_tests`. Upserts the `lms_provider_configs` row with **encrypted** config, keeps the
existing `signing_key` unless `--rotate-key`, and **rewrites `organizations.test_url`** — that URL is the
AU URL HealthStream is given. ☠️ `--rotate-key` breaks every launch URL HealthStream already holds.

---

## ☠️ Traps

1. **`lms.status:` is a no-op without an LMS session** (above). The single most misread thing here.
2. **`reported` and `failed` are the same ordinal** — a replayed dead letter never becomes `reported`.
3. **The nonce is consumed at creation** — no replay protection.
4. **Provider config is stored as plain JSON**, including Cornerstone's `client_secret`; `signing_key` is
   a plain column. [S-06](../SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext).
5. **The launch signature is static and permanent**, with an unconditional `APP_KEY` fallback.
   [S-05](../SECURITY.md#s-05--organisation-launch-signatures-are-static-permanent-bearer-credentials).
6. **Nothing runs the queue by default.** `QUEUE_CONNECTION=database`; the worker exists in both compose
   files but only under `COMPOSE_PROFILES=workers` (`ws-404`). On `develop` enqueued deliveries sit in
   `jobs` until it runs. [QUEUES.md](../QUEUES.md). `ws-460` makes the first attempt worker-free
   (`after_response`) — but **retries still need the scheduler** (see *Dispatch mode*).
7. **Tier 4 (`organization_patient_sessions`) is the pre-LMS path and is explicitly marked for removal**
   "after Phase 3 cutover". New work belongs on `lms_sessions`; do not extend tier 4.
