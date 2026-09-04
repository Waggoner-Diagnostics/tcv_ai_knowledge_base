# Website Route Index (TCV-Website)

**32 pages · 3 layouts · 4 server API routes** — Next.js App Router.

Every page is a **Server Component** that imports one `*Client.jsx` from `/views` for the
interactive half. `use_client` should be `false` for every row in `/app`; a `true` there means
someone broke the split.

| ID | Route | Client view | `'use client'` in /app? | File |
|---|---|---|---|---|
| `WEB-001` | `/` | _inline_ | no | [app/page.jsx](../../../TCV-Website/app/page.jsx) |
| `WEB-002` | `/about` | `AboutClient` | no | [app/about/page.jsx](../../../TCV-Website/app/about/page.jsx) |
| `WEB-003` | `/advice` | `AdviceClient` | no | [app/advice/page.jsx](../../../TCV-Website/app/advice/page.jsx) |
| `WEB-004` | `/advice/parents` | `ParentsClient` | no | [app/advice/parents/page.jsx](../../../TCV-Website/app/advice/parents/page.jsx) |
| `WEB-005` | `/advice/problems` | `ProblemsClient` | no | [app/advice/problems/page.jsx](../../../TCV-Website/app/advice/problems/page.jsx) |
| `WEB-006` | `/advice/schools` | `SchoolsClient` | no | [app/advice/schools/page.jsx](../../../TCV-Website/app/advice/schools/page.jsx) |
| `WEB-007` | `/advice/students` | `StudentsClient` | no | [app/advice/students/page.jsx](../../../TCV-Website/app/advice/students/page.jsx) |
| `WEB-008` | `/advice/teachers` | `TeachersClient` | no | [app/advice/teachers/page.jsx](../../../TCV-Website/app/advice/teachers/page.jsx) |
| `WEB-009` | `/colorblindness/anomalous-trichromacy` | `AnomalousTrichromacyClient` | no | [app/colorblindness/anomalous-trichromacy/page.jsx](../../../TCV-Website/app/colorblindness/anomalous-trichromacy/page.jsx) |
| `WEB-010` | `/colorblindness/deutan` | `DeutanClient` | no | [app/colorblindness/deutan/page.jsx](../../../TCV-Website/app/colorblindness/deutan/page.jsx) |
| `WEB-011` | `/colorblindness/dichromacy` | `DichromacyClient` | no | [app/colorblindness/dichromacy/page.jsx](../../../TCV-Website/app/colorblindness/dichromacy/page.jsx) |
| `WEB-012` | `/colorblindness/how-people-see` | `HowPeopleSeeClient` | no | [app/colorblindness/how-people-see/page.jsx](../../../TCV-Website/app/colorblindness/how-people-see/page.jsx) |
| `WEB-013` | `/colorblindness/interesting-links` | `InterestingLinksClient` | no | [app/colorblindness/interesting-links/page.jsx](../../../TCV-Website/app/colorblindness/interesting-links/page.jsx) |
| `WEB-014` | `/colorblindness/monochromacy` | `MonochromacyClient` | no | [app/colorblindness/monochromacy/page.jsx](../../../TCV-Website/app/colorblindness/monochromacy/page.jsx) |
| `WEB-015` | `/colorblindness/protan` | `ProtanClient` | no | [app/colorblindness/protan/page.jsx](../../../TCV-Website/app/colorblindness/protan/page.jsx) |
| `WEB-016` | `/colorblindness/tritan` | `TritanClient` | no | [app/colorblindness/tritan/page.jsx](../../../TCV-Website/app/colorblindness/tritan/page.jsx) |
| `WEB-017` | `/colorblindness/what-is` | `WhatIsClient` | no | [app/colorblindness/what-is/page.jsx](../../../TCV-Website/app/colorblindness/what-is/page.jsx) |
| `WEB-018` | `/colorblindness/why` | `WhyClient` | no | [app/colorblindness/why/page.jsx](../../../TCV-Website/app/colorblindness/why/page.jsx) |
| `WEB-019` | `/distributors` | _inline_ | no | [app/distributors/page.jsx](../../../TCV-Website/app/distributors/page.jsx) |
| `WEB-020` | `/distributors/signup` | `DistributorSignupClient` | no | [app/distributors/signup/page.jsx](../../../TCV-Website/app/distributors/signup/page.jsx) |
| `WEB-021` | `/faq` | `FaqClient` | no | [app/faq/page.jsx](../../../TCV-Website/app/faq/page.jsx) |
| `WEB-022` | `/pricing` | _inline_ | no | [app/pricing/page.jsx](../../../TCV-Website/app/pricing/page.jsx) |
| `WEB-023` | `/test/adult-instructions` | `AdultTestInstructionsClient` | no | [app/test/adult-instructions/page.jsx](../../../TCV-Website/app/test/adult-instructions/page.jsx) |
| `WEB-024` | `/test/credibility` | `TestCredibilityClient` | no | [app/test/credibility/page.jsx](../../../TCV-Website/app/test/credibility/page.jsx) |
| `WEB-025` | `/test/examples` | `TestExamplesClient` | no | [app/test/examples/page.jsx](../../../TCV-Website/app/test/examples/page.jsx) |
| `WEB-026` | `/test/faqs` | `FaqClient` | no | [app/test/faqs/page.jsx](../../../TCV-Website/app/test/faqs/page.jsx) |
| `WEB-027` | `/test/hospital` | `HospitalInfoClient` | no | [app/test/hospital/page.jsx](../../../TCV-Website/app/test/hospital/page.jsx) |
| `WEB-028` | `/test/instructions` | `TestInstructionsClient` | no | [app/test/instructions/page.jsx](../../../TCV-Website/app/test/instructions/page.jsx) |
| `WEB-029` | `/test/overview` | _inline_ | no | [app/test/overview/page.jsx](../../../TCV-Website/app/test/overview/page.jsx) |
| `WEB-030` | `/test/pediatric-instructions` | `PediatricTestInstructionsClient` | no | [app/test/pediatric-instructions/page.jsx](../../../TCV-Website/app/test/pediatric-instructions/page.jsx) |
| `WEB-031` | `/test/platform` | _inline_ | no | [app/test/platform/page.jsx](../../../TCV-Website/app/test/platform/page.jsx) |
| `WEB-032` | `/test/uses` | `TestUsesClient` | no | [app/test/uses/page.jsx](../../../TCV-Website/app/test/uses/page.jsx) |

## Layouts

| Route | File |
|---|---|
| `/` | [app/layout.jsx](../../../TCV-Website/app/layout.jsx) |
| `/colorblindness` | [app/colorblindness/layout.jsx](../../../TCV-Website/app/colorblindness/layout.jsx) |
| `/test` | [app/test/layout.jsx](../../../TCV-Website/app/test/layout.jsx) |

## Server API routes

| Route | Methods | Forwards to |
|---|---|---|
| `/api/auth` | POST | `/api/login` |
| `/api/countries` | GET | `/api/countries-with-states` |
| `/api/logout` | POST | `/api/logout` |
| `/api/register` | POST | `/api/register` |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-03. Do not hand-edit — re-run the generator._
