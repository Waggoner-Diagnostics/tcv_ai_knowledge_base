# Public (Unauthenticated) Endpoint Audit

Derived view: every `api/*` route reachable **with no token of any kind**. Auth in this codebase is
opt-in per route — a route added outside the `auth:sanctum` or `FlexibleAuthMiddleware` group is
public by default. This list is the blast radius of that design; re-read it every release.

**17 of 170 endpoints are public.**

Several are legitimately public (login and registration precede a token; the invitation and resume
flows authenticate by emailed token *inside* the controller). The ones to scrutinise are those that
read or mutate money, credits, or another user's data.

| ID | Method | URI | Action |
|---|---|---|---|
| `API-001` | GET | `api/access-check` | _(closure)_ |
| `API-015` | GET | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates |
| `API-032` | POST | `api/distributor-enquiry` | DistributorController@submit |
| `API-039` | POST | `api/login` | AuthController@login |
| `API-048` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature |
| `API-056` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail |
| `API-057` | POST | `api/password/reset` | AuthController@setOrResetPassword |
| `API-058` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken |
| `API-079` | POST | `api/register` | AuthController@register |
| `API-087` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken |
| `API-088` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink |
| `API-089` | GET | `api/reset-password/{token}` | _(closure)_ |
| `API-106` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus |
| `API-107` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode |
| `API-117` | POST | `api/test/resume` | TestResumeController@resume |
| `API-167` | GET | `api/validate-token` | AuthController@isTokenValid |
| `API-168` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken |

## Also public: `routes/web.php`

| Method | URI | Action |
|---|---|---|
| GET | `/` | _(closure)_ |
| GET | `payment/callback` | StripePaymentController@paymentCallback |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-10-15. Do not hand-edit — re-run the generator._
