# Implementation summary

| File | Change |
| --- | --- |
| `composer.json` | `larena/ui` moved from `require` to `suggest`; kept in `require-dev` |
| `composer.lock` | `larena/ui`, `larena/dataview` moved to `packages-dev`; content-hash recomputed |
| `src/Providers/AuditServiceProvider.php` | history routes load only when `Larena\Ui\Smart` exists |

No change to events, redaction, sinks, the read model, migrations or routes.
