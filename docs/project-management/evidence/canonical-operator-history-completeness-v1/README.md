# Canonical Operator Audit history completeness v1

This bounded correction makes already-recorded Docara submit-for-publication
and revision-restore lifecycle events visible on the protected Audit history
screen. It does not change event taxonomy, persistence, routes, authorization,
or migrations.

The operator projection continues to use a strict safe-field allowlist. Raw
payloads, page bodies, credentials, tokens, file contents and unknown fields
never reach the view.

Nonclaims remain explicit: `production_ready=false`,
`frontend_complete=false`, and `all_42_packages_ready=false`.
