# Context: Email Invitations & Resume Links

> Load this **instead of** reading the invitation subsystem. ~2.9k tokens. This is how a patient with no
> account takes a test, and how the copy of the email that invites them is edited and stored.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/TestInvitationController.php` (559 lines) | ⭐ Send, verify, resend, cancel, list unregistered |
| `app/Http/Controllers/TestResumeController.php` (190 lines) | ⭐ Resume-link issue + redemption |
| `app/Models/TestInvitation.php` · `TestSession.php` · `TestResumeToken.php` | The three token records |
| `app/Models/TestEmailTemplates.php` · `UserEmailTemplate.php` | Per-user email copy |
| `app/Services/EmailTemplateService.php` | Picks the sender's template, or the admin default, or a hard-coded fallback |
| `app/Services/TestInvitationMailer.php` | ⭐ Renders + sends one invitation email — owns all three assembly passes (`ws-404`, extracted from the controller) |
| `app/Jobs/SendTestInvitationEmailsJob.php` | ⭐ Sends one batch of 25 after the response (`ws-404`) |
| `app/Jobs/SweepPendingInvitationsJob.php` | ⭐ Re-sends rows stranded at `pending`, using web traffic as the clock (`ws-404`, **unmerged**) |
| `app/Support/EmailTemplatePlaceholders.php` | ⭐ The one placeholder vocabulary; both save paths validate against it (`ws-404`) |
| `app/Console/Commands/SendPendingInvitations.php` | Recovers invitations stranded at `email_status='pending'` (`ws-404`) |
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
  ├─ audit: test.invitation_sent | _sent_bulk | _resent   ← only when created > 0
  ├─ 202 Accepted  ← returns here, in well under a second
  └─ AFTER the response: SendTestInvitationEmailsJob × ceil(n/25) mails the batches
                         then SweepPendingInvitationsJob (ws-404 only, throttled)
```

⚠️ **The three audit keys do not mean what the catalog says.** `AuditEventCatalog` titles
`test.invitation_sent_bulk` "Test invitation sent via CSV (bulk)", but the controller picks it purely on
`$createdCount > 1` — typing three addresses by hand emits the "via CSV" event. `test.invitation_resent`
wins over both whenever `unique_test_id` is present, whatever the count. Do not read CSV usage out of
these rows.

⭐ **Dispatch order in `sendInvitations()` is deliberate and load-bearing.** All dispatching happens
*last*, after every write. `afterResponse()` callbacks run once the response is sent whatever its
status, so anything that threw after the first dispatch would return a 500 while the mail still went
out — inviting a retry that sends the whole list twice and charges twice. The single `$deadline`
(`mail.invitation_send_budget`, default 240s) is computed once for the **whole** send, not per chunk:
the 20 batches a 500-address list produces all run back to back in the same FPM child, so a per-chunk
budget would multiply by 20.

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
address it cannot deliver, via `Credits::addCreditsToUser(..., SOURCE_REVOKED)`. It also sets
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
EmailTemplateService::getTemplateForUser(userId, typeForUser(sender))   ← org_test_link for an organization (ws-401)
   user_email_templates row  →  test_email_templates (admin default)  →  hard-coded fallback
 ├─ 1. str_replace the {{test_name}} {{verification_link}} {{verification_code}} {{expires_at}} … vars
 │     then, org only: {{patient_firstname}} {{patient_lastname}} {{organization_name}} {{organization_email}}
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

**The default subject changed on `ws-400`** (2026-08-31 — merged into `ws-404` on 2026-09-01, not yet
deployed): `Welcome to Testing Color Vision` → **`You have been invited to take a color vision test`**,
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

☠️ **`ws-373` and `ws-400` edit the same `return` block and will conflict on merge.** `ws-373` replaces
the fallback **body** (bare token → button + copy-and-paste line); `ws-400` replaces the **subject** on the
line directly above it. Neither branch is merged, so both were cut from a `develop` that still holds the
bare-token body — a trial `git merge-tree` reports exactly one conflict, in this file. The correct
resolution keeps **both**: `ws-400`'s subject with `ws-373`'s body. Taking either side wholesale silently
drops the other ticket's fix, and nothing downstream will fail loudly if you do.

### The template editor locks system values (`ws-400`)

Committed on branches `ws-400` (both repos, 2026-08-31 → 2026-09-01); the backend half is merged into
`ws-404`, not yet deployed.

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

A row stuck at `pending` means a send was interrupted — the batch job leaves it there when it cannot
reach the SMTP host.

