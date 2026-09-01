# Context: Email Invitations & Resume Links

> Load this **instead of** reading the invitation subsystem. ~1.7k tokens. This is how a patient with no
> account takes a test.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/TestInvitationController.php` (559 lines) | ⭐ Send, verify, resend, cancel, list unregistered |
| `app/Http/Controllers/TestResumeController.php` (190 lines) | ⭐ Resume-link issue + redemption |
| `app/Models/TestInvitation.php` · `TestSession.php` · `TestResumeToken.php` | The three token records |
| `app/Models/TestEmailTemplates.php` · `UserEmailTemplate.php` | Per-user email copy |
| `app/Services/EmailTemplateService.php` | Picks the sender's template, or the admin default, or a hard-coded fallback |
| `app/Support/EmailContent.php` | ⭐ Makes bare URLs and `{{placeholder}}`s clickable (`ws-373`) |

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
POST api/test-invitations/send   ← auth:sanctum. { test_id, emails[≤500], unique_test_id? }
  ├─ owner = $request->user()                   ← NOT from the body
  ├─ Credits::getAvailableCredits(owner->id)    → 402 when short  (guarded for 'Unlimited')
  ├─ per email: token = Str::random(32), code = upper(random(6)), expires_at = now + 7d
  ├─ mail the link
  └─ CreditConsume::consume(user, n, 'test_invitation', [ids])
```

**`user_id` is no longer accepted in the body.** It was dropped from the validation rules when the route
moved into the `auth:sanctum` group on 2026-08-26 — the owner is always `$request->user()`, super-admin
included. That closed
[S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time).
`set_time_limit(0)` is still called, so a 500-address call still has no execution-time ceiling, and the
route is still unthrottled.

**Short balance truncates rather than rejects.** When `credit < count(emails)` the call sends only the
first `credit` addresses and returns 200 — it does not 402. Only a balance below 1 is a 402.

**Resend is a separate endpoint and does not re-charge.** The `is_resend` body flag on `send` is **gone**
(removed with the S-13 fix, so a resend can no longer be used to skip the credit check on `send`).
`POST api/test-invitations/{id}/resend` → `resendUnregisteredInvitation()` issues a *fresh* token + code
with a *fresh* 7-day window, consumes no credit, and is scoped to `user_id = auth()->id()`.

### How the invitation body is assembled

`sendInvitationEmail()` is three passes over one string, and the order matters:

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

---

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

1. ~~**`POST api/test-invitations/send` is public and spends someone else's credits.**~~ ✅ **Fixed 2026-08-26** — route is `auth:sanctum` and the body `user_id` is gone ([S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time)). Still unthrottled, and still `set_time_limit(0)` for ≤500 addresses.
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
   "the invitation email" may mean changing any of the four.
8. **The Quill editor drops what it does not whitelist.** `components/richTextEditor/RichTextEditor.js`
   passes an explicit `formats` list to `ReactQuill`, so inline `style` attributes — and an `<a>` a user
   pastes in — can vanish on save. That is why the send path restyles and linkifies rather than trusting
   the stored markup, and why `ws-373` also repairs the stored rows
   (`2026_08_31_000001_anchor_bare_link_placeholders_in_email_templates`, chunked at 100 rows because
   `user_email_templates` holds one `longText` body per user).
9. **That repair migration skips rows it did not expect.** It rewrites a row only when
   `EmailContent::anchorPlaceholders()` actually changes it, so an already-anchored or customised
   template is left alone — and it bumps `updated_at`, which the editor surfaces as "last modified".
