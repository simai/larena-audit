# Smoke

The executable focused smoke is:

```text
composer run test:connection-bound
```

It constructs the real database pipeline on a file-backed SQLite connection,
routes redacted events, proves caller rollback and commit behavior, verifies
the provider alias and checks that the prior generic bindings remain intact.

The real database smoke is:

```text
LARENA_AUDIT_MYSQL_ENV_FILE=<ignored-0600-file> composer run test:mysql-connection-bound
```

The MySQL harness accepts credentials only from an explicit absolute,
repo-local ignored and untracked file with private permissions and a local
host. It creates one randomly named allowlisted schema, refuses an existing
schema, performs the acceptance sequence and removes the schema in a
synchronous `finally` block with a shutdown fallback.

No existing application database, `larena.test`, live service or production
runtime is read or mutated.
