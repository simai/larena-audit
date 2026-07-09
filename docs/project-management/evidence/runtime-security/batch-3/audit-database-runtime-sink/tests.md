# Tests

Runtime: PHP 8.3.31 with Composer platform pinned to PHP 8.3.31.

- legacy Audit contract and fail-closed tests pass;
- `DatabaseAuditSinkTest` creates an isolated file-backed SQLite database;
- the migration creates `larena_audit_events`;
- `AuditEventPipeline` redacts a sensitive payload value before persistence;
- the row is read after the database connection is purged and recreated;
- rollback removes the table;
- the successful test leaves zero `larena-audit-*` files in the system temporary directory;
- PHPStan reports no errors.
