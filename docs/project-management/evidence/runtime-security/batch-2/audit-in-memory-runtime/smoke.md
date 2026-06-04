# Smoke

Smoke expectations:

- Payload redaction replaces sensitive fields before sink write.
- Forbidden payload fields fail closed before sink write.
- In-memory sink receives only redacted events.
- Missing accepting sink fails closed.

Result: passed.

Evidence:

- `AuditRedactorRuntimeTest` proves sensitive fields are redacted and forbidden
  fields fail closed.
- `AuditEventPipelineTest` proves an accepting in-memory sink receives only a
  redacted event.
- `AuditEventPipelineFailsClosedTest` proves forbidden payload fields and
  missing accepting sink fail closed before sink write.
