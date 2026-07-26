# Smoke

Regression fixtures prove that the protected Audit history includes both
`docara_page_submitted_for_review` and `docara_page_restored`, while page body,
token and unknown payload fields are absent.

An exact-revision sealed-runtime browser retest against the real events already
recorded by the Canonical Operator scenario is required in the parent release
evidence after the Root manifest pins this package revision.

The first exact-revision browser retest found untranslated operator keys. The
package now includes and tests both English and Russian labels; the parent
sealed-runtime retest must reject any `larena-audit::admin.operations.*` text.
