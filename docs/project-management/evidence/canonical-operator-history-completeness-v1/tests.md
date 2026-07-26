# Tests

Package revision base: `ab2546b1a0fdd577faba895755a3d6c44f0f9da8`.

Passed with PHP 8.3.31:

- `composer validate --strict`;
- focused `AuditHistoryAdminTest`: 5 tests, 41 assertions;
- `composer run quality:gate`;
- PHPStan: no errors;
- complete package test command: 7 PHPUnit tests, 56 assertions plus all
  standalone contract/runtime tests;
- metadata sync, evidence boundary and scope checks.

The package MySQL test remains explicit opt-in and was skipped by the package
quality gate. Current exact-revision MySQL and sealed-runtime acceptance belong
to the parent Canonical Operator RC evidence, not this read-model-only patch.
