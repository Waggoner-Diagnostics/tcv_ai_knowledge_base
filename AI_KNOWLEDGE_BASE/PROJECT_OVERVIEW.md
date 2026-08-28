# Project Overview

**TestingColorVision (TCV)** delivers the Waggoner Computerized Color Vision Test online. A clinician,
employer, school or research organisation buys **credits**, assigns a test to a **patient**, and the
patient takes it in a browser. The system scores it, stores a result snapshot, produces a PDF, and — for
organisations wired to a Learning Management System — reports completion back to that LMS.

## Who uses it

| Role | `usertype` | What they do |
|---|---|---|
| **Super admin** | `1` | Authors tests (sections, plates, conditions), manages users, organisations, pricing, discount codes, restricted IPs, LMS provider configs, and reports |
| **Customer** | `2` | A clinician or practice: buys credits, manages patients, sends tests, reads results |
| **Organization** | `4` | An institution: gets a signed **Test URL**, runs patients through it (optionally via an LMS), with an org-configured intake form |
| **Patient** | — | **Never has an account.** Reaches a test through an emailed invitation, a resume link, or an org Test URL |

There is **no `usertype = 3`.**

## The three products

| Repo | What it is | Deployed as |
|---|---|---|
| `TCV-Backend` | Laravel 12 REST API on PHP 8.4, MySQL, S3 | Docker (php-fpm + nginx) |
| `TCV-Frontend` | React 18 SPA — the admin portal **and** the patient test player | static build served at `/app` |
| `TCV-Website` | Next.js 15 marketing site + the login/register entry point | standalone Node, port 3001 |

## Core concepts

- **Test** — the template: `Test` → `TestSection` → `TestSectionPlate`, plus `TestCondition` rules that
  can skip later sections. Authored by a super admin.
- **PatientTest** — one attempt, identified by a **UUIDv4 `unique_test_id`**. Both-eyes (monocular)
  tests are *two* attempts sharing a `parent_test_id`, with `eye_tested` of `OS` / `OD`; **`OS` is the
  canonical result**.
- **TestAnswer** — one row per plate, created up-front with `answered = 0`. All progress is derived by
  counting these.
- **Credit** — the currency. One credit ≈ one test. The balance is **derived**
  (`SUM(grants) − SUM(spends)`), never stored. Credits come from an admin grant or a Stripe purchase.
- **Session** — a patient is authenticated by one of four token kinds, not by a login
  ([CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md)).
- **Result snapshot** — `patient_tests.result_json`, computed once at completion by
  `ColorVisionDiagnosisService` and never recomputed.

## Test types

Test templates are data, not code, so the catalogue is whatever is seeded. Known anchors:
`App\Support\TestConstants::DEFAULT_TEST_TITLE = 'Adult Diagnostic'` — assigned automatically to every
new `CUSTOMER` at registration. `User` also carries per-account feature flags
(`includeColorVisionTesting`, `includeOlderChildrenCCVT`, `includeWaggnorCCVT`,
`includeWaggnorCCVT10Sec`, `allow_monocular_test`, `show_occupational_questions`).

## The three ways a patient reaches a test

| Route in | Credential | Guard |
|---|---|---|
| **Email invitation** | 32-char token + 6-char code → 2-hour `TestSession` | tier 2 |
| **Resume link** | 64-char token, 7 days → fresh `TestSession` | tier 2 |
| **Organisation Test URL** | `HMAC(org_id, signing_key)` → `LmsSession` | tier 3 |

Plus a logged-in clinician running a test in the room, on their own Sanctum token (tier 1).

## Third parties

Stripe (payments), AWS S3 (test plate images, served as short-lived pre-signed URLs), HubSpot,
Cloudflare Turnstile (bot defence), and per-organisation LMS providers (Cornerstone via xAPI, or a
generic webhook). See [THIRD_PARTY.md](THIRD_PARTY.md).

## What is *not* here

- No mobile or desktop client in these repos. (`TCV-Frontend` contains a large dead file pasted from
  such an app — see [FRONTEND.md](FRONTEND.md).)
- No scheduler and no queue worker in either compose file, despite a `database` queue
  ([QUEUES.md](QUEUES.md)).
- No repository layer, no observers, no broadcast channels
  ([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)).
