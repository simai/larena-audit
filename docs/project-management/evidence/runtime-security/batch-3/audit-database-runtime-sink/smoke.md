# Smoke

The executable smoke is `php tests/Unit/DatabaseAuditSinkTest.php`.

It uses only a generated file-backed SQLite database, routes a `docara.page.published` event through the real redactor and database sink, reconnects, verifies the stored redacted event, rolls the migration back, and removes the temporary database.

No existing application, staging, or production database is opened or modified.
