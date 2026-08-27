# Public (Unauthenticated) Endpoint Audit

Derived view: every `api/*` route reachable **with no token of any kind**. Auth in this codebase is
opt-in per route — a route added outside the `auth:sanctum` or `FlexibleAuthMiddleware` group is
public by default. This list is the blast radius of that design; re-read it every release.

**21 of 177 endpoints are public.**

Several are legitimately public (login and registration precede a token; the invitation and resume
flows authenticate by emailed token *inside* the controller). The ones to scrutinise are those that
read or mutate money, credits, or another user's data.

| ID | Method | URI | Action |
|---|---|---|---|
| `API-010` | GET | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates |
| `API-034` | POST | `api/login` | AuthController@login |
| `API-043` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature |
| `API-051` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail |
| `API-052` | POST | `api/password/reset` | AuthController@setOrResetPassword |
| `API-053` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken |
| `API-074` | POST | `api/register` | AuthController@register |
| `API-079` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken |
| `API-080` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink |
| `API-081` | GET | `api/reset-password/{token}` | _(closure)_ |
| `API-091` | POST | `api/stripe/confirm-payment` | StripePaymentController@confirmPayment |
| `API-092` | POST | `api/stripe/create-payment-intent` | StripePaymentController@createPaymentIntent |
| `API-093` | GET | `api/stripe/payment-methods` | StripePaymentController@getPaymentMethods |
| `API-094` | POST | `api/stripe/payment-methods/set-default` | StripePaymentController@setDefaultPaymentMethod |
| `API-095` | DELETE | `api/stripe/payment-methods/{payment_method_id}` | StripePaymentController@removePaymentMethod |
| `API-101` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus |
| `API-102` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode |
| `API-103` | POST | `api/test-invitations/send` | TestInvitationController@sendInvitations |
| `API-112` | POST | `api/test/resume` | TestResumeController@resume |
| `API-174` | GET | `api/validate-token` | AuthController@isTokenValid |
| `API-175` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken |

## Also public: `routes/web.php`

| Method | URI | Action |
|---|---|---|
| GET | `/` | _(closure)_ |
| GET | `payment/callback` | StripePaymentController@paymentCallback |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-08-19. Do not hand-edit — re-run the generator._
