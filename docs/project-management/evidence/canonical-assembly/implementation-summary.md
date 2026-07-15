# Implementation summary

Status: implementation written and package-verified on 2026-07-15.

The isolated package branch starts from `b5f6d215fb020f7b8b071cc40b7dde4e2ed2cea1`. The correction removes the invented caller-supplied `read-only` prop from the Audit history table and expresses the supported read-only behavior with the locked `selectable=false`, `settings=false`, and `actions=false` props. The source-backed Simai Framework prop validator remains strict.
