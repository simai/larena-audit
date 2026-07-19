# Tests

Runtime:

- PHP `8.3.31`;
- Composer `2.7.1`;
- file-backed SQLite;
- isolated disposable local MySQL schema with allowlisted generated name.

Verified commands:

```text
composer validate --strict
composer run validate:larena
composer run larena:metadata:check
composer run lint
composer run analyse
composer run test
composer run test:connection-bound
LARENA_AUDIT_MYSQL_ENV_FILE=<ignored-0600-file> composer run test:mysql-connection-bound
composer run scope:check
git diff --check
```

Focused SQLite/unit coverage proves:

- exact constructor connection object is returned;
- one existing generic pipeline, one default redactor and one database sink use
  that exact object;
- a different connection object remains distinguishable and receives no row;
- returned and persisted payloads are recursively redacted;
- descriptor mismatch, forbidden payload, JSON failure and database failure
  propagate without a synthetic result;
- transaction nesting is unchanged;
- caller rollback removes the row and caller commit preserves it;
- committed state survives purge and reconnect.

Provider coverage proves:

- contract and concrete bindings use the exact default `DatabaseManager`
  connection object;
- the existing generic sink and pipeline bindings remain database-backed and
  retain their prior one-sink/default-redactor composition.

The real MySQL test proves:

- exact connection identity;
- caller-owned commit and rollback;
- redacted durable payload;
- mismatch and forbidden-field failure propagation;
- reconnect persistence;
- database failure propagation after rollback;
- migration down/up/down reproducibility;
- generated schema cleanup with zero residue.

The default full suite runs the MySQL entrypoint in explicit-opt-in skip mode.
The separate required MySQL command was executed with opt-in and passed.
