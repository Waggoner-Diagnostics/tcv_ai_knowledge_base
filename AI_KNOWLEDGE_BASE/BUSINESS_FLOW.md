# Business Flow

End-to-end: **account → credits → assignment → test → result → report**. Each step names the code that
owns it, so you can jump straight there.

---

## 1. Account

```
POST api/register  (PUBLIC)  → AuthController::register()
   ├─ UserRequest validates  usertype ∈ {1,2,4}, account_status ∈ {active,inactive,suspended}
   ├─ User::create(... email_verified: 'no')
   ├─ Password::broker()->createToken()  → users.password_setup_token
   └─ usertype === CUSTOMER ? assign the 'Adult Diagnostic' test (TestConstants::DEFAULT_TEST_TITLE)
```

Then one of two paths sets the password:
- **self-registered** — verify email (`POST api/verify-email-token`), then log in;
- **admin-created** — a setup link (`type=setup`, 48-hour broker) → `setOrResetPassword()` sets the
  password, marks the email verified, and fires `UserPasswordSet`.

`UserPasswordSet` → `SendAfterPasswordReset` → **if the user owns an Organization**, mails the
`OrganizationTestUrlNotification` carrying the signed Test URL.

☠️ `POST api/register` is public and accepts `usertype: 1`
([S-01](SECURITY.md#s-01--public-registration-accepts-usertype--1)).

## 2. Credits

Two sources, one ledger ([CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md)):

```
admin grant   → POST api/credits           → Credits row, source = 0 (MANUAL)
purchase      → POST api/payment/initialize → Stripe PaymentIntent
                POST api/payment/confirm    → Transaction + Credits row, source = 1 (PURCHASE)
```

Discount codes are validated during `initialize`
([CONTEXT/DISCOUNT_CONTEXT.md](CONTEXT/DISCOUNT_CONTEXT.md)).

☠️ Credits are granted **in the `confirm` request**, not by a webhook. A browser that dies between the
charge and `confirm` leaves the customer paid and un-credited, with nothing to reconcile it
([CONTEXT/BILLING_CONTEXT.md](CONTEXT/BILLING_CONTEXT.md)).

Balance = `SUM(credits.credits) − SUM(credit_consume.credits_used)`, or the **string** `'Unlimited'`.

## 3. Assignment — three doors

| Door | Endpoint | Charges a credit | Produces |
|---|---|---|---|
| **Clinician, in the room** | `POST api/tests/assign` | ✅ at assign time | `PatientTest` + all `TestAnswer` rows |
| **Email invitation** | `POST api/test-invitations/send` | ✅ at **send** time, per address | `TestInvitation` (7-day token + code) |
| **Organisation / LMS** | `POST api/organization/verify-signature` → patient form → assign | per the org's arrangement | `LmsSession` → `PatientTest` |

`assignTest()` **skips** the deduction when the request carries a `test_invitation_id` — the invitation
already paid. That one condition is the whole double-charge guard.

For a **monocular** test, `TestAssignmentService` creates **two** `PatientTest` rows sharing a
`parent_test_id` (`OS` and `OD`); the `OD` row starts `pending` and is promoted when `OS` completes.

## 4. Taking the test

```
patient opens the link
   ├─ invitation: POST api/test-invitation/verify-code   → 2-hour TestSession
   ├─ resume:     POST api/test/resume                   → 2-hour TestSession
   └─ org:        POST api/organization/verify-signature → LmsSession (120–180 min)

GET  api/test-session/{uuid}                              → sections + progress
GET  api/test-session/{uuid}/section/{id}/plates          → unanswered plates (+1 pre-signed URL)
GET  api/test-session/{uuid}/plate/{answerId}/url         → a pre-signed URL per plate (900 s)
POST api/tests/perform  { test_answer_id, answer }        → repeat until the section ends
```

Per answer, `TestExecutionService::submitAnswer()` scores it, may **terminate the section early** (last
*N* non-demo plates all wrong), and on section completion may **skip later sections** per
`test_conditions`. Full detail in
[CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md).

## 5. Completion

`finalizeTestIfCompleted()` — inside a locked transaction, once no `answered = 0` rows remain:

```
status = completed · ip_address recorded
result_json = ColorVisionDiagnosisService output    ← written ONCE, never recomputed
TestInvitation.is_used = true
paired eye (if any) promoted pending → inprogress
────── transaction commits ──────
event(TestCompleted)
```

## 6. Reporting back

```
TestCompleted
  → HandleLmsNotificationOnCompletion         (auto-discovered listener)
      → LmsDeliveryService::enqueueCompletion()  → lms_delivery_queue row (idempotent)
          → ProcessLmsDeliveryJob                 → provider (webhook or Cornerstone xAPI)
              retries 30 s → 2 m → 10 m → 1 h → 6 h, then dead_letter
```

Non-LMS tests are a no-op — the listener finds no `LmsSession` and returns.

☠️ **Nothing runs the queue** in either compose file, so enqueued deliveries wait
([QUEUES.md](QUEUES.md)).

## 7. Results out

| Audience | Path |
|---|---|
| Patient / clinician on screen | `GET api/test-result/{uuid}` |
| PDF | `GET api/test-result/{uuid}/download-pdf` · `POST api/tests/result-pdf` (dompdf, reads `result_json`) |
| Admin reports | `GET api/reports/user-tests`, `/discount-codes`, `/list-patients-having-tests` + Excel exports |
| LMS | the delivery above |

## Reversal: revoking a credit

```
POST api/patient-tests/{identifier}/revoke-credit
   ├─ requires at least one test in the group to be 'inprogress'
   ├─ grants 1 credit back (source = 2, REVOKED) to the test OWNER
   ├─ every test in the group → 'abandoned'
   └─ the invitation's expires_at = now()   (the patient's link stops working)
```

Always **one** credit, even for a two-row monocular pair.

☠️ Cancelling an *unregistered invitation* refunds `auth()->user()` instead of the invitation's owner —
[CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md).

---

## Money and credits at a glance

```
Stripe charge ──▶ transactions + transaction_details
                        │ ref_id
                        ▼
                    credits (source=1)  ──┐
admin grant     ──▶ credits (source=0)  ──┼──▶ SUM ──┐
revocation      ──▶ credits (source=2)  ──┘          ├──▶ available balance
                                                      │
invitation send ──▶ credit_consume ('test_invitation')┤
direct assign   ──▶ credit_consume ('test_completion')┘  (subtracted)
```

☠️ The two spend paths use **different `event_type` values** for the same economic event —
`TestInvitationController` records `EVENT_TEST_INVITATION`, while `TestAssignmentService` records
`EVENT_TEST_COMPLETION` even though it charges at *assign* time, not completion. Any report that filters
`credit_consume` by `event_type` will mis-attribute one of them.

There is no balance column and no ledger-closing process. Every read recomputes.
