# Biblijos paÅ¾inimas

A Lithuanian WordPress Bible research library for Adventistai. Repository: https://github.com/kiritoshiro/wp-about-bible

## Initial implementation

- Gutenberg publications with topics, canonical Bible books and credited authors.
- Source/translator/reviewer fields, document status, presentation form and normalized passage references.
- Private editorial/permission notes, excluded from public REST responses and frontend rendering.
- `[abible_home]` and `[abible_library]`, eight illustrated topic cards, keyword search, topic/book filters and pagination.
- Theme-compatible archives, article metadata, heading contents, source links and print styles.
- Study-guide block pattern and optional landing-page **draft** creation. No fabricated articles or automatic public-page/menu changes.
- Native WordPress updates from verified GitHub release assets; mandatory SHA-256, bounded download, strict URLs and credential isolation.
- Same-commit security/runtime gate and reproducible ZIP releaser.

This is a functioning **0.1.0 foundation**, not the whole editorial roadmap. See [the researched product plan](docs/product-plan.md). Verse lookup, dedicated glossary/path management, multiple source records, related-reading curation, media production and actual reviewed content remain future work. Existing `wp-bible` is a separate optional verse-popup plugin; this plugin does not replace or require it.

## Install and operate

Requirements: WordPress 6.6+, PHP 8.3+. Install `wp-about-bible.zip` from a GitHub release; do not install GitHub's automatic source archive. Activate, then visit **Biblijos biblioteka â†’ Nustatymai**. Create the optional landing-page draft, review it, and publish when ready. You can also put the shortcodes in any ordinary page.

The homepage leads with eight topic cards, including topics awaiting publications. Replace a card illustration under **Biblijos biblioteka → Temos → Paveikslėlis** using the WordPress media library; bundled original SVG illustrations are the defaults. Search stays inside the library, with only topics and Bible books as filters.

The homepage leads with eight topic cards, including topics awaiting publications. Replace a card illustration under **Biblijos biblioteka > Temos > Paveikslelis** using the WordPress media library; bundled original SVG illustrations are the defaults. Search stays inside the library, with only topics and Bible books as filters.

Publications are edited in the standard block editor. Add credited authors independently of the WordPress account that enters the content. For source translations/summaries/adaptations, record the original HTTPS URL. Adopted documents require an evidence URL. Save additional references to reveal more rows, up to 20. Reference-derived book classifications are appended; removing a reference does not remove a deliberately assigned book termâ€”review that classification manually.

Metadata validation preserves previously saved metadata on invalid input and shows an editor-only notice. WordPress may still save the article body; metadata validation is not a publication-approval system. The editorial team remains responsible for review before publishing. Private metadata is edited only in admin panels, not the public REST API. References validate structure, not edition-specific verse counts.

For richer guides, use the **Biblijos tyrinÄ—jimo vadovas** block pattern. Learning paths and a glossary can be ordinary pages until dedicated management is added. The theme can override archive output through `abible/archive.php`; CSS uses scoped selectors and inheritable fonts. Production ALPS integration still requires a site-specific check.

## Updates and releases

Public updates need no token. Optionally define `ABIBLE_GITHUB_TOKEN` in `wp-config.php` as a repository-scoped Contents: Read token. Never place it in plugin source or the options database. Only the GitHub API receives the token; CDN requests are unauthenticated.

Dashboard â†’ Updates â†’ **Check again** clears the release cache for authorized administrators. Settings reports the latest metadata-check outcome. Draft/prerelease/malformed releases and releases without required metadata/digest are ignored. A failed download returns a safe WordPress error and preserves the installed plugin.

See [release and rollback runbook](docs/releasing.md), [security controls](docs/security.md), and [error handling](docs/errors.md).

## Development

```sh
composer install --working-dir=.github/security --no-scripts --no-plugins
.github/security/vendor/bin/phpcs --config-set installed_paths ../../wp-coding-standards/wpcs,../../phpcsstandards/phpcsextra,../../phpcsstandards/phpcsutils
.github/security/vendor/bin/phpcs --standard=.github/security/phpcs.xml .
npm ci --ignore-scripts --no-audit --no-fund  # pinned SWC + lightningcss; package.py minifies the ZIP's assets
python scripts/package.py
python scripts/prepare-runtime.py
python .github/playground/smoke.py --php 8.4 --wp latest
```

The integration suite runs against the **packaged plugin** in a disposable WordPress Playground. It covers authorization, private data, metadata rejection, ranges, filtering, and mocked updater transport. CI additionally runs WordPress Plugin Check, PHPCS, Semgrep, TruffleHog, Trivy, zizmor and actionlint. Required scanners fail closed. The final check is **All security checks passed**. No CodeQL PHP coverage is claimed.

All production content is retained on deactivation/uninstall. Remove unwanted publications explicitly through WordPress after a backup. License: GPL-2.0-or-later. Updater and CI baseline adapted from the owner's `wp-bible` repository; see commit provenance in the verification record.
