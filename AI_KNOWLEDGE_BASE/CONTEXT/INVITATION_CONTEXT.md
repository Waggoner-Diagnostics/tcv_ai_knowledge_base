# Context: Email Invitations & Resume Links

> Load this **instead of** reading the invitation subsystem. ~2.9k tokens. This is how a patient with no
> account takes a test, and how the copy of the email that invites them is edited and stored.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/TestInvitationController.php` | ⭐ Send, verify, resend, cancel, list unregistered |
| `app/Http/Controllers/TestResumeController.php` (190 lines) | ⭐ Resume-link issue + redemption |
| `app/Models/TestInvitation.php` · `TestSession.php` · `TestResumeToken.php` | The three token records |
| `app/Models/TestEmailTemplates.php` · `UserEmailTemplate.php` | Per-user email copy |
| `app/Services/EmailTemplateService.php` | Picks the sender's template, or the admin default, or a hard-coded fallback |
| `app/Services/TestInvitationMailer.php` | ⭐ Renders + sends one invitation email — owns all three assembly passes (`ws-404`, extracted from the controller) |
| `app/Jobs/SendTestInvitationEmailsJob.php` | ⭐ Sends one batch of 25 after the response or on the queue; decides defer vs fail (`ws-404`) |
| `app/Jobs/SweepPendingInvitationsJob.php` | ⭐ Re-sends rows stranded at `pending`, using web traffic as the clock (`ws-404`) |
| `app/Support/EmailTemplatePlaceholders.php` | ⭐ The one placeholder vocabulary; both save paths validate against it (`ws-404`) |
| `app/Console/Commands/SendPendingInvitations.php` | Recovers invitations stranded at `email_status='pending'`; refunds ones that expired undelivered. **Scheduled** every 10 min (`ws-404`) |
| `app/Console/Commands/MailPreflight.php` | `mail:preflight` — is invitation email going to work in this environment? (`ws-404`) |

> **All `ws-404` work in this file is on `TCV-Backend@develop`** — merged 2026-09-15 (PR #240) with a
> follow-up on 2026-09-16 (PR #251). ☠️ The scheduled recovery exists in code but runs only where the
> compose `workers` profile is on, which it is not by default ([../DEPLOYMENT.md](../DEPLOYMENT.md)).
| `app/Console/Commands/CheckEmailTemplatePlaceholders.php` | Scans stored templates for placeholders that will not render (`ws-404`) |
| `app/Support/EmailContent.php` | ⭐ Makes bare URLs and `{{placeholder}}`s clickable (`ws-373`) |
| `components/richTextEditor/emailPlaceholders.js` *(TCV-Frontend)* | ⭐ Stored HTML ⇄ editor HTML; renders system values as read-only chips (`ws-400`) |
| `components/richTextEditor/RichTextEditor.js` *(TCV-Frontend)* | The shared Quill wrapper; `lockPlaceholders` turns the chip behaviour on (`ws-400`) |

## Tables
`test_invitations` · `test_sessions` · `test_resume_tokens` · `test_email_templates` · `user_email_templates`

---

## Three different tokens — do not confuse them

| Token | Table | Length | Lifetime | Redeemed by |
|---|---|---|---|---|
| **Invitation token** + 6-char code | `test_invitations.token` / `.verification_code` | `Str::random(32)` / `Str::upper(Str::random(6))` | **7 days** (`INVITATION_VALIDITY_DAYS`) | `POST api/test-invitation/verify-code` |
| **Session token** | `test_sessions.session_token` | `Str::random(32)` | **2 hours** | presented as Bearer → tier 2 of `FlexibleAuthMiddleware` |
| **Resume token** | `test_resume_tokens.token` | `Str::random(64)` | **7 days** | `POST api/test/resume` (public) |

All three are stored **in plaintext**. Only the LMS session token is hashed
([AUTH_CONTEXT](AUTH_CONTEXT.md)).

---

## Send flow

```
POST api/test-invitations/send   ← auth:sanctum + throttle:bulk-invitations (5/min).
                                   { test_id, emails[≤500], unique_test_id? }
  ├─ owner = $request->user()                   ← NOT from the body
  ├─ Credits::getAvailableCredits(owner->id)    → 402 when short  (guarded for 'Unlimited')
  ├─ DB::transaction:                                              ← ws-404
  │    ├─ bulk insert rows: token, code, expires_at = now+7d, email_status='pending'
  │    ├─ CreditConsume::consume(user, n, 'test_invitation', [ids])
  │    └─ PatientTest::increment('resend_count')  when unique_test_id given
  ├─ for each chunk of 25: dispatchEmailBatch()   ← each wrapped in try/catch(\Throwable): log + continue
  │      after_response (default): ->afterResponse(), shared $deadline (invitation_send_budget 240s)
  │      queue:                    to backend-queue, per-batch budget (invitation_queue_batch_budget 60s)
  ├─ SweepPendingInvitationsJob::dispatch()->afterResponse()   ← throttled; afterResponse even in queue mode
  ├─ audit: test.invitation_sent (Single|Multiple) | test.invitation_resent   ← only when created > 0
  └─ 202 Accepted  ← returns here, in well under a second; the batches run after it
