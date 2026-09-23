# Tests

`composer test` in `larena/audit`: 8 script tests passed; PHPUnit history
screen and pipeline binding — OK (7 tests, 56 assertions). The history screen
tests run with `larena/ui` from `require-dev`, so the guarded route still
loads when UI is present. The MySQL connection-bound test is opt-in
(`LARENA_AUDIT_CONNECTION_BOUND_MYSQL_TEST=1`) and was not enabled.
