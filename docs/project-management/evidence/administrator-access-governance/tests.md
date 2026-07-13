# Tests

Failing-before-implementation probes reproduced both defects:

- descriptor/event source mismatch was accepted by the pipeline;
- nested redacted and forbidden keys were not protected.

After implementation, the focused tests passed:

```text
AuditEventPipelineFailsClosedTest passed.
AuditRedactorRuntimeTest passed.
AuditEventPipelineTest passed.
DatabaseAuditSinkTest passed.
```

Independent review then found a missing negative case: a redacted parent could
hide a forbidden descendant. The implementation was changed to perform a full
forbidden-field pre-pass before any redaction, and a dedicated regression now
proves that `token.password` is rejected even when `token` itself is redacted.
The reviewer reran this case and the complete gate before issuing PASS.

The complete package gate passed on PHP 8.3.31:

```text
composer run quality:gate
PHPStan: No errors
Audit unit scripts: passed
AuditHistoryAdminTest: OK (5 tests, 32 assertions)
metadata sync: passed
scope check: passed
```

The first gate attempts exposed local toolchain setup issues before reaching
the package result: the default PHP 8.2 runtime had an incompatible ICU library,
and this worktree did not yet have development dependencies installed. The gate
was rerun with the repository-supported PHP 8.3 runtime after `composer install`;
neither issue required a product-code workaround.
