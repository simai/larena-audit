# Larena Audit

Unified structured audit layer that receives package AuditEvent objects, validates taxonomy, redacts sensitive payloads, correlates runtime activity, routes events to sinks and provides operator search/export/diagnostics.

## Durable database pipeline

Consumers that must prove transaction compatibility can depend on
`ConnectionBoundAuditEventPipeline`. The default implementation,
`DatabaseAuditEventPipeline`, exposes the exact Laravel database connection
object used by its single `DatabaseAuditSink`.

The proof-bearing pipeline:

- applies the existing `DefaultAuditRedactor`;
- propagates descriptor, redaction, JSON and database failures;
- joins a caller-owned transaction without beginning, committing or rolling it
  back;
- has no memory, null, log, external, fallback or multiple-sink mode.

The existing generic `AuditEventPipeline`, `AuditSink` and
`DatabaseAuditSink` bindings remain available for backward compatibility. Only
the connection-bound contract proves exact database connection identity.

See [the Security Audit contract](docs/developer/security-audit-contract.md)
for integration and failure semantics.

Canonical specifications are in `simai/larena-specs`.
