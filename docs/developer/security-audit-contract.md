# Security Audit contract

Larena Audit treats an `AuditEventDescriptor` as the registered contract for an
event. Before sink selection or persistence, the pipeline requires the event to
match the descriptor's source package, category, type, severity, and retention
class exactly. A mismatch raises an `InvalidArgumentException`; no sink receives
the event.

Payload protection applies recursively to associative arrays and list items.
At every depth, forbidden field names reject the entire event and redacted field
names are replaced with `[redacted]`. Forbidden fields take precedence if a
field appears in both lists. Empty or non-string protection field names and
payload nesting deeper than the supported safety limit fail closed.

The contract intentionally covers array payloads only. Producers must normalize
domain objects before creating an `AuditEvent` and must not put credentials,
invitation tokens, session tokens, MFA secrets, passwords, or recovery material
in an event payload.

Security-sensitive producers should test all three boundaries:

1. descriptor/event metadata mismatch never reaches a sink;
2. nested forbidden fields reject the event;
3. nested redacted fields reach persistent storage only as `[redacted]`.

## Connection-bound durable pipeline

Integrations that require same-transaction durability must resolve
`Larena\Audit\Contracts\ConnectionBoundAuditEventPipeline`. Its default
implementation is `Larena\Audit\Runtime\DatabaseAuditEventPipeline`.

The contract exposes:

```php
public function connection(): ConnectionInterface;

public function route(
    AuditEventDescriptor $descriptor,
    AuditEvent $event,
): AuditEvent;
```

`connection()` returns the exact object passed to the implementation. The
implementation composes the existing generic pipeline with
`DefaultAuditRedactor` and exactly one `DatabaseAuditSink` on that same
connection.

Before opening a caller-owned transaction, an integrating coordinator may use
strict object identity to reject mismatched database participants. A
connection name, driver name or equivalent configuration is not sufficient
proof that writes share one active transaction.

The Audit package does not begin, commit or roll back the transaction. A
successful route participates in the caller's current transaction. Caller
rollback removes the Audit row and caller commit preserves it. Descriptor,
forbidden-field, redaction, JSON and database failures propagate without a
synthetic success result.

The connection-bound contract intentionally has no arbitrary sink injection,
fan-out, fallback or external transport. External delivery requires a separate
outbox contract and cannot be represented as same-connection durability.

The generic `AuditEventPipeline` remains supported for existing integrations,
but it does not expose or prove database connection identity.
