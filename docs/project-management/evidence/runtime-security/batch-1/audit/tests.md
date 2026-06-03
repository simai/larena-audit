# Audit Batch 1 Tests

Date: 2026-06-03

## Commands

```bash
PATH=/Applications/ServBay/package/php/8.2/8.2.29/bin:$PATH /Applications/ServBay/bin/composer validate --strict
PATH=/Applications/ServBay/package/php/8.2/8.2.29/bin:$PATH /Applications/ServBay/bin/composer run validate:larena
/Applications/ServBay/package/php/8.2/8.2.29/bin/php tests/Unit/AuditEventContractTest.php
/Applications/ServBay/package/php/8.2/8.2.29/bin/php tests/Unit/AuditFailsClosedTest.php
PATH=/Applications/ServBay/package/php/8.2/8.2.29/bin:$PATH /Applications/ServBay/bin/composer run test
PATH=/Applications/ServBay/package/php/8.2/8.2.29/bin:$PATH /Applications/ServBay/bin/composer run lint
PATH=/Applications/ServBay/package/php/8.2/8.2.29/bin:$PATH /Applications/ServBay/bin/composer run analyse
```

## Result

Composer validation, Larena package validation, contract tests, lint and analyse pass with ServBay PHP 8.2.29.

## Environment Limitation

The package `composer.json` declares PHP `^8.3`. This machine currently has usable ServBay PHP 8.2.29 and a broken Homebrew PHP binary. Therefore PHP 8.3 runtime compatibility is not fully proven in this batch and must be rechecked before release readiness.
