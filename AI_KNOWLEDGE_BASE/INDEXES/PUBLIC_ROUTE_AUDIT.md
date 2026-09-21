# Public (Unauthenticated) Endpoint Audit

Derived view: every `api/*` route reachable **with no token of any kind**. Auth in this codebase is
opt-in per route — a route added outside the `auth:sanctum` or `FlexibleAuthMiddleware` group is
public by default. This list is the blast radius of that design; re-read it every release.

**17 of 164 endpoints are public.**

Several are legitimately public (login and registration precede a token; the invitation and resume
flows authenticate by emailed token *inside* the controller). The ones to scrutinise are those that
read or mutate money, credits, or another user's data.

| ID | Method | URI | Action |
|---|---|---|---|
| `API-001` | GET|HEAD | `api/access-check` | _(closure)_ |
| `API-015` | GET|HEAD | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates |
| `API-032` | POST | `api/distributor-enquiry` | DistributorController@submit |
| `API-039` | POST | `api/login` | AuthController@login |
| `API-048` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature |
| `API-056` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail |
| `API-057` | POST | `api/password/reset` | AuthController@setOrResetPassword |
| `API-058` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken |
| `API-077` | POST | `api/register` | AuthController@register |
| `API-082` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken |
| `API-083` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink |
| `API-084` | GET|HEAD | `api/reset-password/{token}` | _(closure)_ |
| `API-100` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus |
| `API-101` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode |
| `API-111` | POST | `api/test/resume` | TestResumeController@resume |
| `API-161` | GET|HEAD | `api/validate-token` | AuthController@isTokenValid |
| `API-162` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken |

## Also public: `routes/web.php`

| Method | URI | Action |
|---|---|---|
| GET|HEAD | `/` | _(closure)_ |
| GET|HEAD | `payment/callback` | StripePaymentController@paymentCallback |
| GET|HEAD | `sanctum/csrf-cookie` | Laravel\Sanctum\Http\Controllers\CsrfCookieController@show |
| GET|HEAD | `storage/{path}` | _(closure)_ |
| PUT | `storage/{path}` | _(closure)_ |
| GET|HEAD | `up` | _(closure)_ |
| GET|HEAD | `{prefix?}/cities` | Nnjeim\World\Http\Controllers\City\CityController@index |
| GET|HEAD | `{prefix?}/countries` | Nnjeim\World\Http\Controllers\Country\CountryController@index |
| GET|HEAD | `{prefix?}/currencies` | Nnjeim\World\Http\Controllers\Currency\CurrencyController@index |
| GET|HEAD | `{prefix?}/geolocate` | Nnjeim\World\Http\Controllers\Geolocate\GeolocateController@index |
| GET|HEAD | `{prefix?}/languages` | Nnjeim\World\Http\Controllers\Language\LanguageController@index |
| GET|HEAD | `{prefix?}/states` | Nnjeim\World\Http\Controllers\State\StateController@index |
| GET|HEAD | `{prefix?}/timezones` | Nnjeim\World\Http\Controllers\Timezone\TimezoneController@index |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-21. Do not hand-edit — re-run the generator._
