# Function Index (global helpers)

**There are no global (free) PHP functions in this codebase.** `composer.json` has no
`autoload.files` entry, so nothing is callable without an import.

What plays the helper role instead — both are **static classes you must import**:

| Class | File | Role |
|---|---|---|
| `App\Helpers\ApiResponse` | `app/Helpers/ApiResponse.php` | The JSON envelope every controller returns |
| `App\Helpers\TestHelper` | `app/Helpers/TestHelper.php` | Test-flow helpers |
| `App\Support\HttpStatus` · `App\Support\TestConstants` | `app/Support/` | Status codes and test constants |

See [METHOD_INDEX.md](METHOD_INDEX.md) for their methods, and [CONSTANTS.md](CONSTANTS.md)
for the constant values.

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-04. Do not hand-edit — re-run the generator._
