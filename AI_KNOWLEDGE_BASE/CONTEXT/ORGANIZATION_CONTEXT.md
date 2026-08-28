# Context: Organisations & the Test URL

> Load this **instead of** reading `OrganizationController` (876 lines — the largest file in the repo).
> ~1.4k tokens.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/OrganizationController.php` (876 lines) | CRUD, logo upload, **`verifySignature()`**, patient-form config, privileges, redirect URL |
| `app/Http/Controllers/OrganizationPatientController.php` (305 lines) | `storeDefaultPatient()`, `storeProlificPatient()` |
| `app/Models/Organization.php` | ⭐ `generateTestUrl()` — the signed launch URL |
| `app/Models/OrganizationConfig.php` · `OrganizationSettingsOption.php` · `OrganizationType.php` · `Compliance.php` · `Privilege.php` · `AllowedTest.php` | Configuration surface |
| `app/Policies/OrgPolicy.php` | Super-admin + ability gated |
| `app/Notifications/OrganizationTestUrlNotification.php` | Mails the URL after first password set |

## Tables
`organizations` · `organization_configs` · `organization_settings_options` · `organization_types` ·
`compliances` · `privileges` · `allowed_tests` · `organization_patient_sessions` (legacy) · `prolific_ids`

---

## What an organisation is

A `User` with `usertype = 4` (`ORGANIZATION`) **plus** an `Organization` row (`organizations.user_id`).
Both must exist; code that checks only one will misbehave. Deleting the user does not delete the org row.

### The display/behaviour flags on `organizations`
`show_tcv_branding` · `anonymize_patient` · `show_occupational_questions` · `show_gender` · `show_zip` ·
`show_patient_id` · `send_test_email_to_patients` · `run_test_on_subdomain` · `authorized_redirect` ·
`logo_uploaded`

These are read by `getPatientForm()` and drive what the SPA renders on the public
`/organization/patients/add` page. **Adding a field to the patient form is a two-repo change**: a column
+ `getPatientForm()` here, and the form renderer in `TCV-Frontend` — see
[FULLSTACK_MAP.md](../FULLSTACK_MAP.md).

`privileges` and `allowed_tests` are many-to-many; `getOrganizationPrivileges()` and
`getDefaultTests()` expose them to the session.

---

## The Test URL — the org's entire front door

`Organization::generateTestUrl()`:

```php
$signingKey = $providerConfig?->signing_key ?? config('app.key');
$signature  = hash_hmac('sha256', (string) $this->id, $signingKey);

return config('app.frontend_app_url') . '/organization/patients/add?' . http_build_query([
    'org_id'    => $this->id,
    'signature' => $signature,
]);
```

It is stored on `organizations.test_url` and mailed by `OrganizationTestUrlNotification`, which is
triggered by `UserPasswordSet` → `SendAfterPasswordReset` **only when the user owns an Organization**
([AUTH_CONTEXT](AUTH_CONTEXT.md)).

The SPA then posts `{org_id, signature}` to the **public** `POST api/organization/verify-signature`,
which mints an `LmsSession` ([LMS_CONTEXT](LMS_CONTEXT.md)).

### ☠️ The signature is a permanent bearer credential
It covers `org_id` **and nothing else** — no nonce, no timestamp, no expiry. Whoever holds the URL can
mint unlimited sessions forever. Rotating the provider config's `signing_key`
(`POST api/admin/lms/provider-configs/{id}/rotate-key`) is the **only** revocation, and it invalidates
the stored `test_url` at the same time — so rotation must be followed by regenerating and re-mailing the
URL. See [S-05](../SECURITY.md#s-05--organisation-launch-signatures-are-static-permanent-bearer-credentials).

### ☠️ The `APP_KEY` fallback never closes
`generateTestUrl()` and `verifySignature()` both fall back to `config('app.key')` when no active
provider config exists — and `verifySignature()` *additionally* accepts an `APP_KEY`-derived signature
even when a per-org config **does** exist, calling it "retroactive healing". Nothing records that the
fallback was used, so the branch stays live on every request. A leaked `APP_KEY` forges a launch URL for
**every** org.

---

## Patient intake

Two entry points, both `FlexibleAuthMiddleware` + `lms.status:launched,identity_resolved`:

| Endpoint | For |
|---|---|
| `POST api/organization/patient/default` | Normal orgs — fields driven by the display flags above |
| `POST api/organization/patient/prolific` | Prolific research panel — identity is a `prolific_id` |

Both advance the LMS session's status. Remember the status gate only bites for tier-3 sessions.

---

## ☠️ Traps

1. **`verifySignature()` is public and its signature never expires** (above).
2. **`getDefaultTests()`, `getPatientForm()`, `getOrganizationPrivileges()`, `getOrganizationRedirectUrl()`
   are all behind `FlexibleAuthMiddleware`, not `auth:sanctum`** — they answer to *any* of the four token
   tiers, including a plain invitation `TestSession` with no org at all. They resolve the org from
   request state merged by the middleware, so an invitation-tier caller reaches them with no `org_id`.
3. **`GET api/organization/test` is a closure returning `{valid: true}`**, guarded by `signed`
   *inside* the `auth:sanctum` group. It is a leftover probe, not a feature.
4. **`Organization` is the only model whose `id` is a security parameter.** It appears in a public URL and
   in an HMAC. Never renumber or reuse org ids.
5. **`registration_fee_paid` is cast to `float`** with a comment questioning it. Money as a float —
   compare with a tolerance, or better, don't compare at all.
6. **The org's user and the org row are separate lifecycles.** Look up by `Organization::where('user_id', …)`
   (as `SendAfterPasswordReset` does), not by assuming `$user->organization` is loaded.
