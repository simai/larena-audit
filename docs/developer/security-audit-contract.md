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
