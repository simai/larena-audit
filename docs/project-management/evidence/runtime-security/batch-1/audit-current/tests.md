# Tests

Status: `passed`

Executed commands:

```bash
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer validate --strict
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer dump-autoload
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run quality:gate
git diff --check
```

Observed results:

- `composer.json is valid`
- Composer autoload files generated successfully.
- `validate-larena-package`: `Larena Audit coding launch context is valid.`
- PHP lint checked scripts, tools, `src` and `tests` with no syntax errors.
- PHPStan analysed scripts, tools, `src` and `tests` with no errors.
- `AuditEventContractTest passed.`
- `AuditFailsClosedTest passed.`
- Evidence contract passed for the current repository state.
- Scope check passed for the launch allowed files and evidence path.
- `git diff --check` passed.

Semantic checks:

- contract test creates a valid `AuditEvent`;
- fail-closed test rejects an empty required event field;
- lint checks scripts, tools, src and tests;
- PHPStan analyses scripts, tools, src and tests;
- scope check rejects changes outside launch allowed files and evidence path.
