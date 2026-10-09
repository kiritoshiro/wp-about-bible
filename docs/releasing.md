# Release and rollback runbook

1. Update Version/ABIBLE_VERSION in wp-about-bible.php and Stable tag/changelog in readme.txt together. Update requirements if needed.
2. Run local checks. Review the change and merge after **All security checks passed**.
3. Tag the reviewed main commit `vX.Y.Z` and push that tag. Publishing a tag is a separate deliberate release action.
4. The release workflow reruns the whole gate on that commit, requires main ancestry and version agreement, builds a deterministic allowlisted `wp-about-bible/` ZIP, and publishes it with SHA-256 and commit manifest.
5. Confirm the release run succeeded. Download the asset, compare its SHA-256 to the manifest and GitHub digest, inspect root/header, and exercise installation/update in an isolated WordPress site.
6. An authorized site owner installs/upgrades and checks the library, editor, source links and error logs. Record deployment separately from publication.

The release notes contain a generated machine-readable `abible-requirements` comment with WordPress/PHP minimums. The updater requires it and GitHub's SHA-256 asset digest. Do not delete it or manually substitute an untested package. Published releases should be immutable by policy; use a new version for corrections. CI runs on the source commit with the same deterministic package script; no development files or secrets are included.

The release workflow deliberately fails if a release already exists, rather than replacing assets. Inspect partial publication before retrying; do not overwrite a release consumed by installations.

Before upgrade: back up database and installed plugin files. Rollback: replace only the plugin with the retained previous ZIP and restore the database only if a migration requires it. Version 0.1.0 introduces no destructive migrations. If the plugin prevents admin access, use WordPress recovery mode or disable its directory through the site's administration tooling. Content is preserved on deactivation and uninstall.

The latest stable public release is consumed by the updater. Drafts/prereleases are not offered. Automatic updates remain controlled by WordPress/site policy; this plugin does not opt the site in automatically.
