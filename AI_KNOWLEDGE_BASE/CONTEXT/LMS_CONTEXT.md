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

## Tables
`lms_provider_configs` · `lms_sessions` · `lms_delivery_queue` · `lms_delivery_tokens` ·
`organization_patient_sessions` (legacy predecessor)

## Tests
`tests/Feature/Lms/` — launch, admin, delivery, section progress, xAPI batching. **This is the only
subsystem with a real suite**; run it before and after any change here ([TESTING.md](../TESTING.md)).

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
`attempt_count`, then `release()`s with the backoff schedule **30 s → 2 m → 10 m → 1 h → 6 h**. After the
last step the row becomes `dead_letter`, which the job then refuses to touch until an admin replays it.

**Idempotency** is enforced at enqueue time, not delivery time: `enqueueCompletion()` refuses a second
row for the same session + event, and `enqueueSectionProgress()` derives a deterministic key from
session + section so the same section can never enqueue twice.

### Admin surface (all `auth:sanctum`, all under `api/admin/lms`)
`GET/POST provider-configs` · `GET provider-configs/{id}/signing-key` · `POST provider-configs/{id}/rotate-key` ·
`GET dead-letters` · `POST dead-letters/{id}/replay` · `POST dead-letters/{id}/dismiss` · `GET delivery-status`

☠️ **None of these carry a policy check** — `auth:sanctum` alone. Any authenticated user of any
`usertype` can read an org's signing key ([S-06](../SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext)).

---

## Providers

Registered in `LmsServiceProvider::register()` on a singleton `LmsProviderRegistry`:

| Key | Class | Delivery |
|---|---|---|
| `generic_webhook` | `GenericWebhookProvider` | POST to `completion_url`, `auth_type` from config |
| `cornerstone` | `CornerstoneProvider` | OAuth token → xAPI statements to an LRS via `XapiStatementBuilder` |
| `healthstream` | — | **Not implemented.** `TYPE_HEALTHSTREAM` exists as a constant with a default config, and `LmsServiceProvider` has the registration commented out as "Phase 3". |
| `scorm` | — | Constant only. No provider. |

Adding a provider is a three-step change: implement `LmsProviderInterface`, register it in
`LmsServiceProvider`, add its defaults to `LmsLaunchService::buildDefaultConfig()`. Nothing else.

☠️ **`verifySignature()` can select a config whose provider is not registered.** Its sort prefers
`cornerstone > healthstream > generic_webhook`, and `healthstream` has no registered provider — so an org
configured for HealthStream launches fine and then fails at delivery.

---

## ☠️ Traps

1. **`lms.status:` is a no-op without an LMS session** (above). The single most misread thing here.
2. **`reported` and `failed` are the same ordinal** — a replayed dead letter never becomes `reported`.
3. **The nonce is consumed at creation** — no replay protection.
4. **Provider config is stored as plain JSON**, including Cornerstone's `client_secret`; `signing_key` is
   a plain column. [S-06](../SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext).
5. **The launch signature is static and permanent**, with an unconditional `APP_KEY` fallback.
   [S-05](../SECURITY.md#s-05--organisation-launch-signatures-are-static-permanent-bearer-credentials).
6. **Nothing runs the queue.** `QUEUE_CONNECTION=database` and **neither compose file defines a worker**.
   Enqueued deliveries sit in `jobs` until some worker exists. [QUEUES.md](../QUEUES.md).
7. **Tier 4 (`organization_patient_sessions`) is the pre-LMS path and is explicitly marked for removal**
   "after Phase 3 cutover". New work belongs on `lms_sessions`; do not extend tier 4.
