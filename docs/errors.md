# Error handling and triage

| Condition | Behavior | Maintainer action |
|---|---|---|
| Invalid editorial field or passage | Retain old metadata; show per-user notice. Article body can still save. | Correct the field, re-save and verify; review publication status separately. |
| Unauthorized write | No metadata mutation; setup returns 403. | Verify user role/capabilities; do not disable authorization. |
| No search results | Normal empty-state guidance and clear filters. | Broaden query; check publication status/classification. |
| GitHub timeout/rate limit/no release | No update offered, short negative cache, admin-only diagnostic. | Check repository release and outbound access; use Check again after recovery. |
| Missing digest/invalid URL/requirements | Fail closed and offer no package. | Correct release through reviewed workflow. |
| Bad redirect, corrupted or truncated download | WP_Error; temporary file deleted; existing plugin untouched. | Retry a verified release. Never bypass checksum validation. |
| PHP warning/notice/deprecation/fatal in plugin runtime | CI smoke gate fails. | Reproduce with same WP/PHP version; add regression and fix. |
| Scanner cannot execute | Required gate fails. | Repair tool/config/network issue, then rerun. |

Do not install a global PHP exception handler or mask unrelated WordPress errors. Use WordPress recovery behavior; on production keep display_errors off and logs access-controlled. Never log tokens, source bodies, private notes or request payloads. The updater stores only a generic metadata-check status/time, not raw remote errors. Download failures are returned through WordPress's upgrader UI.

Workflow: reproduce in a disposable site → record version/commit and sanitized symptom → determine severity → regression test → fix → full gate → checked release → site-owner install and verification. Report an ordinary bug through the issue template; use SECURITY.md for sensitive issues. CI does not automatically open public issues containing logs or send external notifications.

WordPress publication metadata is not a database transaction. Validation is all-or-nothing before field writes, but storage failure recovery relies on database backups and revisions. Keep backups before site upgrades. The initial plugin has no destructive migrations.
