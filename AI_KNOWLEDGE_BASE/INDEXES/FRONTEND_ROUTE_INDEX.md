# Frontend SPA Route Index (TCV-Frontend)

**64 top-level routes** (10 nested tab routes) from `src/router/routes/`.

A protected route renders **only if it appears in both places**: `protectedRoutes.js` (the route
exists) *and* `RouteConfig[role].parentRoutes` (the role may see it). `Router.js` intersects the two
at runtime. The `Roles` column below is that intersection — `⚠ none` means the route is registered
but **unreachable by every role**.

| ID | Kind | Path | Component | Roles allowed |
|---|---|---|---|---|
| `FE-001` | protected | `/add-credits` | `AddCredits` | SUPER_ADMIN |
| `FE-002` | protected | `/admin` | `Admin` | SUPER_ADMIN, CUSTOMER |
| `FE-003` | protected | `/credit-history` | `CreditHistory` | SUPER_ADMIN |
| `FE-004` | protected | `/dashboard` | `DashboardWrapper` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-005` | protected | `/discount` | `DiscountCodes` | SUPER_ADMIN |
| `FE-006` | public | `/forgot-password` | `ForgotPassword` | _n/a — public_ |
| `FE-007` | public | `/login` | `Login` | _n/a — public_ |
| `FE-008` | protected | `/organisation` | `Organisation` | SUPER_ADMIN, ORGANIZATION |
| `FE-009` | public | `/organization/instruction/:uniqueTestId` | `InstructionPageForTest` | _n/a — public_ |
| `FE-010` | public | `/organization/patients/add` | `OrganizationPatient` | _n/a — public_ |
| `FE-011` | public | `/organization/verified/default-user` | `VerifiedDefaultUser` | _n/a — public_ |
| `FE-012` | public | `/organization/verified/tests` | `OrganizationTestHome` | _n/a — public_ |
| `FE-013` | public | `/payment/return` | `PaymentReturn` | _n/a — public_ |
| `FE-014` | protected | `/pricing` | `Pricing` | SUPER_ADMIN, CUSTOMER |
| `FE-015` | protected | `/public-pages` | `PublicPagesWraper` | SUPER_ADMIN |
| `FE-016` | public | `/register` | `Register` | _n/a — public_ |
| `FE-017` | protected | `/reports` | `ReportsWrapper` | SUPER_ADMIN, SUPER_ADMIN |
| `FE-018` | protected | `/reports/user-tests/patient/:patientId/:userName` | `UserTestDetail` | SUPER_ADMIN |
| `FE-019` | public | `/reset-password/:token` | `ResetPassword` | _n/a — public_ |
| `FE-020` | public | `/set-password/:token` | `SetPassword` | _n/a — public_ |
| `FE-021` | protected | `/settings` | `Settings` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-022` | shared | `/shared` | `SharedComponent` | _n/a — shared_ |
| `FE-023` | public | `/test-invitation/test/instruction/:uniqueTestId` | `InstructionPageForTest` | _n/a — public_ |
| `FE-024` | public | `/test-invitation/test/start-test/countdown/:uniqueTestId` | `CountdownScreen` | _n/a — public_ |
| `FE-025` | public | `/test-invitation/test/start-test/prepare/:uniqueTestId` | `PrepareScreen` | _n/a — public_ |
| `FE-026` | public | `/test-invitation/test/start-test/result/:uniqueTestId` | `ResultPage` | _n/a — public_ |
| `FE-027` | public | `/test-invitation/test/start-test/section-complete/:uniqueTestId` | `SectionCompleteScreen` | _n/a — public_ |
| `FE-028` | public | `/test-invitation/test/start-test/test/:uniqueTestId` | `TestPage` | _n/a — public_ |
| `FE-029` | public | `/test-invitation/test/start-test/transition/:uniqueTestId` | `TransitionScreen` | _n/a — public_ |
| `FE-030` | public | `/test-invitation/verified/:token/add-patient` | `VerifiedUserAddPAtient` | _n/a — public_ |
| `FE-031` | public | `/test-invitation/verify/:token` | `VerifyTestUser` | _n/a — public_ |
| `FE-032` | public | `/test/resume/:token` | `ResumeTest` | _n/a — public_ |
| `FE-033` | protected | `/tests` | `Test` | SUPER_ADMIN, CUSTOMER |
| `FE-034` | protected | `/user-panel` | `UserPanelHome` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-035` | protected | `/user-panel/checkout` | `CheckoutPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-036` | protected | `/user-panel/contact` | `ContactUs` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-037` | protected | `/user-panel/credit` | `CreditPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-038` | protected | `/user-panel/done` | `DonePage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-039` | protected | `/user-panel/next` | `NextScreen` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-040` | protected | `/user-panel/patient-tests` | `PatientTestList` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-041` | protected | `/user-panel/patient-tests/:patientId` | `PatientTestList` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-042` | protected | `/user-panel/patients` | `PatientPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-043` | protected | `/user-panel/patients/add` | `AddPatient` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-044` | protected | `/user-panel/patients/edit/:patientId` | `AddPatient` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-045` | protected | `/user-panel/payment-status` | `PaymentStatusPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-046` | protected | `/user-panel/result` | `ResultPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-047` | protected | `/user-panel/settings` | `SettingsPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-048` | protected | `/user-panel/start-test/:testId/confirm/:patientId` | `StartTestConfirmPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-049` | protected | `/user-panel/start-test/:testId/patients` | `PatientPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-050` | protected | `/user-panel/start-test/:testId/patients/add` | `AddPatient` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-051` | protected | `/user-panel/start-test/countdown/:uniqueTestId` | `CountdownScreen` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-052` | protected | `/user-panel/start-test/instruction` | `InstructionPage` | ⚠ **none** |
| `FE-053` | protected | `/user-panel/start-test/instruction/:uniqueTestId` | `InstructionPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-054` | protected | `/user-panel/start-test/prepare/:uniqueTestId` | `PrepareScreen` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-055` | protected | `/user-panel/start-test/result/:uniqueTestId` | `ResultPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-056` | protected | `/user-panel/start-test/section-complete/:uniqueTestId` | `SectionCompleteScreen` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-057` | protected | `/user-panel/start-test/test` | `TestPage` | ⚠ **none** |
| `FE-058` | protected | `/user-panel/start-test/test/:uniqueTestId` | `TestPage` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-059` | protected | `/user-panel/start-test/transition/:uniqueTestId` | `TransitionScreen` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-060` | protected | `/user-panel/timer` | `TimerScreen` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-061` | protected | `/users` | `Users` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `FE-062` | public | `/verify-email` | `VerifyEmail` | _n/a — public_ |
| `FE-063` | protected | `/view-test-plate` | `ViewTestPlate` | SUPER_ADMIN, ORGANIZATION |
| `FE-064` | protected | `/view-webgl-report` | `ViewWebglReport` | SUPER_ADMIN, ORGANIZATION |