```

✅ **The "via CSV (bulk)" audit key is gone** (merged 2026-09-15, AUDIT_TRAIL_BACKEND_CONTEXT §14 item 2).
`test.invitation_sent_bulk` was chosen purely on `$createdCount > 1`, so typing three addresses by hand
emitted a "sent via CSV" event. Now there are two keys: `test.invitation_resent` whenever
`unique_test_id` is present, else `test.invitation_sent` with a dynamic `(Single)`/`(Multiple)` title.
Rows written before the merge still carry the old key — do not read CSV usage out of them.

⚠️ **Dispatch order in `sendInvitations()` is *not* what it was documented to be.** `afterResponse()`
callbacks run once the response is sent whatever its status, so anything that throws after the first
dispatch returns a 500 while the mail still goes out — inviting a retry that sends the whole list twice
and charges twice. The audit block (an `expires_at` query, the title lookup, `auditService->log()`) runs
**after** the dispatch loop inside the same `try`, so that window is open. Since 2026-09-15 a failing
*dispatch* is caught per batch and cannot reach the outer catch; what follows the loop still can. See
[../QUEUES.md](../QUEUES.md#after-response-dispatch-ws-404). The single `$deadline` is computed once for
the **whole** send, not per chunk: the 20 batches a 500-address list produces all run back to back in the
same FPM child, so a per-chunk budget would multiply by 20.

**The response is now `202`, not `200`, and the payload changed** (`ws-404`). Delivery no longer happens
inside the request, so it cannot be reported per address:

| Removed | Added |
|---|---|
| `successful_invitations`, `successful_emails` | `queued_invitations`, `invitation_ids` |
| `failed_invitations`, `failed_emails` | `batch_size`, `batches` |

`skipped_emails` / `skipped_count` survive — those are addresses the *request* dropped for insufficient
credit, which it still knows. Per-address delivery outcome now lives on the row
(`email_status`, `email_sent_at`, `email_error`) and surfaces via
`GET api/test-invitations/unregistered`.

**`user_id` is no longer accepted in the body.** It was dropped from the validation rules when the route
moved into the `auth:sanctum` group on 2026-08-26 — the owner is always `$request->user()`, super-admin
included. That closed
[S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time).
It is throttled as of 2026-09-02 (`throttle:bulk-invitations`, 5/min — defined in
`AppServiceProvider::configureRateLimiting()`). `set_time_limit(0)` has moved out of the controller and into
`SendTestInvitationEmailsJob::handle()` (`ws-404`), where it covers the after-response send rather than
the request — the request itself is now a few hundred inserts and finishes well inside the normal
limit.

**Short balance truncates rather than rejects.** When `credit < count(emails)` the call queues only the
first `credit` addresses and returns 202 — it does not 402. Only a balance below 1 is a 402. The
remainder come back in `skipped_emails`.

**Credits are charged up front and refunded on failure** (`ws-404`). Delivery is asynchronous, so the
whole batch is billed at insert time; `SendTestInvitationEmailsJob::markFailed()` returns one credit per
address it cannot deliver, via `Credits::addCreditsToUser(..., SOURCE_REVOKED, creditedBy: null)`, and
only if it still holds the row's `sending` claim (a row settled elsewhere in the meantime gets no second
refund). `invitations:send-pending` also refunds rows that **expired while still `pending`**. It also sets
`is_revoked` — deliberately, because that closes both remediation endpoints for a row whose credit has
already been returned (a resend would be free, a cancel would refund a second time). Retry by sending
the address again from Send Test, which charges properly.

**Resend is a separate endpoint and does not re-charge.** The `is_resend` body flag on `send` is **gone**
(removed with the S-13 fix, so a resend can no longer be used to skip the credit check on `send`).
`POST api/test-invitations/{id}/resend` → `resendUnregisteredInvitation()` issues a *fresh* token + code
with a *fresh* 7-day window, consumes no credit, and is scoped to `user_id = auth()->id()`.

### How the invitation body is assembled

`TestInvitationMailer::send()` is three passes over one string, and the order matters. (It was
`TestInvitationController::sendInvitationEmail()` until `ws-404` extracted it; the controller method
survives as a one-line delegate for the resend path. Both the batch job and the resend now go through
the same three passes — see the merge note below.)

```
EmailTemplateService::getTemplateForUser(userId, TYPE_TEST_LINK)
   user_email_templates row  →  test_email_templates (admin default)  →  hard-coded fallback
 ├─ 1. str_replace the {{test_name}} {{verification_link}} {{verification_code}} {{expires_at}} … vars
 ├─ 2. restyle: preg_replace_callback rewrites <a href="{the link}"> into the blue button
 └─ 3. EmailContent::linkify(): wrap any URL still sitting in plain text   ← ws-373
