# Context: Email Invitations & Resume Links

> Load this **instead of** reading the invitation subsystem. ~1.3k tokens. This is how a patient with no
> account takes a test.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/TestInvitationController.php` (559 lines) | ⭐ Send, verify, resend, cancel, list unregistered |
| `app/Http/Controllers/TestResumeController.php` (190 lines) | ⭐ Resume-link issue + redemption |
| `app/Models/TestInvitation.php` · `TestSession.php` · `TestResumeToken.php` | The three token records |
| `app/Models/TestEmailTemplates.php` · `UserEmailTemplate.php` | Per-user email copy |

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
POST api/test-invitations/send   ← PUBLIC. { user_id, test_id, emails[≤500], unique_test_id? }
  ├─ Credits::getAvailableCredits(user_id)      → 402 when short  (guarded for 'Unlimited')
  ├─ per email: token = Str::random(32), code = upper(random(6)), expires_at = now + 7d
  ├─ mail the link
  └─ unless is_resend:  CreditConsume::consume(user, n, 'test_invitation', [ids])
```

☠️ **This route is unauthenticated and spends the credits of a body-supplied `user_id`, up to 500
addresses per call, after `set_time_limit(0)`.** It is the highest-impact defect in the codebase —
[S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time).

**Resends do not re-charge.** `is_resend = true` skips the `CreditConsume::consume()` call, and
`resendUnregisteredInvitation()` issues a *fresh* token + code with a *fresh* 7-day window.

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

1. **`POST api/test-invitations/send` is public and spends someone else's credits.** [S-13](../SECURITY.md#s-13--public-test-invitationssend-spends-any-users-credits-500-emails-at-a-time).
2. **Cancel refunds the caller, not the owner** (above).
3. **Expiry is compared as a date in some paths and a datetime in others** — `verifyCode()`/`checkTokenStatus()`
   comment their check as date-based ("expires_at < today") while `TestResumeToken::isExpired()` is a
   true datetime comparison. An invitation can therefore stay valid for part of its expiry day.
4. **`is_used` flips at completion, not redemption.** An abandoned attempt does not burn the invitation.
5. **Invitation and resume tokens are stored in plaintext.** A DB read yields working credentials for
   both flows.
6. **`resend_count` exists on both `test_invitations` and `patient_tests`** and they are incremented in
   different places. Do not treat either as the total.
7. **Email copy comes from three places** — `test_email_templates`, `user_email_templates`, and inline
   heredocs (resume). Changing "the invitation email" may mean changing any of the three.