---

## Gating drift

### Registered but no role can reach it (2)

| Path | Component |
|---|---|
| `/user-panel/start-test/instruction` | `InstructionPage` |
| `/user-panel/start-test/test` | `TestPage` |

### Granted to a role but no such route exists (6)

Dead entries in `RouteConfig`. Harmless at runtime, but they make the config lie about what a
role can do — read the table above, not `routeConfig.js`, to answer "can this role see X?".

| Path in RouteConfig | Granted to |
|---|---|
| `/logout` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `/page-categories` | SUPER_ADMIN |
| `/profile` | CUSTOMER, ORGANIZATION |
| `/user-panel/instruction` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `/user-panel/result/:uniqueTestId` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |
| `/user-panel/test` | SUPER_ADMIN, CUSTOMER, ORGANIZATION |

---

## Child (tab) routes

| Parent | Tab | Component |
|---|---|---|
| _(see `src/router/routes/protectedRoutes.js`)_ | `user-tests` | `UserTests` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `discount-code` | `DiscountCode` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `profile-summary` | `ProfileSummary` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `test-email-template-configuraion` | `TestEmailTemplate` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `profile` | `ProfileSettings` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `test-qa` | `TestQA` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `restricted-ips` | `RestrictedIps` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `webgl-result` | `WebglReports` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `page-categories` | `PageCategories` |
| _(see `src/router/routes/protectedRoutes.js`)_ | `public-pages` | `PublicPages` |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-02. Do not hand-edit — re-run the generator._
