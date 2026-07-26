# Implementation summary

- Added `docara_page_submitted_for_review` and `docara_page_restored` to the
  protected operator history projection.
- Added explicit operator labels without changing stored event taxonomy.
- Added English and Russian localization parity for both operator labels.
- Preserved the existing safe payload allowlist and authorization boundary.
- Added regression coverage proving both events render while sensitive and
  unknown fields remain absent.
