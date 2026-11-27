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
| `app/Services/TestInvitationMailer.php` | ⭐ Renders + sends one invitation email — owns every assembly pass (`ws-404`, extracted from the controller; `ws-401` added the org-placeholder pass and made the template type follow the sender) |
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

> ✅ **`ws-401`'s second round merged into `develop` on 2026-09-21** (PR #252, `345459fc`, branch head
> `2759d251`), which is the head this pack described, including the review round that moved the org
> substitution after `linkify()`. It is what gives `org_test_link` a renderer, so statements here about an
> organization's invitation using its own template now describe `develop`. Two follow-ups merged
> 2026-09-22 (PRs #276/#278) and put `{{verification_code}}` / `{{expires_at}}` into the `org_test_link`
> bodies. See [below](#org_test_link-carries-the-verification-code-and-expiry-ws-401-2026-09-22). The
> ticket's first round (the `2026_09_03_000002` placeholder-repair migration) merged earlier as PR #212.
> The `INDEXES/*` were not regenerated for this, deliberately — indexing a feature branch hides live
> holes.

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

**Changed on branch `fix/test-email-and-account-restriction` (2026-11-03, unmerged):** the resend now
**keeps** the token and code and renews only the 7-day window — rotating them killed the link and code in
every email the test taker already held. It also no longer mails the sender's `test_link` /
`org_test_link`: `TestInvitationMailer::send(..., isResend: true)` takes `sendResend()`, which renders the
Super Admin's editable **Test Link Resent** (`email_template.test_link_resent`) for **every** sender,
organizations included — **if the row was already mailed** (`email_status = 'sent'`) — with `{{test_url}}` =
this invitation's own `/test-invitation/verify/{token}`, via
the `emails.testLink` view (the same as `TestService::generateAndSendTestLink()`). The resend carries **no
code or expiry** — the test taker uses the original email's code (planned improvement I-7). ☠️ A row
still `'pending'` (queued, or deferred after the mail host did not answer) was never received, so its
resend sends the **full first invitation** instead: the resend marks the row `'sent'`, which takes it out
of the batch, and a codeless resend would leave the test taker with no code at all. An
organization-specific resend template is planned as I-1. The first send is unchanged. Pinned by
`tests/Feature/TestInvitations/ResendUnregisteredInvitationTest.php`.

### How the invitation body is assembled

`TestInvitationMailer::send()` is a sequence of passes over one string, and the order matters. (It was
`TestInvitationController::sendInvitationEmail()` until `ws-404` extracted it; the controller method
survives as a one-line delegate for the resend path. Both the batch job and the resend now go through
the same passes — see the merge note below.) ⚠️ **`ws-401` added a fourth pass for the org placeholders
and made the template type follow the sender** — the first line below read `TYPE_TEST_LINK`, pinned,
until then. **On `develop` since 2026-09-21** (PR #252). This was the ticket's second round; its first
(the placeholder repair migration) merged as PR #212.

```
sender = User::with('organization')->find(userId)  memoised per instance   ← ws-401
EmailTemplateService::getTemplateForUser(userId, typeForUser(sender))      ← ws-401
   memoised per instance by user_id|type
   user_email_templates row  →  test_email_templates (admin default)  →  hard-coded fallback
 ├─ 1. str_replace the {{test_name}} {{verification_link}} {{verification_code}} {{expires_at}} … vars
 ├─ 2. restyle: preg_replace_callback rewrites <a href="{the link}"> into the blue button
 ├─ 3. EmailContent::linkify(): wrap any URL still sitting in plain text   ← ws-373
 └─ 4. org_test_link only, and only with a non-null sender: the four org    ← ws-401
       placeholders, from the sender + a Patient scoped to (user_id, email).
       LAST — after every pass that rewrites markup, never before one.
```

⚠️ **The `$sender !== null` half of pass 4's condition is unreachable today and stays anyway.**
`typeForUser(null)` casts to `0`, `User::ORGANIZATION` is `4`, so a deleted sender never enters the
branch — but that is a value coincidence in a *different* class, and `organizationVariables()` types
its parameter non-nullable. If it ever did fire, the `TypeError` would be caught by
`SendTestInvitationEmailsJob::sendOne()`'s `catch (\Throwable)` and scored as a **bad address**:
invitation revoked, credit refunded, nothing in the log saying the account was missing. Added in PR
review round 2 (`ws-401`, 2026-09-17).

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
sync with the `$variables` map in `TestInvitationMailer::send()`, **per type** —
`EmailTemplatePlaceholderValidationTest::test_known_covers_everything_the_mailer_substitutes` for
`test_link`, and `…::test_known_org_vocabulary_is_everything_the_mailer_substitutes_for_an_organization`
for `org_test_link` (`ws-401`). Until the second one landed, nothing had ever checked the org vocabulary
against a renderer, which is how the gap below went unnoticed for six weeks.

✅ **`org_test_link` has a renderer as of `ws-401`** (**merged 2026-09-21**, PR #252. PR review
round 3 that day was the first pass that ran the suite against a database, and it moved the code.
See the `whereEmail` and spacing-rule traps below, either of which would have shipped otherwise).
`send()` derives the type with `EmailTemplateService::typeForUser($sender)` instead of pinning
`TYPE_TEST_LINK`, and fills all four org-only placeholders, so what an organization edits under Settings
> Email Configuration is what the patient receives. QA's report is what reopened it: the editor showed
the org template, the patient got the generic one. That supersedes the `ws-456` review's conclusion
(2026-09-11) that there was no product requirement for organizations to have their own template and the
gap was therefore not scheduled to close.

⚠️ **That reversal is an engineering decision so far, not a recorded product sign-off** — raised in PR
review on 2026-09-17 and still open at the time of writing. The precondition the `ws-456` decision named
*is* met (all four placeholders are filled, which is why the tripwire below could be retired), so the
code is not the blocker; what is missing is the product confirmation that organizations should send
their own template at all. If that sign-off lands, delete this note. If it does **not**, the revert is
bigger than re-pinning the type: the four org values, their escaping and their ordering all exist only
to serve `org_test_link`.

☠️ **The pin `ws-401` removed was load-bearing, and its removal had conditions.** Until then
`{{patient_firstname}}`, `{{patient_lastname}}`, `{{organization_name}}` and `{{organization_email}}`
were substituted by **nothing** in any of the four `Mail::` sites (`TestInvitationMailer`,
`AuthController`, `TestResumeController`, `TestService`), so deriving the type mailed the literal text
`{{patient_firstname}}` to the patient — and did so even for an organization with **no** custom
template, because the seeded org admin default carries all four.
`OrganizationEmailTemplateTypeTest::test_the_mailer_still_cannot_render_the_org_vocabulary` was the
tripwire; `ws-401` retired it only after meeting both conditions it named (give `send()` patient
context, substitute all four). **If you add a fifth org placeholder to the catalogue, add its value to
`organizationVariables()` in the same commit** — the catalogue is what the editor offers, and an offered
token nothing fills is mailed to the patient as literal text.

⭐ **Two tests render the *seeded* org body, not a simplified stand-in.** Every other test in the class
overwrites the admin default with a short template, so none of them proves the row a real organization
actually receives comes out clean. `test_the_seeded_org_template_renders_with_nothing_left_unfilled`
and `…_for_an_address_with_no_patient` run `AdminSettingsSeeder` first and assert `{{` survives
nowhere — which is what would catch a placeholder added to the seed and not to
`organizationVariables()`, the failure the note above warns about.

⭐ **Where the org values come from, and what happens when they are missing.** Send Test collects an
email address and nothing else, so the patient's name is known only when the sender already has a
`patients` row on that address — `Patient::whereEmail($email)->where('user_id', $sender->id)->orderBy('id')`,
scoped to the sender so another account's patient on the same address cannot leak a name into the
email. ⚠️ **`orderBy('id')`, not `latest('id')`, and it has to stay that way:** nothing enforces one
patient per address, so this can find several, and `TestController::patientByEmail()` resolves the same
duplicates oldest-first. Disagree with it and the invitation greets one name while the test that
invitation leads to shows another. `…::test_a_duplicated_address_greets_the_same_patient_the_test_flow_resolves`
pins the agreement rather than the choice. A first invitation has no name and the greeting falls back to the literal `Test Taker` (`UNKNOWN_PATIENT_NAME`, `TestInvitationMailer.php:25`; was `Patient` before PR #315) — but only
when *both* names are unknown, because "Dear Test Taker Doe" is not a greeting. That makes the greeting a
**three-way** choice, and the third branch is the one to remember: a row with a surname and no first
name greets by surname alone, `Dear Doe:`. It is a copy decision rather than a consequence of the
code, so it is pinned by `…::test_a_patient_with_only_a_surname_is_greeted_by_surname` against the
*seeded* body — change the wording and that test is where it is recorded.
`{{organization_name}}` resolves `organization.organization_name` → `users.company_name` → `''`, and
each step tests for an **empty string, not null**: an `organizations` row carrying `''` is not a null
one, so a `??` chain would stop there and never reach `company_name`.
`{{organization_email}}` is the sender's **login** address, the same column the legacy
`trigger_patient_testEmail()` used.

☠️☠️ **That lookup must go through `Patient::whereEmail()` — never `where('email', $email)`.**
`patients.email` is ciphertext with a random IV, so comparing the column encrypts the needle afresh and
matches **nothing**, silently: every organization is greeted `Dear Test Taker`, including the re-invite the
feature exists for. `ws-401` carried exactly that bug through **three** review rounds (PR round 3,
2026-09-21). It survived because those rounds verified the code by extracting functions into standalone
PHP harnesses, which exercise the string logic and never touch a query — against a database ten tests
in `OrganizationEmailTemplateTypeTest` fail on it immediately, and the one test that *passed*
(`…::test_a_patient_belonging_to_another_account_is_not_used`) passed because nothing was ever found.
The scope matches the keyed md5 in `patients.identification`; `LegacyEncrypter::emailIndex()`
lower-cases and trims before hashing, which is also what makes the match case-insensitive — the address
is typed into Send Test by hand and need not match the patient's stored casing
(`…::test_the_patient_is_found_whatever_case_the_address_was_typed_in`). Same rule for
`test_invitations.email` (`TestInvitation::whereEmail()`) and for anything else in
[SECURITY.md](../SECURITY.md)'s legacy-encrypted column list.

⚠️ **`send()` keeps its signature — this pack previously predicted otherwise.** The retired tripwire's
note said closing the gap meant giving `send()` patient context and changing all three call sites.
`ws-401` did neither: the patient is resolved inside the mailer from the recipient address it already
receives. If you are reading an older copy of this pack, that prediction is the part that is stale.

⚠️ **An organization with no name at all still sends, and says so in the log.** With neither an
`organizations` row nor a `company_name`, the seeded template's sign-off goes out with a blank line
where the sender's identity belongs — a silent degradation, since the send itself succeeds. `ws-401`
logs `Organization invitation has no organization name to substitute` with the `user_id` **and no
recipient address**. If that line shows up in QA, the account is missing its `organizations` row; it is
not a mail fault.

⚡ **Emitted once per account, not once per recipient** — guarded by a `$missingNameWarned` set keyed on
`$sender->id` (`ws-401` review round two). The warning sits inside `organizationVariables()`, which is
memoised by `user_id|email`, so that memo only ever suppressed a repeat of the *same* address: one
misconfigured organization sending a 500-address batch wrote up to 500 identical lines, while the context
said "account-level". That is the shape of noise that gets a real warning filtered out of the stream. The
guard is per mailer instance, the same bound as `$senders`, so the honest worst case is one line per
account per batch rather than one for the whole send — a static latch would be once per worker *process*
and would hide the fault from the next send too.
`OrganizationEmailTemplateTypeTest::test_the_missing_organization_name_is_logged_once_per_account` pins it.

☠️ **Neither log line `ws-401` adds carries the invitee's address, and new ones must not either.**
`SendTestInvitationEmailsJob::withoutAddresses()` exists precisely to strip addresses that SMTP errors
quote ("550 5.1.1 `<jane@example.com>` …"), so putting one in a context array walks straight past that
guard — and `test_invitations.email` / `patients.email` are encrypted at rest, which logging in
plaintext undoes. The row is the record: `email_status`, `email_sent_at` and `email_error` carry the
full text for the practice that typed the address.

⭐ **The success-path `Log::info('Invitation email sent', …)` no longer carries one either.** It predates
`ws-401` and round one left it alone; review round two took the address out on the reviewer's argument
that `ws-401` was already editing that line to add `template_type`, so "pre-existing" had stopped
applying to it. It now logs `user_id` and `template_type` only. Nothing is lost that answers a real
question — `email_status` / `email_sent_at` on the row are what answer *"was this patient sent their
invitation"*, and they outlive log rotation.

⭐ **No exception survives: `TestInvitationMailer` now logs no recipient address at all.** The `ws-373`
restyle warning above it (`Invitation link restyle failed, sending unstyled content`) carried one until
review round three and no longer does. It was briefly argued as its own ticket — `ws-401` does not
otherwise touch that line — and the counter-argument won: one address left in a file that strips them
everywhere else is a hole whichever line it sits on, and a reviewer will keep finding it.

⚡ **On that line `user_id` is the better key anyway, not merely the safer one.** What failed is the
*template body* outgrowing the backtrack limit, and the body belongs to the account — every recipient of
that template trips it. The address names one symptom; the account names the cause you can act on.

⚠️ **The org block's `$sender === null` branch is unreachable, and deliberately not left as a `skip`.**
`typeForUser()` casts a null sender to `0` and `User::ORGANIZATION` is `4`, so a missing sender always
resolves to `test_link` and never enters the block — but that is a value coincidence in another class,
and both wrong answers fail silently. Passing null to `organizationVariables()` is a `TypeError`, which
`SendTestInvitationEmailsJob::sendOne()`'s `catch (\Throwable)` writes off as a bad address, revoking
the invitation and refunding the credit; *skipping* the block mails the patient a literal
`{{patient_firstname}}`. So it logs `Organization template resolved without a sender` and substitutes
**empty** values, which strips the four tokens through the same path a blank surname takes.

⚡ **All three lookups are memoised on the mailer instance** (`$senders` by user id, `$templates` by
`user_id|type`, and `$organizationValues` by `user_id|email`, the address lower-cased and trimmed so the
memo dedupes exactly what the case-insensitive lookup would match). `send()` runs once per delivery
*attempt*, so uncached they repeat on all three tries inside `SendTestInvitationEmailsJob::sendOne()`'s
retry loop, and the sender's and the template's repeat for every address in a batch — all of which share
one sender — against the 240s budget. The mailer is not a container singleton, so the memo's lifetime is
one job. Keyed rather than single-slot because `SweepPendingInvitationsJob` walks several senders through
one instance. `OrganizationEmailTemplateTypeTest::test_the_per_send_lookups_are_not_repeated` counts the
queries and pins all three.

⚠️ **`$templates` is memoised on the mailer and deliberately *not* inside `EmailTemplateService`.** That
service instance is shared with `UserEmailTemplateController`, where a save followed by a read inside one
request has to see the row just written; caching there would serve the pre-save copy. Added in `ws-401`
review round two, where the template lookup was the last per-address query left in `send()`. Only the
`user_email_templates` row was ever repeating — `EmailTemplateRepository::getAdminDefaultTemplate()` has
kept the admin default behind an hour-long `Cache::remember` all along, which is why the regression
assertion counts that table and not `test_email_templates`.

⭐ **An empty value is not substituted — the token is *removed*, with the spacing that was only there
to separate it.** The seeded greeting is `Dear {{patient_firstname}} {{patient_lastname}}:`, and
substituting `''` for an unknown surname leaves `Dear Patient :`. Dropping the token alone does not
help: the space in front of it is the part that shows. `removeEmptyToken()` matches a bounded run of
whitespace, `&nbsp;`/`&#160;` (Quill emits the entity) **and tags** on either side of the token, then
re-emits every tag untouched and decides the surviving space from **which side the template put the
whitespace on**.

☠️ **That rule took three attempts. Know which one you are looking at before you change it.**

| Round | Rule | What it broke |
|---|---|---|
| 1 | whitespace on **both** sides | `Dear {{patient_firstname}}<strong>{{patient_lastname}}</strong>,` with a blank `first_name` rendered **`DearDoe,`** |
| 2 | whitespace on **either** side, minus a `HUGGING_PUNCTUATION` denylist (`.,;:!?)]}`) | every mark *off* the list gained a space the template never had — `{{patient_lastname}}'s results` greeted a blank surname as **`Jane 's results`** |
| 3 (current, 2026-09-21) | **both** sides → keep one space unless the next character hugs; **one** side → keep it only when *markup* sat on the other side **and** losing it would run two words together | leaves an invisible leading space when a token opens a `<p>` — see below |

Why the split: whitespace on both sides is *two* separators with one token between them, so one leaves
with the token and one stays. Whitespace on one side is a *single* separator, and dropping the token
decides whose it was — in `{{patient_firstname}} {{patient_lastname}}'s` it separated the two names, so
with the surname gone `Jane` meets `'s` directly. **A denylist cannot work on that branch**; it would
have to enumerate every mark anyone might type. The one-side branch uses `WORD_START`
(`/^[\p{L}\p{N}\{]/u`) instead, which is the SPA's own `WORD_EDGE` from `emailPlaceholders.js` plus `{`
— the backend needs `{` because the four keys are `strtr`'d *after* the empty ones are removed, so the
character after a dropped `{{patient_firstname}}` is usually the `{` of a `{{patient_lastname}}` still
awaiting its value. Read it as punctuation and `DearDoe,` comes straight back.

The "markup on the other side" half is the rest of it: the space survives only where the *editor* split
two real neighbours, never where the template wrote them adjacent. Without it
`{{patient_lastname}}2024` gains a space it never had (`Jane 2024`) while the same template with a
surname renders `Jane Doe2024`. ⚠️ **Known cost:** an opening `<p>` is markup on the other side too, so
a token that *starts* a paragraph keeps a leading space — `<p> Jane rocks</p>`. HTML collapses it, and
telling the two apart means duplicating `EmailContent::BLOCK_TAGS` inside this function, so it is left
and pinned by the `token opens the paragraph` data set rather than fixed.

`HUGGING_PUNCTUATION` still exists but is now consulted **only** on the both-sides branch — that is what
keeps `Dear {{patient_firstname}} {{patient_lastname}} :` from sending as `Dear Jane :`, while
`&mdash;` with spaces around it keeps its gap (the entity is not whitespace to the pattern, so a
word-start test would have closed it up — the both-sides branch never asks).

`patients.first_name` is nullable and `storeDefaultPatient()` validates nothing about it, so the round-1
`DearDoe,` case is reachable from the launch URL, not theoretical. Three data-provider tests pull in
opposite directions by design — **change one and run all three**:
`…::test_an_unknown_surname_leaves_no_stray_space` (6), `…::test_a_blank_first_name_keeps_the_surname_spaced`
(4), `…::test_a_dropped_surname_leaves_the_templates_own_spacing` (10, the round-3 cases).

☠️ **Do not narrow that run back to whitespace-only.** The first cut of this matched the *pair* of
tokens with `\s` between them, which meant the fix applied to `{{patient_firstname}} {{patient_lastname}}`
and nothing else. Quill wraps edited runs in its own markup, so
`<strong>{{patient_firstname}}</strong> {{patient_lastname}}` — the normal case here, not an exotic one
— fell through to the per-token pass and produced the exact `Dear Patient ,` the code exists to prevent.
Tags are preserved rather than swallowed because deleting the closing half of a pair to tidy up a space
unbalances the body. `test_an_unknown_surname_leaves_no_stray_space` covers six spacings, three of them
with markup between the tokens; `test_a_known_surname_keeps_its_spacing_through_markup` is the other
direction.

☠️☠️ **The org pass runs LAST — after every pass that rewrites markup, not merely after the generic
substitution.** All four values are typed in by users, and `patients` rows are written by
`OrganizationPatientController::storeDefaultPatient()` through a field whitelist that validates
`gender` and nothing else, from an endpoint reachable by anyone holding the organization's launch URL
(a permanent bearer credential — see [ORGANIZATION_CONTEXT](ORGANIZATION_CONTEXT.md)). Two orderings
are wrong, for two different reasons:

| Filled before | What the patient receives |
|---|---|
| pass 1 (generic) | a name holding `{{verification_link}}` is substituted with the real link |
| pass 3 (`linkify`) | a name holding `https://evil.example` comes back out as a **working anchor** |

The second is the one that bit in review (`ws-401`, PR round 2). `e()` escapes `&  <  >  "  '` — it does
**not** touch a URL scheme — and `EmailContent::URL_PATTERN` matches any bare `http(s)://…` in a text
node, which is exactly what the greeting is. A patient row named
`Claim your results https://evil.example` on a target address therefore turned the organization's next
invitation to that address into a genuine, org-branded email carrying an attacker-chosen link.
`test_a_url_inside_a_patient_name_is_not_turned_into_a_link` and its `organization_name` twin pin the
ordering; `test_the_verification_link_is_still_anchored_for_an_organization` pins that the guard did not
cost the button. **If you add a pass to `send()`, it goes above the org block, never below it.**

⭐ **One `strtr()`, not a `str_replace()` per key.** `strtr` makes a single left-to-right pass and never
re-examines what it just inserted, so a patient named `{{organization_name}}` is greeted by that literal
text instead of having the *next* key fill it in — the same "a value must not be read by a later pass"
rule as the ordering above, one level down. It also keeps a `$1` in a name literal, which `preg_replace`
would not. `test_a_placeholder_typed_into_a_name_is_not_substituted` covers it.

☠️ **The body is escaped; the subject is not.** Body values go through `e()` because they land in HTML;
the subject's do not, because it is a plain-text header — **safe only because the one place the subject
reaches HTML is `<title>{{ $subject }}</title>` in `emails.dynamic-template`, which Blade escapes.** If
that view is ever changed to render the subject with `{!! !!}`, the subject values must be escaped too.
⚠️ For the same reason, assert escaping against the **rendered content** (`.container`), never the whole
HTML body: the title supplies `&amp;` and `&lt;` on its own, so a whole-body assertion passes with `e()`
deleted. `test_org_values_are_escaped_in_the_body_but_not_the_subject` uses the `renderedContent()`
helper and also asserts the raw markup is **absent**, which is the half that actually fails without
`e()`.

☠️ **A pre-`ws-456` row filed under `test_link` is still not migrated — the reason changed, the answer
did not.** `ws-456` routed an organization's *editor* to `org_test_link` while the send path stayed on
`test_link`, so anything an organization saved before `ws-456` sits under `test_link`. That was a silent
no-op (a template saved under Settings > Email Configuration that no email would ever use) until
`ws-401` fixed it for everything saved since — but those old rows are deliberately left where they are.
Previously a backfill was refused because it would move data into a type nothing rendered; now it is
refused because re-typing stores a body written for the generic vocabulary under a type that requires
four placeholders it does not carry and treats its `{{email}}` / `{{token}}` as unrecognised — **a hard
422 on every save**, so that org admin could no longer edit the template at all. Losing a row that has
been unreachable from the editor since `ws-456` beats locking the editor that replaces it.
`OrganizationEmailTemplateTypeTest::test_a_pre_ws_456_row_saved_under_the_generic_type_is_not_resurrected`
pins the decision. To size it on a deployed DB: `SELECT uet.user_id FROM user_email_templates uet
JOIN users u ON u.id = uet.user_id WHERE u.usertype = 4 AND uet.type = 'test_link'` — any hits just need
re-saving once through the editor.

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

### `org_test_link` carries the verification code and expiry (`ws-401`, 2026-09-22)

The seeded `org_test_link` body had no `{{verification_code}}` or `{{expires_at}}`, although `test_link`
did and the mailer already substituted both for either type. Once `ws-401` round 2 made organisations
send their own template, their patients received a link with **no code to enter at
`POST api/test-invitation/verify-code`**. Three migrations fix it, plus `AdminSettingsSeeder` (id 2)
for fresh databases. The block sits **below** the Start Test button:

| Migration | PR | What it does |
|---|---|---|
| `2026_09_22_000001_add_verification_code_and_expiry_to_org_test_link_template` | #276 `d39c81e1` | Exact-fragment `LIKE` replace on the admin default (`test_email_templates`) only |
| `2026_09_22_000002_move_verification_code_and_expiry_below_start_test_button` | #276 | Same technique, moves the block below the button |
| `2026_09_22_000003_backfill_verification_code_and_expiry_in_org_test_link_templates` | #278 `8657e85c` | The real fix: anchors on the `{{verification_link}}` `<a>` (or the bare token) and inserts after it, in **both** `test_email_templates` **and** `user_email_templates` |

☠️ **`000001` and `000002` are silent no-ops wherever an admin has opened the default in the editor.**
Quill rewrites `<p><br />…` into `<p><br></p><p>…`, so their ~150-character verbatim fragment matched
nothing. `up()` still returned cleanly and the migrations were recorded as run, and `migrate` will
never retry them. `000003` exists because of that. **Never write another template migration that
matches a fragment of stored HTML verbatim.** Anchor on a placeholder instead, because the editor
leaves placeholders intact.

- `000003` is idempotent. A body already containing either token is skipped, and a body with no link to
  anchor on is left alone rather than guessed at.
- It **does** touch organisations' own copies, unlike the two before it, which left customised copies
  alone because they were wording changes. An org copy without the code sends an invitation nobody can
  complete, so it is repaired the same way as `2026_08_31_000001_anchor_bare_link_placeholders_in_email_templates`.
- It bumps `updated_at` and calls `Cache::forget('test_email_templates_org_test_link')`, because
  `EmailTemplateRepository::getAdminDefaultTemplate()` caches for an hour. A deploy clears the cache in
  `entrypoint.sh` anyway. A **manual** run on a live container would otherwise keep sending the old body
  for up to an hour.
- ☠️ It broke one older test. See [TESTING.md](../TESTING.md#-develop-is-red-since-2026-09-22-one-stale-ws-401-test).

## Redeem flow

```
POST api/test-invitation/verify-code  { token, code }     ← PUBLIC
  ├─ expired?  → error         (date comparison, not datetime — see trap 3)
  ├─ is_used?  → error
  ├─ expire any other live TestSession for this invitation   ← one active session at a time
  └─ create TestSession(session_token = Str::random(32), expires_at = now + 2h)
        → returned to the SPA, stored in localStorage as `test_invitation_session_token`
  response data: email, token, session_token, active_unique_test_id, existing_patient,
                 expires_at  (UTC ISO 8601 — the confirm popups say "expires today/tomorrow/in N days", see
                              "Confirm Changes / Start Test popups" below)
```

`POST api/test-invitation/check-validity` is the read-only pre-check the SPA calls before showing the
code form.

**The invitation is marked used at test *completion*, not at redemption** —
`TestExecutionService::finalizeTestIfCompleted()` → `markTestInvitationAsUsed()`. So an abandoned test
leaves `is_used = false` and the invitation remains redeemable until it expires.

**Both-eyes (WS-692 follow-up, 2026-11-26):** the invitation is marked used only when **both** eyes are done.
`markTestInvitationAsUsed()` returns early while the other eye is still `pending`/`inprogress`. Older rows
had `is_used = true` after the first eye, so `verifyCode()` / `checkTokenStatus()` now ask
`TestInvitation::isFinished()` (`is_used` **and** no open `patient_tests` row on it). Without that, a taker
between eyes got "already been completed" and could never finish the right eye.

`assignTest()` refuses a second assignment on an invitation that already has a test: **409
`INVITATION_ALREADY_STARTED`** (`api.invitation_already_started`). The taker continues through
`verify-code`, which hands back `active_unique_test_id`. One invitation is paid for once.

---

## Resume flow

```
POST api/test/send-resume-email   ← FlexibleAuthMiddleware. { unique_test_id, email }
  ├─ test must be pending|inprogress, and the caller must own it
  └─ TestLinkRenewalService::issueResumeLink()   (WS-692 follow-up, on develop since 2026-11-26)
       → resendResumeLink(): one link per test taker per test, both eyes share it,
         under the cache lock  resume-link:{parent_test_id ?? unique_test_id}
         ├─ a link exists, still valid → mail that same token, expiry unchanged
         ├─ a link exists, expired     → mail that same token, expires_at = now + 7d
         └─ none yet                   → token = Str::random(64), expires_at = now + 7d
       mailed first, saved after (a failed send records nothing)
       email = the `test_resume_link` system template: {{expiry_days}} = days actually left
         (rounded up, min 1), {{expires_at}} = the link's real expiry (m/d/Y)

POST api/test/resume   ← PUBLIC. { token (size:64) }
  ├─ missing → 404 token_invalid ·  expired → 410 token_expired
  ├─ token's eye no longer open and it is a both-eyes test → open the pair's open eye
  │   (inprogress before pending); response unique_test_id = the eye actually opened
  ├─ nothing open → 409 test_completed
  ├─ expire other live TestSessions for the same invitation
  └─ create a fresh 2-hour TestSession → { session_token, unique_test_id, test_status }
```

📌 **Before 2026-11-26** every send expired the previous resume links and minted a new one ("only the newest
link works"). Now the start email and the Test Takers page's Resend both reuse the pair's link, so neither
can kill a link the other just mailed. Do not reintroduce "expire on send" there. `expireResumeLinks()`
still exists and is used in one place: a Send Test Resend that **renews** an expired invitation, since the
invitation is then the taker's only link.

☠️ **`sendResumeEmail` takes both the test id and the destination address from the request body and
checks neither against the caller's session** — [S-03](../SECURITY.md#s-03--sendresumeemail-mails-a-resume-link-for-any-test-to-any-address).

---

## Cancelling

`POST api/test-invitations/{id}/cancel` (`auth:sanctum`, `API-110`):
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

### What the test taker sees when an invitation is cancelled mid-flow

The scenario: the invitation is cancelled while the taker is already verified, on the add form or the
confirm page. Their next request (Save Test Taker or Start Test) then fails.

- **Backend:** cancelling only sets `expires_at = now()` on the sessions. It leaves `invalidated_reason`
  null (only `superseded` exists). So `FlexibleAuthMiddleware` returns the generic **`401
  session_expired`**. It cannot tell a revoked session from one that timed out after 2 hours.
- **Frontend** (`src/utils/invitationErrors.js` → `invitationDeadEnd()`, WS-699, frontend PR #492): any
  `session_*` / 401 shows **"Your session has ended" / "Your session has expired. Enter your verification
  code again to continue." / Verify again**.
  - 📌 The message deliberately gives no duration. It first said "sessions end after 2 hours", which was
    false here. Fixed in `d5b87b7` on 2026-11-19.
- **Verify page:** "Verify again" lands here. `verify-code` sees `is_revoked` and only then shows the real
  reason: "Invalid Token — This invitation link has been cancelled…".

So the taker sees **two popups**: expired first, then cancelled.

- **Decided 2026-11-19: wording only.** The rejected alternative would show "Invitation cancelled" directly:
  - backend: tag the sessions `invalidated_reason = 'cancelled'` on cancel; the middleware returns a
    distinct `error_type`. The column already exists, so no migration is needed.
  - frontend: map that `error_type` to the existing 410 "Invitation cancelled" dead end.
- **On `develop`, before WS-699:** the same 401 showed "Failed to add test taker. Session has expired." and
  left the taker on the form, where every retry failed.

## Pre-release data step — WS-699 (existing test taker reuse)

Not in the code diff, so it is easy to miss. Source: `.claude/plans/existing-test-taker-prefill-and-dedupe.md` §3.4 and
`ws-699-review-fixes.md` §3.4. Run on **each environment, before deploying** the backend:

1. `php artisan legacy:check-encryption` — confirm the keys decrypt real legacy rows (the rebuild leaves unreadable rows untouched).
2. `php artisan patients:rebuild-email-index` — **dry run** (default). Read the counts and any undecryptable rows.
3. `php artisan patients:rebuild-email-index --apply` — rewrites the case-insensitive blind index (`patients.identification`, `test_invitations.email_ident`). Idempotent, safe to re-run, `--chunk=500`. Already on `develop`; not part of the WS-699 diff.
4. Deploy backend, then frontend. On QA, invite a mixed-case legacy email in a different case and confirm it reaches the confirm page.

Without it nothing crashes: a legacy mixed-case row simply misses the lookup and a **second patient row is created**.
Duplicate emails already in production are **not merged** and keep working as separate rows; the resolver and the mail
greeting use the **oldest** (`orderBy('id')`), In-Office add is blocked for a repeated email with a link to it, and history
stays split across the rows. One test taker per email per account is the intended behaviour. The planned
`patients:duplicate-emails --report` command and a DB unique index were **not** shipped.

---

## Confirm Changes / Start Test popups — WS-699 follow-up (2026-11-24)

**Branch / PRs:** frontend `feat/WS-699-confirm-changes-popup` → [TCV-Frontend#494](https://github.com/Waggoner-Diagnostics/TCV-Frontend/pull/494),
backend `feat/WS-699-invitation-expiry-days` → [TCV-Backend#376](https://github.com/Waggoner-Diagnostics/TCV-Backend/pull/376). Both
target `develop`, on top of WS-699 (#492 / #374). **Merge the backend first.** Not merged at the time of writing.

A popup with **Cancel** (stays on the page) / **Confirm** (runs the existing flow) before a test starts. Button labels on the
pages are unchanged ("Next", "Save Test Taker", "Submit & Start Test", "Start Test").

| Flow | Page | Title | Message |
|---|---|---|---|
| User panel → Start Test → Add Test Taker (**new**) | `AddPatient.js` | Confirm Changes | "You're about to start the **{test}** test. Please check that the details you entered are correct. Do you want to save them and continue?" |
| Send Test → Verify → Add details (**new**) | `AddPatient.js`, invitation route | Confirm Changes | "Please check that the details you entered are correct. Do you want to save them and continue to the test?" + smaller line "Note: This test link will expire today / tomorrow / in N days." |
| LMS launch URL → Add details | `VerifiedDefaultUser.js` | Confirm Changes | Same check text, no note. Opens **after validation, before** Turnstile + submit |
| Send Test → **existing** test taker | `InvitationConfirmPage.jsx` | Start Test | "Do you want to continue to the test?" + the expiry note |
| Start Test → **existing** test taker | `StartTestConfirmPage.jsx` | Start Test | "You're about to start the **{test}** test. Do you want to continue to the test?" |

No popup for Edit patient or Patients → Add (no test starts). The read-only pages are titled "Start Test", not "Confirm
Changes", because nothing was changed. The existing-taker popup on `StartTestConfirmPage` opens only after the eye /
occupational checks pass (`validateStart()`), so it never sits over an invalid form.

**Where the code is** (frontend): wording in `src/utils/confirmChanges.jsx` (`*Parts()` return plain strings and are what
tests assert on; `*Message()` wrap them for `showPopup` — body on top, note as `.popup-message-secondary`, test name in
`<strong>`); the guard in `src/hooks/useConfirmPopup.js`; the act-once check in `src/components/showPopup.js`.

### Expiry wording is computed in the browser, from `expires_at`

`verifyCode()` returns `expires_at` (UTC ISO 8601). `Verification.js` passes it in `navigate(..., { state: { expiresAt } })` to
both the add-details page and the confirm page; it is **not** personal data, so it is fine in history state (the patient record
still is not — only the id is in the URL). `expiryNote()` counts the **taker's own calendar days**: same local date →
"today", next local date → "tomorrow", otherwise "in N days"; missing/invalid/already-passed → no note at all.

- 📌 **Decided 2026-11-24:** an earlier version returned a server-computed `expires_in_days` (UTC-midnight day count). It said
  "1 day" with only hours left, and for a taker in New York or Tokyo the UTC date was the wrong day entirely. Removed; do not
  reintroduce a server-side day count.
- 📌 A link expiring at 01:00 tomorrow with 3 hours left says "tomorrow" (it is tomorrow's date) — accepted, which is why the
  1-day wording is "tomorrow", not "in 1 day".
- A page opened without going through verification (no `location.state`) simply shows no note.

### Double-click safety (review finding, fixed in #494)

Without a guard, a double-click on **Confirm** ran the action twice (the popup stays clickable ~200 ms while it fades): on Start
Test the 2nd `POST /patients` 422s ("Test taker already exists" over the instructions page); on the invitation flow the test was
assigned twice and the rejected 2nd attempt could bounce the taker back to verification. Double-clicking **Next** opened two popups.
Two layers:

1. `useConfirmPopup()` → `ask(content, onConfirm)` allows one open popup and runs `onConfirm` through `guard()` (a `running`
   ref); `guard(fn)` is also used for submits that skip the popup. **Use it for any new confirm-then-act button.**
2. `showPopup` buttons act once: `handled` must be a **`useRef`** — a local `let` inside `PopupComponent` is reset by the
   re-render that closing triggers (found in review; the original `dblClick` test missed it because both clicks fired before a
   re-render). This protects every popup in the app, not just WS-699.

### Verifying (manual)

Local/non-prod only. To see "today / tomorrow / in N days", change **one** invitation's `expires_at` by id (note the original
first), then verify the code again — the note is computed on each verify. Each flow: Cancel stays, Confirm proceeds, double-clicking
Next / Confirm acts once; LMS: Turnstile fires only after Confirm.

---

---

## In Progress tests: no Revoke / Resend, Resume for In-Office — WS-692 (2026-11-25)

**Branch / PRs:** `fix/WS-692-revoke-inprogress` in both repos → [TCV-Backend#382](https://github.com/Waggoner-Diagnostics/TCV-Backend/pull/382),
[TCV-Frontend#499](https://github.com/Waggoner-Diagnostics/TCV-Frontend/pull/499), both **merged to `develop`**. Plan: `.claude/plans/ws-692-revoke-inprogress.md`.

⚠️ **The Resend half of this section is superseded.** Since the follow-up (TCV-Backend #386 / TCV-Frontend #503, merged
2026-11-26), a started Send Test or Test URL test **can** be resent: the same link, its expiry moved only if it has
expired. Read [Resend on started tests](#resend-on-started-tests-same-link-expiry-moves-only-once-expired--ws-692-follow-up-2026-11-26)
for Resend; Revoke and Resume below are unchanged.

**The bug:** the account could revoke the credit of, or resend, a test the taker had already started. Revoke abandoned the
test and expired the link under a taker mid-test, so they hit a technical error popup.

📌 **History.** A first attempt (`4a0b0e28`, "Bug 692") also changed Credit History / Manage Test Credits to keep revoked
grants ("Part B"). It was merged to `develop` and **reverted** (TCV-Backend #379; also reverted on `QA` by #381). WS-692 is
Part A only, re-applied on a fresh branch. Part B is **parked** pending client discussion on `fix/692-revoke-inprogress` in
both repos: `Credits::revokeGrant()` keeping rows, the `revoked_at` column, the `credits_revoked` / `credits_already_revoked`
messages, `AddCredits.js` / `CreditPage` / `addCreditsColumns`. **None of that is on `develop`.** Because `4a0b0e28` counts
as merged, re-merging the old branch brings nothing back; reverting the revert brings Part B back.

### The rule

"In Progress" = a `patient_tests` row exists (created `inprogress` by `POST api/tests/assign` when the taker clicks
Start / Submit and Start; a both-eyes pair's second eye waits as `pending`) **and** the test can still be taken.
One function decides it: `PatientTest::groupIsInProgress($group)` — an open row (`pending`/`inprogress`) whose
`unique_test_id` has `testanswers` rows (`takeableUniqueTestIds()`).

| Test type | How the backend tells (`PatientTest::groupOrigin()`) | Revoke | Resend | Resume |
|---|---|---|---|---|
| Send Test | any row has `test_invitation_id` (or `is_email_invite`) | refused | ~~refused~~ same link, see follow-up | — (taker uses their link) |
| Plain Test URL (`generic_webhook`) | an `lms_sessions` row for any `unique_test_id` in the group | refused | ~~refused~~ same resume link, see follow-up | — (taker uses their resume link) |
| External LMS (any other provider) | same, provider ≠ `generic_webhook` | refused | refused (422 `LMS_MANAGED_TEST`) | — (relaunch from the LMS) |
| Start Test / In-Office | neither | refused | **not offered** | **offered** |

- The Test URL always mints an `LmsSession` (`verifySignature()`), so it follows the LMS rules.
- **Legacy exception (kept on purpose):** a migrated unfinished test has no `testanswers` rows, so it is not "in progress".
  Its credit can still be revoked (otherwise it is locked forever) and it never offers Resume. It is listed on the
  **Invited** tab, not the Test Taker page (`scopeOpenInvitation()`), and that tab's Revoke calls the same `revokeCredit()`.
- Cancelling an invitation **before** the taker starts works as before (refund 1 credit).
- 📌 **Signed off 2026-11-25:** if the taker never finishes and the 7-day link expires, the test stays In Progress and the
  credit is **not** returned — nobody can revoke it. Since 2026-11-26 the account **can** Resend it, which gives the same
  link 7 more days. Marking such tests Expired is a follow-up. Do not "fix" it by counting expired tests as not in
  progress without a product decision.

### Backend

| Where | Behaviour |
|---|---|
| `CreditsController::revokeCredit()` | in progress → **422** `error_code: TEST_IN_PROGRESS`, `api.test_in_progress_cannot_revoke` ("Test is in progress. You cannot revoke this credit."). Unknown id → readable **404** `api.invalid_unique_test_id` (was `firstOrFail()` → "No query results for model") |
| `PatientController::resendTestLink()` | ~~Send Test / LMS in progress → 422 `TEST_IN_PROGRESS`~~ — **replaced 2026-11-26**, see the follow-up section. Only an external LMS is still refused. **In-Office is not blocked here** — the UI offers Resume instead |
| `TestInvitationController::cancelUnregisteredInvitation()` / `resendUnregisteredInvitation()` (Invited tab) | via `ownInvitationOrRefusal()` → `startedInvitationRefusal()`: taker already started → **409** `TEST_IN_PROGRESS`; completed → 409 `TEST_COMPLETED`; already cancelled → 409 `INVITATION_CANCELLED`; unknown / not yours → **404** `api.invitation_not_found`. Cancel re-checks under `lockForUpdate()` on the invitation row. A started invitation is resent from the **Test Takers** page instead |
| `TestController::assignTest()` (taker's Start) | for an email invite, locks the invitation row and refuses a cancelled one with **410** `INVITATION_CANCELLED`. With the cancel's lock, Start vs Cancel at the same moment has exactly one winner |
| `PatientTestTransformer::actionFlags()` (Test Taker list, `GET api/patients/{id}/tests`) | per row / both-eyes pair: `origin`, `in_progress`, `can_revoke`, `can_resend`, `can_resume`, `resume_unique_test_id`, `invitation_expires_at`. LMS and takeable ids are loaded **once per list**, not per row |
| `TestExecutionService::getSessionDetails()` (`GET api/test-session/{id}`) | adds `invitation_expires_at` (Send Test only, else null) for the taker's instruction screen |
| `ApiResponse::error()` | new optional 5th argument `?string $errorCode` → top-level `error_code` ([ERROR_HANDLING](../ERROR_HANDLING.md#response-shapes)) |

Flags: `can_revoke` = not in progress && some row `inprogress` (so only the legacy case passes); `can_resend` = some open
row && no `resend_blocked_reason` (changed 2026-11-26, see the follow-up section); `can_resume` = in progress && origin `in_office`. `resume_unique_test_id` = the takeable open eye,
**`inprogress` before `pending`** — the same choice as `TestAssignmentService::findActiveTest()`. When the left eye is
submitted, the backend flips the right eye `pending → inprogress` in the same transaction
(`TestExecutionService.php:164`), so "left done, right pending" only exists mid-flight.

### Frontend

- **`PatientTestList.js`** (Test Taker page): `can_revoke === false` / `can_resend === false` render a
  `DisabledActionButton` with the reason. `=== false`, not falsy: an older API without the flags keeps the old buttons.
  `can_resume && resume_unique_test_id` → **Resume Test** in place of "Resend Test Email", which does
  `navigate('/user-panel/start-test/instruction/' + resume_unique_test_id)` — the page Start Test → pick taker → Start
  already lands on (`StartTestConfirmPage.jsx` → `checkActiveTest`). No new endpoint. On a `TEST_IN_PROGRESS` refusal the
  list reloads (`fetchPatientTests`).
  - 📌 **`src/pages/ResumeTest/ResumeTest.js` is not used for this.** That is the public `/test/resume/:token` page for the
    taker's emailed resume token: it stores an invitation session token and goes to `/test-invitation/...`. Wrong flow for
    a signed-in user.
  - 📌 **Dropped 2026-11-25:** a "Link expires <date>" line on the In Progress Send Test card (too cluttered). The backend
    still sends `invitation_expires_at` on the row; the SPA does not show it.
- **`InvitedPatientsTab.js`:** `showActionError()` — 409 / 404 / `TEST_IN_PROGRESS` shows the server message
  ("Test In Progress" title for the latter) and **reloads the list**; 5xx does not reload.
- **`InstructionPage.js`:** on the Send Test taker route only (`/test-invitation/test/...`) with `invitation_expires_at`:
  "You have until <MM/DD/YYYY hh:mm A> to finish this test using the link in your email." Not on LMS
  (`/organization/instruction/...`) or In-Office.
- **Taker's start screen:** `invitationErrors.js` maps **410 → "Invitation cancelled"** and **409 → "Test already
  completed"** — hence `assignTest()`'s 410 (trap 18). Since 2026-11-26 one 409 is told apart by `error_code`:
  `INVITATION_ALREADY_STARTED` → "Test already started" / Verify again (follow-up section).
- New: `src/components/DisabledActionButton.{js,scss}` ([FRONTEND](../FRONTEND.md#disabled-actions-with-a-reason--disabledactionbutton-ws-692-2026-11-25)),
  `src/utils/testActionAvailability.js` (`TEST_IN_PROGRESS`, the two messages, `isTestInProgressError()`).
  `patientTestSlice` / `sendTestSlice` pass `skipErrorPopup` for these calls so the page's popup is the only one.

### Tests

Backend `tests/Feature/TestInvitations/InProgressRevokeResendTest.php` (20). Frontend `PatientTestList.test.js`,
`InvitedPatientsTab.test.js`, `InstructionPage.test.js` (new), `DisabledActionButton.test.js`.
⚠️ CRA's Jest runs with `resetMocks`: a `jest.fn()` implementation set in a `jest.mock` factory is wiped before each test.
These tests use plain functions with a mutable override object (`mockThunks.revoke = …`) instead.

### Verifying (manual, QA MySQL)

1. Send Test, LMS / Test URL, In-Office: have the taker start each, then Revoke / Resend from a page loaded **before** they
   started → readable message, list refreshes, taker carries on with no popup.
2. Start and Cancel at the same moment on one invitation (two browsers). Exactly one wins. **SQLite cannot test the lock.**
3. In-Office both-eyes: finish the left eye, close the tab → Test Taker page shows Resume Test + disabled Revoke, no
   Resend → Resume opens the **right** eye. Repeat closing mid-left-eye → continues the left eye.

### Not covered (pre-existing, follow-ups)

- Resumes write **no audit event** in any flow (Resume button, Start Test re-entry, verify-code, `/test/resume`, LMS
  relaunch); only `test.started` (on create) and `test.completed` exist. Considered 2026-11-25 and left out of WS-692.
- `GET api/test-session/{id}` has no ownership check ([S-02](../SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test)). Resume only links to the caller's own
  tests (random UUIDs), so WS-692 does not widen it. If it fails, `InstructionPage` sends the user to `/user-panel` with
  no message.
- `revokeCredit()` checks and refunds without a row lock, so two simultaneous revokes could refund twice. After WS-692
  only legacy no-plates tests reach that path.
- Resend on the Invited tab is not locked against Start: at worst one extra email with the same link and code.
  (Since 2026-11-26 it is locked against a second Resend of the same invitation, not against Start.)

---

## Resend on started tests: same link, expiry moves only once expired — WS-692 follow-up (2026-11-26)

**PRs:** `fix/WS-692-resend-expired-reapply` → [TCV-Backend#386](https://github.com/Waggoner-Diagnostics/TCV-Backend/pull/386),
[TCV-Frontend#503](https://github.com/Waggoner-Diagnostics/TCV-Frontend/pull/503), **merged to `develop` 2026-11-26**.
Plan: `.claude/plans/ws-692-resend-link-state-rules.md`.

📌 **History.** A first version ("Resend only once the link has expired", #384 / #501) was merged and **reverted**
(#385 / #502). #386 / #503 re-apply it and replace the rule with the one below.

### The rule (signed off 2026-11-26)

| Test state | Resend | Expiry |
|---|---|---|
| Not started (Invited tab), link active or expired | enabled | **7 days from the resend**, every time |
| Started Send Test, link still valid | enabled, **same** token + code | **unchanged** (sent 2 Nov, resent 4 Nov → still 9 Nov) |
| Started Send Test, link expired | enabled, same token + code | **7 days from the resend**; any older resume link is expired (`expireResumeLinks()`) so the invitation is the one link |
| Started plain Test URL, resume link valid | enabled, the **same** resume token | unchanged |
| Started plain Test URL, resume link expired | enabled, the same resume token | 7 days from the resend |
| Started plain Test URL, no resume link ("Send Test Email to Test Takers" off) | enabled | a **first** link, 7 days from the resend |
| Started via external LMS | **disabled** | — (relaunch from the LMS) |
| Started In-Office | not offered | — (Resume Test instead, unchanged) |
| Test taker has no email | disabled | — |
| Within 60 s of the last send | disabled | — |

- **Test URL expiry means the resume token's 7 days**, not the 2-hour `LmsSession`. A Test URL test with no
  emailed link counts as expired once every `LmsSession` has expired (`groupLinkExpired()`).
- **Both-eyes:** one link for the pair. `groupLink()` takes the longest-lasting resume token across both eyes,
  finished eyes included, and `/test/resume` opens the eye still open (see Resume flow).
- **Emails:** the Test URL email states the days actually left and the real date (2 days into a 7-day link: "5 days",
  original date). A renewed or first link says 7 days. The Send Test email is mailed with the invitation's real
  `expires_at` (unchanged or renewed).
- **No expiry in the app UI.** The success popup says whether the link was renewed; the date is in the API response,
  the audit log and the email only. Help documents will explain the rule.
- Test takers have **no** self-service "email me a resume link". Only the account (or a super admin impersonating it)
  can resend.

### Backend

| Where | Behaviour |
|---|---|
| `PatientController::resendTestLink()` | Lock `resend-test-link:{parent_test_id ?? unique_test_id}` (60 s; held → **409 `RESEND_IN_FLIGHT`**). Then: external LMS started → **422 `LMS_MANAGED_TEST`** (`api.test_in_progress_cannot_resend_lms`); within the hold → **409 `RESEND_COOLDOWN`** (`api.test_link_resend_cooldown`); Send Test started → `renewInvitation($invitation, $actor, $extend = $invitation->isExpired())`; Test URL started → `resendResumeLink()` for the open eye; anything else (In-Office, not started) → `generateAndSendTestLink()` as before. No email → 422 `api.patient_no_email` |
| its response | message `api.test_link_renewed` when extended, else `api.test_link_resent`; data `resend_count`, `last_resent_at`, `extended`, `link_expires_at` |
| its audit (`test.link_resent`) | adds `Reason` ("Link still valid, expiry unchanged" / "Link expired, renewed for 7 days" / "First link issued for 7 days") and `Link Expires` |
| `TestInvitationController::resendUnregisteredInvitation()` (Invited tab) | Lock `resend-invitation:{id}` (held → 409 `RESEND_IN_FLIGHT`); the row is re-read **inside** the lock, then the 60 s hold on `email_sent_at` (409 `RESEND_COOLDOWN`), then `renewInvitation()` (always extends) |
| `App\Services\TestLinkRenewalService` (new) | `renewInvitation()` — keeps token + code, mails first, saves after; `resendResumeLink()` / `issueResumeLink()` — one link per pair under lock `resume-link:{parent_test_id ?? unique_test_id}` (`block(10)`); `expireResumeLinks()` |
| `TestResumeController` | `sendResumeEmail()` → `issueResumeLink()` (reuses the link, no longer expires others); `resume()` opens the pair's open eye |
| `PatientTest` | `RESEND_COOLDOWN_SECONDS = 60`; `ERROR_LMS_MANAGED`, `ERROR_RESEND_IN_FLIGHT`, `ERROR_RESEND_COOLDOWN`; `linkInfo()` (LMS + resume tokens, once per list), `groupLink()`, `groupLinkExpired()`, `groupResendAvailableAt()` (latest `last_resent_at` + 60 s), `groupIsExternalLms()` |
| `PatientTestTransformer::actionFlags()` | adds `link_expired`, `resend_blocked_reason` (`in_progress` for In-Office · `external_lms` · `no_email` · `cooldown`), `resend_available_at`, `resend_available_in` (seconds, ≥ 1) |
| `TestController::assignTest()` | 409 `INVITATION_ALREADY_STARTED` for a second assignment on one invitation |
| `TestExecutionService::markTestInvitationAsUsed()` / `TestInvitation::isFinished()` | invitation used only when both eyes are done (see Redeem flow) |

### Frontend

- **`PatientTestList.js`:**
  - Disabled Resend shows `resendBlockedMessage(pt)` (`testActionAvailability.js`): the LMS, no-email or
    cooldown message, else the old "Test is in progress…".
  - Success popup: `result.data.extended` → "The test taker's link had expired. It has been renewed and resent to their
    email."; else "Test link has been resent to the test taker's email." The list always reloads quietly afterwards.
  - `RESEND_COOLDOWN`, `RESEND_IN_FLIGHT`, `LMS_MANAGED_TEST` and `TEST_IN_PROGRESS` count as refusals
    (`isResendRefusedError()`): title "Cannot Resend Test", quiet reload.
  - **Hold timer:** a `useEffect` on `patientTests` waits the smallest `resend_available_in` (seconds from the server,
    so a wrong browser clock does not matter; falls back to `resend_available_at`) + 1 s, then reloads quietly. It
    re-arms after every load.
  - **Link Active / Link Expired badge** on In Progress rows, now for Send Test **and** plain Test URL (from
    `link_expired`); not for external LMS.
- **`patientTestSlice` `fetchPatientTests({ patientId, silent: true })`** passes `{ skipErrorPopup, skipAuthRedirect }`:
  a background reload never pops the global error or logs an idle user out.
- **`invitationErrors.js`:** `errorCodeOf(err)`; `INVITATION_ALREADY_STARTED` → title "Test already started", "You have
  already started this test. Enter your verification code again to continue where you left off.", action Verify again.
- **`InstructionPage.js`:** keeps the loader up for a completed test until the redirect to its result lands (no flash of
  the instructions).

### Tests

Backend `InProgressRevokeResendTest.php` (53 tests, was 20): the 2 Nov → 9 Nov → 19 Nov example, the cooldown on both
pages and its seconds left, the in-flight lock on the Invited tab, Test URL keeping one link and reviving it, start email
and Resend in both orders leaving one working link, days-left in the email, both-eyes link opening the right eye.
`ResendTestLinkStatusTest` travels `RESEND_COOLDOWN_SECONDS + 1` between resends. Frontend: `PatientTestList.test.js`
(timer with a skewed clock, re-arm after reload, popups, badge), `patientTestSlice.test.js` (new: silent flags),
`invitationErrors.test.js`, `InstructionPage.test.js`.

### Support / QA notes

- "My test taker's link still says the old date after Resend" — expected while the link is still valid.
- "Resend is greyed out" — check the tooltip: LMS-launched, no email on file, or sent within the last minute (it comes
  back by itself).
- The resend that issues a **first** Test URL link shows the "had expired… renewed" popup, though nothing expired.
  Known wording quirk, accepted.
- To test expiry on QA: change **one** row's `test_invitations.expires_at` or `test_resume_tokens.expires_at` by id,
  then Resend. Wait 60 s between resends. **The locks are cache locks** on the default store (`CACHE_STORE`, default `database`), so
  they hold across PHP workers. With the `array` store (the test suite) they hold within one process only.

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
14. ⚠️ **`SendTestInvitationEmailsJob::sendBatch()` selects by `whereIn('id', …)` with no
    `where('user_id', …)`.** Pre-existing and harmless today — both callers group by sender before
    constructing the job (`SweepPendingInvitationsJob::sweep()` does it explicitly) — but `ws-401`
    raised what a mismatch would cost. The template *type*, the organization name and the patient
    lookup now all derive from `$this->userId`, while the recipient address derives from the row: an id
    list that ever mixed senders would mail one organization's branding, sign-off and patient name to
    another's invitee, not merely charge the wrong account. A defensive scope on the query is cheap.
    **Not part of `ws-401`** — raised in its round-2 review (2026-09-17) and left for its own ticket.
15. ☠️ **A confirm popup's button can be clicked twice.** `showPopup` keeps its buttons clickable for the ~200 ms fade-out.
    Any "confirm, then create/assign" action must go through `useConfirmPopup().ask()` (WS-699 follow-up, see "Confirm
    Changes / Start Test popups"), or Confirm runs it twice: duplicate assign, a 422 "Test taker already exists" over the
    instructions page, or a bounce back to verification with the test already assigned. `showPopup`'s own act-once flag is a
    `useRef` — as a local `let` it resets on the closing re-render.
16. ⚠️ **Do not compute "days left" on the server in UTC.** `expires_at` is returned raw and the SPA counts the taker's own
    calendar days. A UTC-midnight count said "1 day" with hours left and the wrong day for takers east/west of UTC.
17. ☠️ **"In progress" means started *and* takeable — one function, `PatientTest::groupIsInProgress()`** (WS-692). The
    Revoke / Resend refusals, the list flags and Resume all go through it. A new action on a started test should too.
    Checking `status === 'inprogress'` alone locks migrated no-plates tests' credit forever.
18. ⚠️ **The taker's SPA reads status codes, not `error_code`.** `invitationErrors.js` → `invitationDeadEnd()` shows 409 as
    "Test already completed" and 410 as "Invitation cancelled". A taker-facing refusal must use the code whose wording
    fits (WS-692 review: `assignTest()`'s cancelled-invitation refusal was 409 and showed "already completed"; now 410).
    The one exception (2026-11-26): `INVITATION_ALREADY_STARTED` is read by `error_code` first. A new 409 the taker can
    see needs its own `error_code` arm there, or it reads "Test already completed".
19. **Part B of Bug 692 is not on `develop`.** Credit History still deletes / counter-entries revoked grants as before.
    Do not cherry-pick from `fix/692-revoke-inprogress` without the client decision.
20. ☠️ **Never mint a new resume link on a send** (WS-692 follow-up). The start email and Resend both go through
    `TestLinkRenewalService::resendResumeLink()` under one lock per pair, so whichever runs second mails the first one's
    link. A path that creates a token and expires the others kills a link the taker was just emailed.
21. ⚠️ **Resend on a started test must not move a valid link's expiry.** `renewInvitation(..., $extend)` and
    `mailPairLink()` extend only when the link has already expired. The Invited tab (not started) always extends.
22. ⚠️ **The resend hold is read from the server's seconds, not its timestamp.** The SPA waits
    `resend_available_in`; comparing `resend_available_at` with the browser clock stuck the button on a skewed clock.
    The hold itself is `PatientTest::RESEND_COOLDOWN_SECONDS`, on both resend endpoints.
23. ⚠️ **A both-eyes invitation stays open between eyes.** Check `TestInvitation::isFinished()`, not `is_used`, for
    "nothing left to do": older rows have `is_used = true` with the right eye still open.

---

## Editable account/system emails & the Email Templates dropdown (2026-10-12, merged: backend PR #315 / frontend PR #445)

**What the platform sends, and where each lives** (SPA: Settings > Email Templates, dropdown in this order):

| # | Email | Stored in | Key | Link placeholder (required) |
|---|---|---|---|---|
| 1 | Test invitation (Test Taker) / (Integrated Partner) | `test_email_templates` | `test_link`, `org_test_link` | `{{verification_link}}` |
| 2 | Test Link Resent (`TestService::generateAndSendTestLink`) | `email_template` | `test_link_resent` | `{{test_url}}` |
| 3 | Start or Resume Test Link (`TestResumeController::dispatchResumeEmail`) | `email_template` | `test_resume_link` | `{{resume_link}}` (+ `{{test_name}}`, `{{expires_at}}`, `{{expiry_days}}`) |
| 4 | Verify Email Address (`AuthController::sendVerificationEmailForUser`) | `email_template` | `email_verification` | `{{verification_link}}` |
| 5 | Set Password for a New Account (`ResetPasswordNotification`, `isReset=false`) | `email_template` | `set_password` | `{{set_password_url}}` (+ `{{first_name}}`, `{{last_name}}`) |
| 6 | Password Reset (`ResetPasswordNotification`, `isReset=true`) | `email_template` | `password_reset` | `{{reset_url}}` (+ names) |
| 7 | Integrated Partner Test URL (`OrganizationTestUrlNotification`, fired by `SendAfterPasswordReset`) | `email_template` | `organization_test_url` | `{{test_url}}` (+ `{{organization_name}}`) |

There is **no "Welcome" template** anywhere in the backend; "Welcome" only survives in old subject strings.

**API (`routes/api.php`, `auth:sanctum` group, `TestEmailTemplateController`):**
- `GET api/test-email-templates`: original keys `test_link` / `org_test_link` unchanged, plus `system_templates` (map name -> `{id, subject, body, type, last_modified, placeholders}`) and `available` (ordered `[{key,label,source}]`, only templates whose row exists). Account-email order and labels live in the controller constant `SYSTEM_TEMPLATES` (`TestEmailTemplateController.php:35-42`); the two test-invitation labels are hard-coded in `availableTemplates()` (`:97`, `:100`).
- `PUT api/test-email-templates/system/{id}` (`updateSystem`): registered **before** `/{id}`. **Super Admin only** (403 otherwise, `usertype === User::SUPER_ADMIN`): the group is `auth:sanctum` only, so without the check any customer could rewrite the password-reset email. Edits `subject` and `body` only (header/footer are shared branding, never exposed). Validation reuses `validateRequiredPlaceholders` against `EmailTemplatePlaceholders::catalogue($name)` (required / unknown / padded / markup-broken), re-anchors bare link tokens using `SYSTEM_LINK_LABELS`, and logs `settings.email_template_updated` with before/after.
- **Updated 2026-10-13, merged PR #315:** `PUT /{id}` (`update`) is now **Super Admin only** (403 otherwise): it edits the global admin-default `test_email_templates` rows. Customers/orgs edit their own copies via `user-email-template` (`UserEmailTemplate`); the only SPA caller of `PUT /{id}` is the admin Settings slice. `GET index` returns an empty `system_templates` and omits the account-email entries from `available` for non-Super-Admins (`test_link`/`org_test_link` are still returned to everyone). The old text "`GET index` and `PUT /{id}` remain unscoped" is no longer true.
- ☠️ **A disabled (`status != 'enable'`) row behaves differently per email:** `test_link_resent` / `test_resume_link` silently fall back to `SystemEmailDefaults` (`SystemEmailTemplateService.php:28-42`); `organization_test_url` falls back to the hard-coded `MailMessage` (`render(..., false)` returns null, `OrganizationTestUrlNotification.php:43-46`); `password_reset` / `set_password` / `email_verification` **throw** ("…template not found", `ResetPasswordNotification.php:47-54`, `AuthController.php:845-852`), so **no mail is sent**. `index` / `updateSystem` ignore `status`, so a disabled row is still listed and editable but the edit never sends.
- `updateSystem` runs with no DB transaction and no cache clear (unlike `update`, `:222-243`). Its audit "Template Name" is the dropdown label `SYSTEM_TEMPLATES[$name]` (`:384`). `subject` is `required|string|max:250` on both PUTs (`:414`).
- ⚠️ Stale code comment at `TestEmailTemplateController.php:316` still says "index()/update() above predate this and are unscoped" — both are now scoped (`:120`, `:178`). Trust the code.
- ⚠️ `render()` HTML-escapes values (a URL with `&` becomes `&amp;` in `href` — valid). The pre-existing `ResetPasswordNotification` / `AuthController` paths do **not** escape `{{first_name}}` / `{{last_name}}` (`ResetPasswordNotification.php:60-68`); now that Super Admin can place those tokens anywhere, unescaped user names reach the HTML.
- The `{id}` for `/system/` is an `email_template.id`, a different id space from `test_email_templates.id`.
- `EmailTemplatePlaceholders::catalogue()` gained the six name-keyed arms above, so `GET api/test-email-templates/placeholders/{type}` also works for them.

**Rows 2, 3 and 7 used to be hard-coded; now data with a fallback.**
- `App\Support\SystemEmailDefaults::all()` holds each default subject/body: the exact HTML the mailers used to emit, with the dynamic values turned into placeholders. It is the single source for the seeding migration `2026_10_12_000002_seed_editable_system_email_templates` (idempotent: skips a name that already exists, header/footer `''`; `down()` removes only rows still holding the seeded body), for `EmailTemplateSeeder`, and for the fallback.
- `App\Services\SystemEmailTemplateService::render($name, $values, $fallbackToDefaults = true)` reads the enabled `email_template` row (header + body + footer), replaces `{{name}}` with the **HTML-escaped** value in the body (the subject is plain text, unescaped), runs `EmailContent::linkify` **then `EmailContent::styleAnchors`**, and returns `['subject','content']`. `styleAnchors` (added on `fix/protected-routes-03nov`, 2026-11-03) gives every `<a href>` with no authored `color:` the blue underline `color:#007bff;text-decoration:underline`, because Outlook/YOPmail render an unstyled anchor as plain text (Resend Test and the Start / Resume fallback link). Buttons and links with their own colour are left alone. ⚠️ Rendered output is therefore no longer byte-identical to the old mail for an unstyled link; `SystemEmailTemplateMailTest` was adjusted, and 4 of its cases fail with or without the branch's changes.
- Resume and Resent mails: with the seeded row the content equals the old output except the Resent greeting, now `Dear Test Taker,` (was `Dear Patient,`, terminology commit `bfd55029`; the test's "legacy" copy already carries the new wording); with the row missing or disabled the service uses `SystemEmailDefaults`, same output. `emails/testLink.blade.php` now just wraps a `$content` variable in the same document shell.
- Integrated Partner Test URL: `OrganizationTestUrlNotification` renders the row through `emails.dynamic-template` (like reset/set-password). **Visual change:** it previously used Laravel's default markdown mail layout (greeting, button, "Regards" footer, trouble-clicking subcopy). The words and button are reproduced apart from the terminology rename (subject `Your Integrated Partner Test URL`, body "Your integrated partner has been created successfully." — was `Organization`), but the layout shell is now the `dynamic-template` container. With the row missing **or disabled** the notification returns the hard-coded `MailMessage`, which carries the same renamed wording.
- Editing: bodies pass through Quill in `linkTokens` mode ([FRONTEND](../FRONTEND.md), section "Settings: Super Admin Profile removal & Email Templates dropdown"). The resume button's original inline style (`padding:12px 28px; font-weight:600`) is replaced by the standard editor button style the first time the body is saved from the SPA.
- Tests: `tests/Feature/Mail/SystemEmailTemplateMailTest.php`, `tests/Feature/Settings/SystemEmailTemplateTest.php`.

🔒 **Public `POST api/test/resume` is unchanged by PR #315.** Only `TestResumeController::dispatchResumeEmail()`
(`:285`, called by `sendResumeEmail`) changed: subject/body now come from
`SystemEmailTemplateService::render('test_resume_link', …)` (`:293`) — values `e()`-escaped in the body, not in the
plain-text subject. `resume()` (`:157`) is untouched. A disabled or missing row falls back to `SystemEmailDefaults`,
so **disabling the template does not stop resume emails** (same for `test_link_resent`, `TestService.php:243`).

**Fallback link on its own line (2026-11-04, `fix/protected-routes-03nov`, backend `c0986cde`).** In `password_reset`, `set_password` and `organization_test_url` the "copy and paste ... into your browser:" sentence now ends in `<br>` and the link follows inside the same paragraph, the pattern `test_resume_link` already used (the reset/set-password link used to sit in a separate `<p>`; the org URL sat on the same line as the sentence). Expiry wording ("expires in 60 minutes / 48 hours") is unchanged. Three edits in lockstep: `EmailTemplateSeeder` (reset, set-password), `SystemEmailDefaults::ORGANIZATION_TEST_URL_BODY`, and the match-on-fragment migration `2026_11_04_000001_put_email_fallback_link_on_new_line` (replaces only the exact fragment, so rows a Super Admin edited are otherwise kept; `down()` restores it). `ORGANIZATION_TEST_URL_BODY_V1` and migration `2026_10_28_000003`'s `seededBody()` still hold the older markup on purpose, as match values for earlier migrations.

☠️ **Changing a default body in `SystemEmailDefaults` changes only fresh databases and the fallback.** Existing rows keep their stored text, like every `email_template` row ([AUTH_CONTEXT](AUTH_CONTEXT.md) trap 8).
