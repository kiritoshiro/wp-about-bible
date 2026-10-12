=== Biblijos pažinimas ===
Contributors: kiritoshiro
Tags: bible, library, education
Requires at least: 6.6
Requires PHP: 8.3
Stable tag: 0.1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lithuanian Bible research library with source attribution and structured references.

== Description ==
Publications, topics, Bible books, authors, source metadata, private editorial notes,
search/filter shortcodes and validated GitHub release updates. No production content is seeded.

== Installation ==
Upload wp-about-bible.zip. Activate. Open Biblijos biblioteka > Nustatymai.
Create the optional landing-page draft, review it, and publish when ready.
Use [abible_home] or [abible_library] on an ordinary page.

== External services ==
WordPress update checks contact api.github.com for public release metadata and GitHub
release asset hosts when installing updates. No publication content is sent.
An optional ABIBLE_GITHUB_TOKEN constant raises the rate limit; it is sent only to
api.github.com. GitHub terms: https://docs.github.com/en/site-policy/github-terms/github-terms-of-service
GitHub privacy: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement

== Changelog ==
= 0.1.1 =
The release package ships minified scripts and styles (SWC and lightningcss, pinned
build tools). Behaviour is unchanged; the repository keeps the readable sources.

= 0.1.0 =
Initial library/editor foundation, secure updater, release packaging and security/runtime gates.
Library listings no longer show excerpts of password-protected publications. The updater
caches invalid release metadata, stores only verified fields, always requires the SHA-256
digest and hides the requirements marker in update details. The topic portal loads all
topic terms in one query.
