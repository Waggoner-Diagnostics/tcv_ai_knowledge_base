# Context: Patients

> Load this **instead of** reading the patient subsystem. ~900 tokens. A "patient" is the *subject* of a
> test and is **not** a `User` — patients never authenticate.

## Files
| File | Role |
|---|---|
| `app/Models/Patient.php` | 9 fillable columns, two relations, `GENDERS` + `genderLabel()` |
| `app/Http/Controllers/PatientController.php` (432 lines) | CRUD, `getPatientTests()`, `resendTestLink()` |
| `app/Http/Controllers/OrganizationPatientController.php` (305 lines) | Org/LMS intake (`default`, `prolific`) |
| `app/Http/Requests/PatientAddRequest.php` · `PatientUpdateRequest.php` | Validation |
| `app/Services/PatientTestTransformer.php` | Shapes the patient→tests payload |
| `app/Models/ProlificId.php` | Prolific research-panel identity |

## Tables
`patients` · `patient_tests` · `prolific_ids` · `organization_patient_sessions`

---

## Shape

```php
// Patient::$fillable
'first_name', 'last_name', 'dob', 'user_id', 'patient_id', 'email', 'zipcode', 'test_condition', 'gender'
```

- **`user_id`** — the *owner* (the clinician/customer/org `User`), not the patient. This is the only
  tenancy boundary in the table.
- **`patient_id`** — the owner's own external reference (a chart number), free text. Not the primary key.
  `patients.id` is the primary key and is a sequential integer.
- **`gender`** — nullable tinyInteger: `1` Male · `2` Female · `3` Intersex. The accepted values live
  once in `Patient::GENDERS` ([CONSTANTS](../INDEXES/CONSTANTS.md)); both patient FormRequests alias it
  (`const GENDER = Patient::GENDERS`) and build their `in:` rule from its keys, and
  `Patient::genderLabel()` is the only label mapping (`TestService`, `TestResultService`, and through
  them the result PDF). Add a gender in the constant and validation, reports and the PDF all follow.
  `null` means *not specified* and `genderLabel()` returns `null` for it — the org intake paths
  (`storeDefaultPatient`, `storeProlificPatient`) do not validate gender at all and default it to null.
- `Patient::tests()` → `hasMany(PatientTest, 'patient_id')` — keyed on `patients.id`, not on the
  `patient_id` column. Two similarly-named things; read carefully.

---

## The three ways a patient is created

| Path | Endpoint | Guard |
|---|---|---|
| Clinician adds one | `POST api/patients` | `FlexibleAuthMiddleware` |
| Org form (standard) | `POST api/organization/patient/default` | `FlexibleAuthMiddleware` + `lms.status:launched,identity_resolved` |
| Prolific panel | `POST api/organization/patient/prolific` | same |

The org paths honour the organisation's display flags (`anonymize_patient`, `show_gender`, `show_zip`,
`show_patient_id`, …) — see [ORGANIZATION_CONTEXT](ORGANIZATION_CONTEXT.md). An anonymised org creates
patients with **no real name**, which is exactly what `index()` keys off to compute `is_prolific`:

```php
'is_prolific' => $isProlificOrg && (empty($p->first_name) || $p->first_name === 'N/A'),
```

☠️ The literal `'N/A'` sentinel is load-bearing. Changing what the anonymised form writes into
`first_name` silently breaks the prolific flag.

---

## ☠️ Traps

1. **`show()`, `update()` and `destroy()` have no ownership check** — only `index()` filters by
   `user_id`. Ids are sequential. This is reachable by *any* of the four token tiers, including an
   invitation session. [S-14](../SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping).
2. **`update()` uses `$request->all()`, not `$request->validated()`** — so `PatientUpdateRequest`'s
   filtering is bypassed and every fillable column is writable, `user_id` included. Same finding.
3. **`Patient` soft-deletes; `PatientTest` does not.** `destroy()` sets `deleted_at` and leaves every
   `patient_tests` row live and queryable. Any query that joins **through** `patients` silently drops
   those tests (the global scope hides the parent), while a query straight off `patient_tests` still
   returns them — so a deleted patient's tests appear in one report and vanish from another. Use
   `withTrashed()` on the join when the report is meant to be complete.

   Exactly **four** models soft-delete: `User`, `Patient`, `DiscountCode`, `LmsProviderConfig`. Check
   the `Traits` column in [MODEL_INDEX](../INDEXES/MODEL_INDEX.md) before assuming.
4. **`dob` is stored as written.** No cast on the model, no timezone handling. Age arithmetic in reports
   and in `ColorVisionDiagnosisService` reads it as a raw date string.
5. **Patients are never deduplicated.** Nothing enforces uniqueness on `email` or `patient_id`, so the
   same person invited twice becomes two `patients` rows with separate test histories.
6. **`resendTestLink()` lives here, not in `TestInvitationController`** — `POST api/resend-test-link`
   (`auth:sanctum`). If you are changing invitation resend behaviour, there are two places.
7. **The SPA holds `gender` in two different shapes.** The fallback selector (`AddPatient.js`) stores the
   *label* — `form.gender === "Male"` — and `usePatientForm.buildPayload()` converts it with
   `getGenderValue()`. The org/field-rules selector (`PatientFormFields.jsx`) stores the *stringified id*
   — `form.gender === "1"` — and `formUtils.buildPayload()` ships it as-is. Both land in the same column.
   `GENDER_OPTIONS` in `src/utils/testUtils.js` is the single list both render from, but check which
   shape a form is in before comparing `form.gender` to anything.
