# Contract Drift — client calls with no backend route

Derived view: TCV-Frontend / TCV-Website call sites whose URL matches **no route** in
[API_ENDPOINT_INDEX.md](API_ENDPOINT_INDEX.md). Each one is a request that reaches the API and comes
back **404**.

**7 unmatched of 87 distinct SPA calls.**

Read the `Why` column before acting — a row is one of three things:

- **`no such path`** — the endpoint does not exist at all. Either the client is calling a removed
  endpoint (fix the client) or one that was never built (fix the backend). Both are real bugs.
- **`verb mismatch`** — the path exists under a different HTTP method. Almost always a client bug.
- **`scanner limit`** — the literal URL is a fragment (a `{param}` base assembled elsewhere). Not a
  finding; the scanner simply cannot resolve it.
- **`dead file`** — the call is real, but it sits in a module **no other module imports**. Nothing
  reaches it at runtime, so it is not a live 404 — it is code to delete.

| Method | Client path | Why | Call site |
|---|---|---|---|
| GET | `/api/auth/ping-api` | _dead file_ | [src/constants/testDescriptionConstants.js:553](../../../TCV-Frontend/src/constants/testDescriptionConstants.js#L553) |
| GET | `/api/tests/sent-invitations` | **no such path** | [src/redux/slices/tests/sendTestSlice.js:89](../../../TCV-Frontend/src/redux/slices/tests/sendTestSlice.js#L89) |
| POST | `/api/user/tests/bulk-update-visibility` | **no such path** | [src/redux/slices/tests/testVisibilitySlice.js:44](../../../TCV-Frontend/src/redux/slices/tests/testVisibilitySlice.js#L44) |
| GET | `/{param}` | _scanner limit_ | [src/redux/slices/createpaginatedslice.js:65](../../../TCV-Frontend/src/redux/slices/createpaginatedslice.js#L65) |
| DELETE | `/{param}/{param}` | _scanner limit_ | [src/redux/slices/createSlice.js:94](../../../TCV-Frontend/src/redux/slices/createSlice.js#L94) _(+1)_ |
| PUT | `/{param}/{param}` | _scanner limit_ | [src/redux/slices/createSlice.js:77](../../../TCV-Frontend/src/redux/slices/createSlice.js#L77) _(+1)_ |
| GET | `/{param}{param}` | _scanner limit_ | [src/apis/miscApis.js:7](../../../TCV-Frontend/src/apis/miscApis.js#L7) |

---

## TCV-Website proxy routes

The marketing site never calls the API from the browser. Four Next.js **server** routes forward to
the backend, so the browser only ever talks to the website's own origin.

| Website route | Methods | Forwards to | Backend route exists? |
|---|---|---|---|
| `/api/auth` | POST | `/api/login` | ✅ `API-035` |
| `/api/countries` | GET | `/api/countries-with-states` | ✅ `API-010` |
| `/api/logout` | POST | `/api/logout` | ✅ `API-036` |
| `/api/register` | POST | `/api/register` | ✅ `API-075` |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-03. Do not hand-edit — re-run the generator._
