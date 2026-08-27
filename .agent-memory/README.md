# Agent memory (portable copy)

This folder is the **version-controlled copy** of Claude Code's per-project memory for this KB, so the
memory travels with the repo when you upload/clone it. It is **not** read directly by Claude Code — the
harness only reads memory from the user profile:

```
<user-home>/.claude/projects/<kb-path-with-separators-as-dashes>/memory/
```

## Install on a new machine / server

1. Put this KB folder wherever it will live, then **open Claude Code once with that folder as the
   working directory** — this creates `~/.claude/projects/<hash>/` for that path automatically.
2. Copy these files into that folder's `memory/` subdirectory (create `memory/` if it's missing):

   **macOS / Linux**
   ```bash
   dest=~/.claude/projects/$(pwd | sed 's#[/\\:]#-#g')/memory
   mkdir -p "$dest" && cp .agent-memory/MEMORY.md .agent-memory/update-kb-after-passing-tasks.md "$dest"/
   ```
   (Run from the KB root. If unsure of the exact `<hash>`, look under `~/.claude/projects/` for the
   folder whose name matches this KB's path — that's the one Claude Code created in step 1.)

   **Windows (PowerShell)** — copy `MEMORY.md` and `update-kb-after-passing-tasks.md` into
   `C:\Users\<you>\.claude\projects\<hash>\memory\`.

## Keeping it in sync

This copy is the source of truth to commit. If you change the live memory (in the profile `memory/`
folder) during a session, copy the updated files back here and commit, so the next upload carries the
change. The memory content uses **bare repo names only** (no absolute paths), so it's machine-portable —
only its *location* is machine-specific.
