# Smoke

Status: `passed_static_only`

Smoke coverage:

- package metadata remains valid;
- PSR-4 autoload loads `Larena\\Audit\\*` classes;
- contract files are side-effect-free;
- no forbidden migration, route, admin UI or sink implementation files exist;
- evidence and graph-sync proposal remain inside package-local evidence path.

No runtime smoke test is expected for this interface-first contract skeleton.
The package does not register routes, migrations, providers, admin UI or
production sinks in this batch.