```

Pass 2 only reaches a link the template **already anchored**; a template whose `{{verification_link}}`
was saved as plain text needs pass 3. Pass 3 skips anything pass 2 already wrapped, so the two do not
fight.

☠️ **Pass 2 used to be able to mail an empty email.** `preg_replace_callback` returns `null` when PCRE
hits its backtrack limit — plausible on a long body, because the pattern is `(.*?)` with `/s` — and that
`null` was assigned straight back to `$content`. `ws-373` keeps the unstyled content and logs
`Invitation link restyle failed, sending unstyled content` with `preg_last_error_msg()` instead. If you
copy this restyle shape anywhere else, keep the null check.

**`EmailTemplateService`'s hard-coded fallback is a real send path**, not dead code — it is used when the
admin default row is missing. `ws-373` changed it from a bare `<p>{{verification_link}}</p>` to a proper
button plus a copy-and-paste line, so a missing admin row no longer produces an unclickable email.

**The default subject changed on `ws-400`** (2026-08-31 — merged into `ws-404` on 2026-09-01, on
`develop` since 2026-09-15): `Welcome to Testing Color Vision` → **`You have been invited to take a color vision test`**,
in all three places that can emit it — `AdminSettingsSeeder` (fresh DBs only), the `EmailTemplateService`
fallback, and `2026_08_29_000001_update_default_test_email_subject` for rows already deployed. The
migration is match-on-old-value and scoped to `type = 'test_link'`, so an admin who retitled the subject
keeps their wording; `down()` reverses on the new value only. Same shape as `ws-373`'s three
([AUTH_CONTEXT](AUTH_CONTEXT.md)) — copy it for any future copy change.

☠️ **`test_email_templates` holds *two* admin-default rows, so a type-scoped data migration only ever
fixes one of them.** `ws-400`'s migration was scoped to `type = 'test_link'`, which left the
`org_test_link` row on the old `Welcome to Testing Color Vision` wording for six weeks — the two
templates a Super Admin edits side by side under Settings > Default Test Email Templates had visibly
different subjects. `ws-456` (2026-09-11) realigns it with
`2026_09_11_000001_update_org_default_test_email_subject`, an exact copy of the `ws-400` migration with
the type swapped, plus the matching `AdminSettingsSeeder` row. **Both defaults now carry
`You have been invited to take a color vision test`.** When you change this copy again, decide
explicitly whether the change is per-type or for both, and write one migration per row you mean to move.

⚠️ **The `EmailTemplateService` fallback ignores `$type` for both subject and body.** It needed no edit
on `ws-456` only because it already returned the `test_link` subject for every caller — so if the two
defaults are ever given different wording again, an organization that falls through to the fallback (its
admin default row missing) silently sends the *generic* subject. The fallback is a real send path, not
dead code.

✅ **`ws-373` and `ws-400` edited the same `return` block — resolved on `develop`.** `ws-373` replaced the
fallback **body**; `ws-400` replaced the **subject** on the line above it. Both reached `develop` through
`ws-404` (2026-09-15) and the fallback now carries both: subject `You have been invited to take a color
vision test`, body built with `EmailContent::anchorPlaceholders(…, ['{{verification_link}}' => 'Start
Test'])` so it renders the same button the save paths and repair migration produce. If you touch this
block, keep both halves — nothing downstream fails loudly when one is lost.

### The template editor locks system values (`ws-400`)

Committed on branches `ws-400` (both repos, 2026-08-31 → 2026-09-01). Backend half on `develop` via
`ws-404` since 2026-09-15; `origin/ws-400` on the frontend is merged too (a local frontend `ws-400` holds
one unpushed commit).

`{{…}}` tokens and the Start Test button stopped being free text in the SPA's three template forms —
`Setting/TestEmailTemplates.js` (admin default **and** org) and
`UserPannel/SettingPage/EmailConfigurationPage.js` (per sender). `RichTextEditor` gained a
`lockPlaceholders` flag; `components/richTextEditor/emailPlaceholders.js` does the work:

```
stored HTML  --toEditorHtml-->  Quill embeds (chips)  --toTemplateHtml-->  stored HTML
```

- Each system value is an atomic Quill `Embed` (`contenteditable=false`), so it can be **moved or deleted
  but never edited into a broken half-token**. Both blots register on `ReactQuill.Quill` — importing
  `quill` directly yields a *different* bundled instance (1.3.7) and the blots would never load.
- They are formats, so `PLACEHOLDER_FORMATS` has to be appended to the editor's `formats` whitelist or
  Quill drops them without a word (trap 8).
- **Validation checks for an anchor, not a substring.** `hasTestLinkButton()` replaced
  `body.includes('{{verification_link}}')` on all three forms. A bare token is inert on send — pass 2 only
  rewrites links the template already anchored — so the old check happily saved a template whose
  delivered email had no clickable button.
- **A bare `{{verification_link}}` is healed into a real anchor** on the way in *and* on the way out. That
  is the only route back after deleting the button: the toolbar has no control that inserts one, so the
  helper text tells the user to type the token where the button belongs.
- Both directions reach a **fixed point** — a second pass is a no-op — which is what stops the round trip
  rewriting the body every time the page opens.

Two silent no-ops in the shared editor were fixed in passing, and both predate this ticket:

- **`disabled` was accepted by the three pages and ignored by the component.** All three passed
  `disabled={saving…}`, but `RichTextEditor` never destructured it and never set `readOnly`, so the body
  stayed editable mid-save while every other control on the form greyed out. It is now wired to
  ReactQuill's `readOnly`.
- **`code-block` and `direction` were on the toolbar but missing from `defaultFormats`.** Quill's scroll
  whitelist rejects any format not on the list, so both buttons did nothing at all. Every control
  `defaultModules` renders needs a matching entry — check the pair together when you touch either.

☠️ **A `{{verification_link}}` already inside an `<a>` is left literal.** Wrapping it would nest `<a>` in
`<a>`, which every parser auto-closes, breaking the surrounding link. Such a template therefore fails
`hasTestLinkButton()` — correctly: it has no Start Test button.

☠️ **`data-inner` is the one thing that rides through Quill without meeting the formats whitelist**, so
`sanitizeButtonInner()` reduces it to bare inline tags (`B/STRONG/I/EM/U/SPAN/BR`, every attribute
stripped) before re-emitting it. Without that, HTML pasted into a button label — or already sitting in a
stored row — would reach the delivered inbox. A label that sanitises down to nothing falls back to the
text label, so the button cannot render invisible.

☠️ **The first render is not a user edit.** Quill re-serialises whatever it is handed and reports it as
an `api` change, and healing a bare token rewrites the body too. Both arrive through the new `onNormalize`
prop, and all three pages move their `original*` baseline with it. Skip that and an untouched form reads
as dirty, offering to save a template nobody touched — while Save is refused when clean.

Covered by `src/components/richTextEditor/emailPlaceholders.test.js` (11 tests, all passing —
[TESTING.md](../TESTING.md)), including the nested-anchor case, the sanitiser and the fixed point.

---

### Delivery state lives on the row (`ws-404`)

`test_invitations` gained `email_status` · `email_sent_at` · `email_error`. The status is the only
record of what happened to an address, since the 202 cannot report it:

```
pending ──────► sent      mail accepted by the SMTP server
   │
   └──────────► failed    3 attempts exhausted → credit refunded, is_revoked = true
```

`getUnregisteredInvitations` maps these onto its `status` field, which now has **five** values —
`sending` (still pending), `failed`, plus the existing `revoked`, `expired`, `pending`. Both new values
are handled in `InvitedPatientsTab.js`; a `failed` row shows "Send Failed — Credit Refunded" and offers
no buttons, because both remediation endpoints 404 on a revoked row.

A row stuck at `pending` means a send was interrupted or deferred — the batch job leaves it there when
nothing it saw was a verdict on the address. `deferred_count` (2026-09-14 migration) says how many
separate runs have deferred it.

⭐ **`ws-404` makes that distinction explicit, and it is the point of the branch.** `sendOne()` returns
`sent` / `failed` / `deferred` instead of a bool:

```
pending ──────► sent       accepted by the server
   │
   ├──────────► failed     the service REJECTED THIS RECIPIENT, or a post-connect socket error
   │                       (timed out / closed unexpectedly — the message may already be delivered)
   │                       → credit refunded, is_revoked = true
   │
   └──────────► pending    never connected · SMTP 4xx · sender-quota 5xx · SES throttle/5xx/credentials
                           → deferred_count+1, claim released, charge and token intact
                           …unless deferred_count > mail.invitation_max_deferrals (36) → failed + refund
```

☠️ Before `ws-404`, every arm went to `failed`. A few minutes of mail-host downtime therefore looked like
a scattering of undeliverable patients — each revoked, each refunded, each needing to be re-sent by hand.
`deferralReason()` now makes the call; the full matching table (SMTP needles, SES error codes and cURL
numbers, the sender-quota phrases) is in [../JOBS.md](../JOBS.md#-connection-failure-is-not-address-rejection).

⚠️ **Deferral is narrower than "looks like a connection problem", on purpose.** An earlier version
deferred `has been closed unexpectedly` and the read/write failures too. Those can fire *after* the
server accepted DATA, and a deferred row is re-sent by the next sweep — so the patient could get the same
invitation three times a sweep for a week. Since `8833f697` they retry within the call and then **fail**:
one wrongly refunded address on a slow host is the cheaper mistake.

⭐ **The QA "Send Failed — Credit Refunded" clusters were the sender quota, not bad addresses.** The QA
mail host answers past 200 emails/hour with `550 … has exceeded the max emails per hour` — a 5xx, which
every other rule treats as permanent. `isSenderQuotaRejection()` (`8ee517aa`, 2026-09-16) recognises the
sender-scoped wording and defers. A bare `quota exceeded`/`over quota` still fails: that is how hosts
report the *recipient's* mailbox as full.

A batch stops after `MAX_CONSECUTIVE_CONNECTION_FAILURES = 3` consecutive deferrals and puts the whole
process into a 60-second stand-down (`HOST_STANDDOWN_SECONDS`, a static so all 20 batches of a
500-address send share one view of the outage). A `sent` **or** a `failed` resets the counter — a
rejection aimed at one address is not evidence about the host. Because a 4xx now defers, a throttling
host trips the breaker instead of quietly writing off the rest of the list.

⚠️ **The deferral cap is what stops a dead row blocking the queue.** Always the oldest `pending` id, an
unreachable row sat first in every sweep and tripped the breaker ahead of everything behind it. At 36
(≈6h of outage at one sweep per 10 min) it is written off with `Deferred N times without reaching the
mail host`. The increment and the write-off happen **while the claim is held** — releasing first let the
sweep deliver a row that was about to be revoked and refunded.

⭐ **`SweepPendingInvitationsJob`** clears these without an operator. It is dispatched
`->afterResponse()` from **`sendInvitations()` and `getUnregisteredInvitations()`** — opening the list
that shows a stranded row is what clears it — and is throttled by an atomic cache lock
(`invitations:sweep`, one run per `mail.invitation_sweep_interval`, default 600s), bounded to one batch
with its own `mail.invitation_sweep_budget` (default 60s), and ignores rows younger than
`mail.invitation_sweep_age_minutes` (default 15) so it cannot race a send still in progress.

☠️ **It needs traffic — an idle deployment sweeps nothing.** The scheduled `invitations:send-pending`
(every ten minutes) is the path that works on an idle system, but only where `backend-scheduler` runs,
i.e. `COMPOSE_PROFILES=workers` — **off by default**. Without it, the sweep plus running the command by
hand are the only routes. Having both is safe: they select the same rows via
`TestInvitation::awaitingDelivery()` (now `->whereNotNull('user_id')` inside the query, so a page of
orphans cannot starve owned rows) and each row is claimed atomically, so an address is sent once. See
[../JOBS.md](../JOBS.md) and [../DEPLOYMENT.md](../DEPLOYMENT.md).

☠️ **A row that expires while still `pending` is refunded only by the command.** `awaitingDelivery()`
requires `expires_at > now()`, so neither the sweep nor a batch will ever touch it again.
`SendPendingInvitations::expireStaleInvitations()` marks it `failed` + revoked
(`Invitation expired before it could be delivered`) and refunds it — ahead of each send, bounded by
`--limit`. No scheduler, no expiry refunds.

### SMTP connection recycling (`ws-404`)

Symfony's `SmtpTransport` reuses **one** connection and only recycles it after 100 messages
(`$restartThreshold`). SES cuts the connection well before that and answers
`421 too many messages in this connection`, then closes the socket — and the transport keeps writing to
it, so every following send in that batch fails too. That produced scattered clusters of failures
across a bulk send.

Two guards, both in `SendTestInvitationEmailsJob`:

- `capMessagesPerConnection()` sets the threshold from `config('mail.messages_per_connection')`
  (default **20**) so the client recycles before the server does.
- `isTransient()` splits SMTP **4xx** (and socket errors, code 0) from **5xx**. A 4xx drops the
  connection and retries up to 3 times; a 5xx is a real rejection and fails immediately. The 100 ms
  throttle runs after failures too — a burst of retries at a server already refusing you is what turns
  one rejection into many.

☠️ `MAIL_MESSAGES_PER_CONNECTION` **does not reach the deployed containers**: `.dockerignore` excludes
`.env` and the compose `environment:` whitelist does not list it, so `env()` always falls back to 20.
To retune in production, change the default in `config/mail.php` and deploy.

### Placeholder validation (`ws-404`)

Rendering is a literal `str_replace('{{name}}', …)` over the **stored HTML**. Anything that stops an
exact match is a silent failure: the raw `{{…}}` text is mailed to the recipient. Validation used to
require only `{{verification_link}}`, so a typo in any other placeholder shipped.

`app/Support/EmailTemplatePlaceholders.php` is now the single vocabulary, used by
`UpdateUserEmailTemplateRequest` (per-user save), `TestEmailTemplateController` (admin default) and
`templates:check-placeholders`. It catches four distinct failures:

| Failure | Example | Reported as |
|---|---|---|
| Misspelled | `{{test_namze}}` | unrecognised, with a Levenshtein "did you mean `{{test_name}}`?" |
| Split by markup | `<strong>{{test_</strong>name}}` | "split by formatting" |
| Space-padded | `{{ test_name }}` | "spacing is not allowed" |
| Required missing | no `{{verification_link}}` | missing required placeholder |

Body **and subject** are checked on both paths — the mailer substitutes into both.

⭐ `known()` is deliberately wider than the editor's catalogue: `{{email}}` and `{{token}}` render but
are not advertised, so a template already using one keeps saving. A test asserts `known()` stays in
sync with the `$variables` map in `TestInvitationMailer::send()` — **for `test_link` only**
(`EmailTemplatePlaceholderValidationTest::test_known_covers_everything_the_mailer_substitutes` passes
that type explicitly). Nothing has ever checked the org vocabulary against a renderer, which is how the
gap below went unnoticed.

☠️☠️ **`org_test_link` has no renderer at all. Do not wire it into the send path.** Its four required
placeholders — `{{patient_firstname}}`, `{{patient_lastname}}`, `{{organization_name}}`,
`{{organization_email}}` — are substituted by **nothing**, in any of the four `Mail::` sites
(`TestInvitationMailer`, `AuthController`, `TestResumeController`, `TestService`). `send()` therefore
pins `TYPE_TEST_LINK` rather than calling `typeForUser()`, and that pin is load-bearing: swapping it for
the derived type mails the literal text `{{patient_firstname}}` to the patient, and does so even for an
organization with **no** custom template, because the seeded org admin default carries all four tokens.
`send()` is not given patient context, so closing this means changing its signature and all three call
sites (`TestInvitationController`, `SendTestInvitationEmailsJob`, `SendPendingInvitations`).

Confirmed 2026-09-11 during `ws-456` review: there is **no product requirement for organizations to
have their own template**, so the gap is not scheduled to close.
`OrganizationEmailTemplateTypeTest::test_the_mailer_still_cannot_render_the_org_vocabulary` is a
tripwire that fails if the org variables are ever added, pointing at the pin that is then safe to
remove.

⚠️ **Consequence worth knowing:** `ws-456` routes an organization's *editor* to `org_test_link` while
the *send* path stays on `test_link`. An organization can therefore save a template under Settings >
Email Configuration that no email will ever use — a silent no-op, not a crash. Any future fix is a
product decision (drop the type, or give the mailer patient context), not a one-line type swap. **The
data migration to move pre-`ws-456` misfiled rows into `org_test_link` was deliberately not written for
this reason** — it would move data into a type nothing renders.

☠️ **The vocabulary is scoped by `type`, and anything that writes stored rows has to respect that** —
a data migration bypasses both save paths and answers to neither. `{{email}}` / `{{token}}` are
`test_link`-only (`unlisted()` returns them for that type alone); `{{patient_firstname}}`,
`{{patient_lastname}}`, `{{organization_name}}` and `{{organization_email}}` are `org_test_link`-only.
A row holding a token its own type does not render is a **hard 422 on every save** — the org admin
cannot edit that template at all until the token is deleted by hand — and a `FAILURE` from
`templates:check-placeholders`. So a repair migration applying one map to both types would store rows
the codebase's own scanner reports as broken, on exactly the templates it set out to fix.
`2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates` (`ws-401`, on `develop`)
derives its map from `known($row->type)` for that reason, and leaves a bracket token with nowhere valid
to go alone. Whether `{{email}}` / `{{token}}` *should* be valid for `org_test_link` is a product
question — the answer belongs in `unlisted()`, never in stored data.

⭐ **A SQL `LIKE` cannot find these.** Quill splits runs, so a corrupted placeholder can read as
`{{test_namze}}` to the recipient while the column holds `test_nam</strong>z<strong>e`. Use
`php artisan templates:check-placeholders` (strips tags first) or, in raw SQL,
`REGEXP_REPLACE(body, '<[^>]*>', '')` on MySQL 8.

⭐ **`ws-373` and `ws-404` collided in this method, and the resolution matters.** `ws-373` added the
null guard and `EmailContent::linkify()` to `sendInvitationEmail()`; `ws-404` moved that method into
`TestInvitationMailer`. Git conflicted on exactly that hunk, and either side taken whole loses
something — "ours" drops linkify and the null guard from every invitation email, "theirs" re-inlines
the send and brings back the 504. The merge keeps the extraction **and** ports both passes into the
mailer. `test_a_bare_link_placeholder_is_still_linkified()` pins it; nothing else would notice the loss.

**This and `ws-400`'s locked chips solve the same problem from opposite ends.** The chips stop a
placeholder being edited into a broken half-token in the editor; this validation rejects one that
arrives broken anyway — from the API directly, from a template saved before `ws-400`, or from a paste.
Keep both: the chips are the ergonomics, the validation is the guarantee. Neither repairs rows already
in the database — that is what the scanner command is for.

## Redeem flow

```
POST api/test-invitation/verify-code  { token, code }     ← PUBLIC
  ├─ expired?  → error         (date comparison, not datetime — see trap 3)
  ├─ is_used?  → error
  ├─ expire any other live TestSession for this invitation   ← one active session at a time
  └─ create TestSession(session_token = Str::random(32), expires_at = now + 2h)
        → returned to the SPA, stored in localStorage as `test_invitation_session_token`
```

`POST api/test-invitation/check-validity` is the read-only pre-check the SPA calls before showing the
code form.

**The invitation is marked used at test *completion*, not at redemption** —
`TestExecutionService::finalizeTestIfCompleted()` → `markTestInvitationAsUsed()`. So an abandoned test
leaves `is_used = false` and the invitation remains redeemable until it expires.

---

## Resume flow

```
POST api/test/send-resume-email   ← FlexibleAuthMiddleware. { unique_test_id, email }
  ├─ test must be pending|inprogress
  ├─ expire all still-live resume tokens for this test    ← only the newest link works
  ├─ token = Str::random(64), expires_at = now + 7d
  └─ mail the link  (heredoc HTML via emails.dynamic-template)

POST api/test/resume   ← PUBLIC. { token (size:64) }
  ├─ missing → 404 token_invalid ·  expired → 410 token_expired
  ├─ expire other live TestSessions for the same invitation
  └─ create a fresh 2-hour TestSession → { session_token, unique_test_id, test_status }
```

☠️ **`sendResumeEmail` takes both the test id and the destination address from the request body and
checks neither against the caller's session** — [S-03](../SECURITY.md#s-03--sendresumeemail-mails-a-resume-link-for-any-test-to-any-address).

---

## Cancelling

`POST api/test-invitations/{id}/cancel` (`auth:sanctum`, `API-103`):
- selects only `user_id = auth()->id()`, `is_used = false`, `is_revoked = false` — anyone else's
  invitation is a not-found,
- ⭐ **claims** the row: `UPDATE … SET is_revoked = true, expires_at = now() WHERE id = ? AND
  is_revoked = false`. If that touches 0 rows — the expiry refund in `invitations:send-pending` or a
  second cancel got there first — it returns **409 `This invitation has already been cancelled.`** and
  refunds nothing (2026-09-15; before, both refunds went through for one charge),
- expires any live `TestSession` for it,
- refunds **1 credit** as a `SOURCE_REVOKED` grant to `auth()->user()`, with `original_source` traced via
  `Credits::traceConsumedOrigin()`.

📌 **Corrected 2026-09-17.** This used to say the refund lands on the *caller* rather than the owner, so a
super admin cancelling for a customer would be credited. The select is scoped to `user_id =
auth()->id()`, so the caller **is** the owner — a super admin gets a not-found, not a misdirected
refund. (Under impersonation `auth()` is the impersonated owner, so that is correct too.) Compare
`CreditsController::revokeCredit()`, which credits `$patientTest->patient->user` explicitly.

---

## ☠️ Traps

1. ~~**`POST api/test-invitations/send` is public and spends someone else's credits.**~~ ✅ **Fixed 2026-08-26** — route is `auth:sanctum` and the body `user_id` is gone ([S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time)). Throttled 2026-09-02 (`throttle:bulk-invitations`, 5/min). `set_time_limit(0)` moved to `SendTestInvitationEmailsJob::handle()` (`ws-404`).
2. ~~**Cancel refunds the caller, not the owner.**~~ 📌 Not a real trap — the cancel query is scoped to the
   caller's own invitations (above). What *is* a trap: a cancel racing the expiry refund, which is why
   the cancel now claims the row and 409s when it loses.
3. **Expiry is compared as a date in some paths and a datetime in others** — `verifyCode()`/`checkTokenStatus()`
   comment their check as date-based ("expires_at < today") while `TestResumeToken::isExpired()` is a
   true datetime comparison. An invitation can therefore stay valid for part of its expiry day.
4. **`is_used` flips at completion, not redemption.** An abandoned attempt does not burn the invitation.
5. **Invitation and resume tokens are stored in plaintext.** A DB read yields working credentials for
   both flows.
6. **`resend_count` exists on both `test_invitations` and `patient_tests`** and they are incremented in
   different places. Do not treat either as the total.
7. **Email copy comes from four places** — `test_email_templates` (admin default), `user_email_templates`
   (per sender), `EmailTemplateService`'s hard-coded fallback, and inline heredocs (resume). Changing
   "the invitation email" may mean changing any of the four. For the *default* subject or body that is
   three edits in lockstep — seeder, fallback, data migration (`ws-400`, above).
8. **The Quill editor drops what it does not whitelist.** `components/richTextEditor/RichTextEditor.js`
   passes an explicit `formats` list to `ReactQuill`, so inline `style` attributes — and an `<a>` a user
   pastes in — can vanish on save. That is why the send path restyles and linkifies rather than trusting
   the stored markup, and why `ws-373` also repairs the stored rows
   (`2026_08_31_000001_anchor_bare_link_placeholders_in_email_templates`, chunked at 100 rows because
   `user_email_templates` holds one `longText` body per user). `ws-400` makes the list load-bearing in a
   second way: the placeholder blots are formats too, so `PLACEHOLDER_FORMATS` must be appended whenever
   `lockPlaceholders` is on, or every chip silently disappears on save.
9. **There are two repair migrations now, and both skip rows they did not expect.**
   `2026_08_31_000001_anchor_bare_link_placeholders_in_email_templates` (`ws-373`) wraps a bare
   `{{verification_link}}` in a button; `2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates`
   (`ws-401`, on `develop` since 2026-09-07 — see [README](../README.md)) rewrites the pre-`{{…}}` spelling — `[link]`, `[patient_firstname]`, `[organization_name]` —
   into canonical tokens. Each writes a row only when its pass actually changes it, so an already-anchored
   or customised template is left alone; each bumps `updated_at`, which the editor surfaces as "last
   modified"; and both are deliberately irreversible, because the state they replace is the broken one.
   `2026_09_03_000002` covers the two template tables but **not** `email_template`, where a bare `[link]`
   could equally mean `{{verification_link}}`, `{{reset_url}}` or `{{set_password_url}}`.
   ☠️ A migration writes straight past the save-path validators, so `2026_09_03_000002` carries their
   rules itself: the rewrite is scoped by the row's `type` (§Placeholder validation), a subject rewrite
   that would outgrow its column is skipped and logged (`test_email_subject` is `VARCHAR(225)`,
   `subject` `VARCHAR(250)`, and every rewrite is two characters longer than what it replaced), and any
   bracket token it could not place — mixed case, a near-miss spelling — is logged as residue. That last
   one is the only way such a token is ever seen: the validators and `templates:check-placeholders`
   recognise `{{…}}` only, so a `[Link]` is invisible to every other tool in the repo.
10. **A `{{verification_link}}` in the body is not the same as a Start Test button.** Since `ws-400` the
    three template forms validate with `hasTestLinkButton()` — an anchor check, not a substring check —
    because pass 2 only restyles links the template already anchored. Anything that validates a template
    on `includes('{{verification_link}}')` is checking the wrong thing.
11. ✅ **`ws-373` and `ws-400` both edit `EmailTemplateService`'s fallback** — resolved on `develop`, which
    keeps both sides (subject from `ws-400`, anchored body from `ws-373`). Keep both if you edit it.
12. ☠️ **The scheduled recovery is registered but not running by default.** `invitations:send-pending` is
    in `bootstrap/app.php`, but `backend-scheduler` needs `COMPOSE_PROFILES=workers`. Until an
    environment opts in, expiry refunds never happen automatically and an idle system recovers nothing.
13. **Turning the scheduler on acts on history.** Its first run resends every still-valid stranded row
    and refunds every expired one — irreversibly. Read the runbook in [../DEPLOYMENT.md](../DEPLOYMENT.md)
    before enabling it anywhere with a backlog.