⭐ **`ws-404` makes that distinction explicit, and it is the point of the branch.** `sendOne()` returns
`sent` / `failed` / `deferred` instead of a bool:

```
pending ──────► sent       accepted by the server
   │
   ├──────────► failed     the server REJECTED THIS RECIPIENT
   │                       → credit refunded, is_revoked = true      (a verdict on the address)
   │
   └──────────► pending    we never reached the server at all,
                           OR it declined to take the message itself
                           → claim released, charge and token intact (a verdict on nothing)
```

📌 **`deferralReason()` was widened 2026-09-16**, after QA re-reported scattered "Send Failed — Credit
Refunded" rows on a 500-address send. Two arms were added: a 4xx that outlasts the in-call attempts
(`isTransientReply()`), and — the one QA actually hit — a **5xx about the sender's own quota**
(`isSenderQuotaRejection()`). 286 rows carried `550-Domain devwaggonerllc.space has exceeded the max
emails per hour (200/200 (100%)) allowed. Message discarded.`, which is the same 550 a dead mailbox
returns, so only the wording separates them.

☠️ **Do not read "5xx" as "verdict on the address" in this subsystem.** That assumption is what the
QA report broke: the code is set by the host, and a host that caps its own customers uses the same
code for "your domain is over its limit" as for "no such mailbox". Equally, do not widen the needles
to a bare `quota exceeded` — that phrasing means the *recipient's* mailbox is full.

⚠️ QA's mail host allows **200 emails/hour** for the whole domain. A 500-address QA send therefore
cannot finish inside the hour whatever the code does; the remainder now waits for a sweep instead of
being written off. Production is `ses-v2` and does not share this limit.

☠️ Before this, both arms went to `failed`. A few minutes of mail-host downtime therefore looked like a
scattering of undeliverable patients — each revoked, each refunded, each needing to be re-sent by hand.
`isConnectionFailure()` separates them by matching the literal Symfony message formats, because Symfony
gives every one of them exception code 0 and the message is the only discriminator.

⚠️ Two of those needles (`has been closed unexpectedly`, and the read/write failures) can in principle
fire *after* the server accepted DATA, so deferring them risks a **duplicate email**. That is the
deliberate trade and the same one `recordSent()` already makes: a duplicate is milder than revoking an
invitation the recipient is holding in their inbox.

A batch stops after `MAX_CONSECUTIVE_CONNECTION_FAILURES = 3` consecutive deferrals and puts the whole
process into a 60-second stand-down (`HOST_STANDDOWN_SECONDS`, a static so all 20 batches of a
500-address send share one view of the outage). A `sent` **or** a `failed` resets the counter — a
rejection aimed at one address is not evidence about the host.

⚠️ **`SweepPendingInvitationsJob` (`ws-404`, not on `develop`)** clears these without an operator. It is dispatched
`->afterResponse()` from **`sendInvitations()` and `getUnregisteredInvitations()`** — opening the list
that shows a stranded row is what clears it — and is throttled by an atomic cache lock
(`invitations:sweep`, one run per `mail.invitation_sweep_interval`, default 600s), bounded to one batch
with its own `mail.invitation_sweep_budget` (default 60s), and ignores rows younger than
`mail.invitation_sweep_age_minutes` (default 15) so it cannot race a send still in progress.

☠️ **It needs traffic — an idle deployment sweeps nothing.** `php artisan invitations:send-pending`
remains the manual route, and on `develop` it is the *only* route.

📌 **Corrected 2026-09-14.** This said the scheduled entry `ws-404` adds does not fire for want of a
cron or `schedule:work` container. That was true of `b69a2c37`; `07a1c9b2` adds a `backend-scheduler`
service, so on `ws-404` the command **does** run every ten minutes and the sweep is the *second* of two
automatic paths rather than the only one. Both are safe to have at once — they select the same rows via
`TestInvitation::awaitingDelivery()` and each row is claimed atomically, so an address is sent once.
See [../JOBS.md](../JOBS.md) and [../DEPLOYMENT.md](../DEPLOYMENT.md).

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
that type explicitly). The org vocabulary got the same check on `ws-401`, once the mailer started
rendering it (below).

⭐ **Organizations send on `org_test_link` (`ws-401`, 2026-09-16, not merged).** Until then `send()`
pinned `TYPE_TEST_LINK`, because nothing substituted the org vocabulary's four required placeholders.
QA reported the result as a bug: an organization's Email Configuration showed the org template, but the
patient received the generic Send Test email. The earlier "no product requirement" call (`ws-456`
review, 2026-09-11) is superseded.

