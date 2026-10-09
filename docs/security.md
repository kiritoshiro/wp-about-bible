# Security and delivery controls

## Boundaries

Publishing uses WordPress's existing post capabilities. Admin metadata writes require both `edit_post` for the target and a verified nonce; setup requires `manage_options` and a nonce. No public write/import/upload endpoint is introduced. Private editorial/permission fields are not REST-exposed. Public output uses contextual escaping; search uses WordPress queries and allowlisted taxonomies.

References are capped at 20, text fields at 8,000 bytes, filter text at 200 characters and pages at 10,000. Source links accept validated public HTTPS URLs but are not fetched by the plugin. Server-side update requests use fixed repository endpoints, reject redirects except a validated GitHub CDN hop, and bound response/package sizes. Required release digests are verified before WordPress unpacks an archive. Credentials are never logged or forwarded to CDN hosts.

## Required gate

`security-gate.yml` owns PR, main-push, weekly and manual triggers. Release invokes that same workflow on its tag commit. Baseline and runtime reusable workflows must both succeed; failed, cancelled or skipped jobs fail **All security checks passed**.

Baseline: PHPCS WordPress security/prepared-SQL checks, Semgrep PHP rules, non-verifying redacted TruffleHog scans, Trivy HIGH/CRITICAL dependency/config checks including unfixed findings, zizmor, actionlint. Runtime: PHP lint, version/package consistency, WordPress Plugin Check, disposable packaged-plugin regression and page/error checks on minimum WordPress/PHP and current WordPress/PHP 8.4. Scanner execution errors fail, not silently pass.

Scanner actions are SHA pinned. Downloaded TruffleHog and actionlint binaries have pinned checksums. Composer QA dependencies are locked and never shipped. Review updates through Dependabot. Semgrep registry rules are fetched at execution time; record this external-rule dependency when interpreting results.

Tests include invalid and malicious input fixtures. No authenticated production crawling, server configuration, Cloudflare, or Wordfence changes occur. The gate does not establish production security or replace code review.

## Repository configuration

Require **All security checks passed** on main, prevent force-push/deletion, and require reviewed pull requests once repository initialization is complete. Protect `v*` tags through an available repository ruleset. These server-side settings require verification separately from committed YAML.

Do not bypass a scanner on failure. Classify scanner outage vs finding, reproduce locally, fix the cause, and rerun. A narrowly scoped exception must document owner, evidence, mitigation and expiry; no blanket ignore of unfixed vulnerabilities.
