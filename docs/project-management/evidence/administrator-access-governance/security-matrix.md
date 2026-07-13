# Security matrix

| Boundary | Accepted result | Rejected result | Evidence |
| --- | --- | --- | --- |
| Descriptor identity | All five routing fields match | Any source, category, type, severity, or retention mismatch | `AuditEventPipelineFailsClosedTest.php` |
| Sink routing | Validated event reaches an accepting sink | Mismatch or forbidden payload reaches no sink | `AuditEventPipelineFailsClosedTest.php` |
| Nested forbidden field | No forbidden key at any array depth | Entire event rejected before persistence | `AuditRedactorRuntimeTest.php` |
| Nested redacted field | Sensitive value becomes `[redacted]` | Raw value is never handed to persistent storage | `AuditRedactorRuntimeTest.php`, `DatabaseAuditSinkTest.php` |
| Invalid protection definition | Non-empty string field names | Empty or non-string name fails closed | `DefaultAuditRedactor.php` |
| Excessive nesting | Payload within bounded recursion limit | Deeper nesting fails closed | `DefaultAuditRedactor.php` |
