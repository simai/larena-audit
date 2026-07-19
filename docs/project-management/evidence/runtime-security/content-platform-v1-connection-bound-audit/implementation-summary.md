# Implementation summary

The package now exposes
`Larena\Audit\Contracts\ConnectionBoundAuditEventPipeline` with two methods:

- `connection(): ConnectionInterface`;
- `route(AuditEventDescriptor, AuditEvent): AuditEvent`.

`Larena\Audit\Runtime\DatabaseAuditEventPipeline` is a final, immutable
implementation. Its constructor accepts one database connection, stores that
exact object and builds one existing `AuditEventPipeline` with
`DefaultAuditRedactor` and one existing `DatabaseAuditSink` on the same object.

The implementation does not accept arbitrary sinks, start a transaction,
commit, roll back, catch routing failures or provide a fallback. Descriptor,
forbidden-field, redaction, JSON and database errors continue to propagate
through the existing generic pipeline.

`AuditServiceProvider` adds a transient concrete binding from the default
`DatabaseManager` connection and aliases the contract to the concrete class.
The pre-existing `DatabaseAuditSink`, `AuditSink` and generic
`AuditEventPipeline` bindings retain their original composition and lifetime.

Composer changes are scripts-only. There are no dependency, platform,
lockfile, migration, configuration, route, resource, event-taxonomy or
Content-package changes.
