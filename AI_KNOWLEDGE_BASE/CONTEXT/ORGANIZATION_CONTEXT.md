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
7. **`GET api/organizations` sorting** (`OrganizationController::index()`, `ws-502`, on `develop` 2026-09-17).
   `sortBy`/`sortOrder` (camelCase), allow-listed. `account_status` and `credits` **inner-join
   `users`**, which is safe only because `organizations.user_id` is NOT NULL. `compliance` left-joins
   `compliances` and orders by its name, with no compliance last ascending. **Every sort now ends on
   `organizations.id`** (qualified, because of those joins). Before `ws-502` none had a tiebreak. With
   most orgs on one compliance and many at 0/Unlimited credits, Status, Compliance and Credits
   repeated or dropped rows across pages on MySQL. ⚠️ **Tests need `Sanctum::actingAs($user,
   ['view-organizations'])`.** The policy checks `tokenCan()`, so plain `actingAs()` gets "This action
   is unauthorized" wrapped in a **500** by the controller's catch-all. Pinned by
   `tests/Feature/Organizations/OrganizationListSortTest.php`.
8. ☠️ **Compliance row ids are load-bearing on the frontend — never renumber them.** `validations.js`
   requires `static_ip` unless `compliance_id === "1"`, and `OrganisationModal.js` hides the asterisk on
   the same literal, so *which row holds "AICC/SABA"* is a contract rather than an implementation
   detail. The data migration duplicated `compliances` (3–4 alongside the seeded 1–2) and
   `organization_types` (6 names twice), then bound every migrated org to the duplicate — silently making
   Static IP a required field for **57** organisations. `ws-459` merges duplicates onto the **lowest** id
   precisely to keep `1` = AICC/SABA, and the dropdown endpoints (`DropdownValuesController`, no
   `DISTINCT`, no ordering) return one row per option, so any duplicate row *is* a duplicate option. See
   [DATA_MIGRATION_CONTEXT trap 8](DATA_MIGRATION_CONTEXT.md). Pinned by
   `tests/Feature/Organizations/OrganizationLookupDuplicatesTest.php`.
9. **No redirect URL means the patient lands on the result page with nowhere to go.**
   `getOrganizationRedirectUrl()` is what the SPA's `ResultPage` checks first. An org with no
   `organization_configs.redirect_url` shows the result, and, without print or download privileges, a
   lone **Close** button. That button used to be dead: `window.close()` is refused on a tab no script
   opened. ✅ Fixed by frontend PR #441 (`fix/ux-result-close-datefilter`, on `develop` since 2026-10-09)
   with a "you can now close this tab" fallback ([FRONTEND.md](../FRONTEND.md#the-result-page-close-button)).
10. ☠️ **Deleting an Integrated Partner fails with "Something went wrong" once its launch URL has been
    used (open, product decision pending, found 2026-11-04).** `OrganizationController::destroy`
    (`:656`) force-deletes the linked user, then `$organization->delete()` (`:674`). Three foreign keys
    are `ON DELETE RESTRICT`, so MySQL refuses while any child row exists:
    `lms_provider_configs.org_id` → `organizations.id` (`2026_05_08_000001`, line 22),
    `lms_sessions.org_id` → `organizations.id` (`…000002`, line 36) and
    `lms_sessions.lms_provider_config_id` → `lms_provider_configs.id` (`…000002`, line 37).
    `getOrCreateProviderConfig` (`LmsLaunchService.php:17`, called from
    `OrganizationController.php:834`) creates the config row lazily the **first time the launch URL is
    opened**. So partners never launched delete fine and used partners always fail, which looks random.
    The `catch` (`:698`) rolls back (the user is restored too), logs, and returns **500 `api.error`**
    with **`$e->getMessage()` as the body**, so raw SQL (table and constraint names) reaches the
    browser. Same leak at `:345`, `:429`, `:443`, `:625`, `:652`.
    - **`lms_provider_configs` has `softDeletes()`, which does not help:** the DB foreign key ignores
      soft-deleted rows. Tear-down order must be `lms_sessions`, then `lms_provider_configs`
      (`forceDelete`), then the org. `lms_delivery_queue` (cascade from session) and
      `lms_delivery_tokens` (cascade from config) clean themselves up.
    - **Not yet verified:** other tables that point at `organizations` (patients, tests, credits) may
      also be `RESTRICT`. Check before declaring a fix complete.
    - **Options:** (A) delete sessions and configs inside `destroy`, which is small but loses LMS
      history permanently; (B) catch the FK violation and return a clear 409, a stopgap;
      (C) soft-delete `Organization`, which keeps history but touches every partner list, lookup and
      name/email uniqueness check. Stop echoing `$e->getMessage()` in all cases. Decision owner:
      product. Product-owner brief: https://claude.ai/artifact/PHHpEuMpMePgVyoRpsHCt8

---

## UI terminology & form changes (2026-10-12, `client-unbilled-changes`)

> Frontend side is on `develop` (PR #445, merged 2026-10-13). Backend side is on `develop` (PR #315, merge
> `9082ae22`; indexed 2026-10-15).

- "Organization(s)" -> **Integrated Partner(s)** in the UI only (routes/tables/keys unchanged). Org-settings checkboxes keep
  DB option names, displayed via `ORG_OPTION_LABELS` in `OrganisationModal.js`. Only "Distributor" is used (no reseller/affiliate).
- `run_test_on_subdomain` removed from the SPA create/edit form, payload and validation; backend column/fillable/cast kept, field optional.
- `patient_id` is `required=false` in both the default rules (`OrganizationController.php:904`) and the
  option-selected branch, which forces it back to false (`:956-958`) (Unique Identifier optional).
- Status label **Inactive -> Restricted**; stored value unchanged (`inactive`). ☠️ **Not accounts-only in the SPA:**
  the shared `StatusBadge` (`misc.js:22`) maps `inactive` -> "Restricted" everywhere, so the **Tests list**
  (`testColumns.js:34-36`) also shows inactive tests as "Restricted" while `EditTestForm.js:172` still says
  "Inactive". Discount codes use their own badge. Backend: account status-change audit titles/descriptions say
  "Restricted" (`AuditEventCatalog::statusChangeTitle`, `AuditEventCatalog.php:562-567`, `account.*` keys only);
  `settings.test_status_changed` still says "(Inactive)". Login API message unchanged (`api.resticted`); only the
  audit Reason became "Account is restricted" (`AuthController.php:120`).
- Audit detail labels changed: "Integrated Partner Type/Name", "Test Taker Form Fields", "Deleted Integrated
  Partner", "Integrated Partner ID", "Test Taker Ref", "Test Taker ID" (`TestController.php:270`). Older rows keep
  the old labels, so the audit log mixes both — anything parsing `details` by label must accept both.
