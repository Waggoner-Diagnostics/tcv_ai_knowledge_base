# Enum Index

**No native PHP `enum` types exist in this codebase.**

Enumerated values are expressed three other ways, none of them type-safe:

1. **Class constants** — `User::SUPER_ADMIN`, `App\Support\TestConstants`, `App\Support\HttpStatus`. See [CONSTANTS.md](CONSTANTS.md).
2. **Database `enum` / string columns** — see [DATABASE_TABLE_INDEX.md](DATABASE_TABLE_INDEX.md).
3. **Bare string literals** — the LMS session state machine (`launched`, `identity_resolved`,
   `form_submitted`, `test_assigned`, `completed`) travels as plain strings through
   `lms.status:` middleware arguments in `routes/api.php`. A typo there fails **open or closed
   silently** — there is no compiler check. See [CONTEXT/LMS_CONTEXT.md](../CONTEXT/LMS_CONTEXT.md).

> **Trap:** `users.usertype` skips 3 — `1 = SUPER_ADMIN`, `2 = CUSTOMER`, `4 = ORGANIZATION`.
> Do not assume the values are contiguous, and never iterate `1..n` over them.

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-02. Do not hand-edit — re-run the generator._
