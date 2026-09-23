# Batch 7 wave C — audit without larena/ui

Minimal CMS v1.1 asks for a `headless_v1` composition: REST and MCP, no
Layout, UI or Admin. REST requires audit, and audit required larena/ui for
one screen — the administrator history. The owner decided (2026-09-23) to
make UI optional for audit.

- `larena/ui` moves from `require` to `suggest`, and stays in `require-dev`
  so the history screen keeps its tests.
- The history routes load only when `Larena\Ui\Smart` exists.
- `composer.lock`: `larena/ui` and `larena/dataview` move from `packages` to
  `packages-dev`; the content-hash is recomputed. Composer could not
  regenerate the lock here, because the package's path repositories do not
  include `../core`, which the current `ui` and `dataview` require. That was
  already true before this change.

With UI installed nothing changes. Without it, audit records, reads, redacts
and pipelines events as before; there is no screen.