`send()` now resolves the type with `EmailTemplateService::typeForUser()`, the same call the editor
uses, and for an organization fills the four extra tokens itself. The signature did not change: the
sender is loaded from `$userId` and the patient from `(user_id, email)`, so none of the three call sites
were touched.

| Placeholder | Value |
|---|---|
| `{{patient_firstname}}` / `{{patient_lastname}}` | Latest `patients` row with this owner's `user_id` and this `email`. **Send Test collects only an address**, so a first invitation has no patient: first name becomes `Patient`, last name empty. |
| `{{organization_name}}` | `organizations.organization_name`, else `users.company_name` |
| `{{organization_email}}` | The organization user's login `email` (what legacy `trigger_patient_testEmail()` used) |

- **The name pair is filled as a unit first.** `{{patient_firstname}}<space or &nbsp;>{{patient_lastname}}`
  becomes the full name (or `Patient`). Filled token by token, an unknown last name would leave
  `Dear Patient :` in the seeded greeting.
- **Org values run *after* the generic pass** so a name typed as `{{verification_link}}` is not
  substituted. They are `e()`-escaped in the body and raw in the subject (a plain-text header).
- ☠️ **The patient lookup matches plaintext `patients.email`.** If the patient-encryption draft
  (`.tcv-encryption-draft`) lands, this query has to move to the blind index with it, like every other
  `Patient::where('email', …)`. Otherwise it finds nobody and every greeting silently becomes `Patient`.
- ⚠️ The other three `Mail::` sites (`AuthController`, `TestResumeController`, `TestService`) still
  render no org vocabulary. None of them reads `org_test_link`, so nothing is broken today.

Covered by `OrganizationEmailTemplateTypeTest` (sends through `SendTestInvitationEmailsJob` and reads
the delivered message) and
`EmailTemplatePlaceholderValidationTest::test_known_org_vocabulary_is_everything_the_mailer_substitutes_for_an_organization`,
which fails if the editor's org vocabulary and the mailer drift apart.

⚠️ **Pre-`ws-456` misfiled rows are still not migrated.** An organization that saved its copy before
`ws-456` has it under `test_link`, which neither the editor nor the send path reads for that account
any more. It now gets the org admin default until it saves again.

☠️ **The vocabulary is scoped by `type`, and anything that writes stored rows has to respect that** —
a data migration bypasses both save paths and answers to neither. `{{email}}` / `{{token}}` are
`test_link`-only (`unlisted()` returns them for that type alone); `{{patient_firstname}}`,
`{{patient_lastname}}`, `{{organization_name}}` and `{{organization_email}}` are `org_test_link`-only.
A row holding a token its own type does not render is a **hard 422 on every save** — the org admin
cannot edit that template at all until the token is deleted by hand — and a `FAILURE` from
`templates:check-placeholders`. So a repair migration applying one map to both types would store rows
the codebase's own scanner reports as broken, on exactly the templates it set out to fix.
`2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates` (`ws-401`, **not merged**)
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

`POST api/test-invitations/{id}/cancel` (`auth:sanctum`):
- sets `expires_at = now()` on the invitation **and** on any live `TestSession` for it,
- refunds **1 credit** as a `SOURCE_REVOKED` grant to `auth()->user()`.

☠️ The refund goes to the **caller**, not to the invitation's `user_id`. For a super admin cancelling on
a customer's behalf, the credit lands in the wrong account. Compare with
`CreditsController::revokeCredit()`, which correctly credits `$patientTest->patient->user`.

---

## ☠️ Traps

1. ~~**`POST api/test-invitations/send` is public and spends someone else's credits.**~~ ✅ **Fixed 2026-08-26** — route is `auth:sanctum` and the body `user_id` is gone ([S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time)). Throttled 2026-09-02 (`throttle:bulk-invitations`, 5/min). `set_time_limit(0)` moved to `SendTestInvitationEmailsJob::handle()` (`ws-404`).
2. **Cancel refunds the caller, not the owner** (above).
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
   (`ws-401`, **not merged** — see [README](../README.md)) rewrites the pre-`{{…}}` spelling — `[link]`, `[patient_firstname]`, `[organization_name]` —
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
11. **`ws-373` and `ws-400` conflict in `EmailTemplateService`** and the resolution must keep both sides
    (subject from `ws-400`, body from `ws-373`) — see the send-flow section. Neither is merged yet.
