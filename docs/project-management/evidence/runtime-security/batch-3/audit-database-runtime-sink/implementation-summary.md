# Audit database runtime sink

Status: `implementation_written`

This batch adds the package-owned `larena_audit_events` table, `DatabaseAuditSink`, and `AuditServiceProvider`. The provider registers the migration and binds `AuditEventPipeline` to `DefaultAuditRedactor` followed by the database sink.

The sink stores normalized event metadata and the already-redacted JSON payload. It adds no routes, UI, retention purge, queue worker, or production-readiness claim.
