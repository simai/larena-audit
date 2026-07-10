# Smoke result

Target: disposable file-backed SQLite application at `127.0.0.1`, removed
after verification.

1. Migrated a clean database and created one temporary administrator.
2. Logged into the persistent protected Admin shell.
3. Created `audit-safe-page` with a private body marker.
4. Published the page.
5. Opened Audit history from the coherent Admin navigation.
6. Observed newest-first Published v2 and Created v1 rows for the same actor.
7. Confirmed the private body marker was absent from both rendered history and
   stored audit payloads.
8. Confirmed an anonymous request redirects to `/admin/login`.
9. Closed the browser tab, stopped the server and removed the SQLite database.

Result: `PASS`.
