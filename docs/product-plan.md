# Adventistai Bible Library — revised plugin plan

Research and planning date: 9 October 2026.

## Current navigation decision (user revision, 9 October 2026)

This decision supersedes conflicting navigation/filter proposals below. Start with a small, curated educational portal. The homepage shows eight image-led topic cards: history/canon, manuscripts/transmission, biblical languages, translations, hermeneutics/methods, book commentaries, revelation/inspiration/authority, and study tools. Each opens its introduction and publications. Show all eight directions even when content is still being prepared; never fabricate counts or articles. Editors can replace each bundled original illustration with a licensed image from the media library.

Keep topic and Bible-book browsing. Remove difficulty and publication-type taxonomies and filters. Search and the two useful filters belong inside the library. Retain document authority and original/translation/adaptation attribution as article metadata because these describe the source honestly. Existing unused legacy taxonomy records are preserved, not deleted.

Implementation includes this navigation, source metadata, security gate, release builder and updater. The longer roadmap below remains planning, not a claim that every feature is implemented.


Public name: **Biblijos pažinimas**. Plugin working name: **Adventistai — Biblijos biblioteka**.

This revision incorporates a targeted review of public Adventist research, reference, and learning resources. It supersedes the earlier plan where priorities differ. It is a proposed specification, not implemented software. Public pages and publisher descriptions were reviewed; enrolled course experiences, APIs, reuse permissions, and the installed adventistai.lt software configuration were not verified.

## 1. What the international examples contribute

| Resource | Observed structure and emphasis | Decision for Adventistai |
|---|---|---|
| [BRI article library](https://adventistbiblicalresearch.org/articles) | Search and filters by category, topic, author, and Bible book/chapter/verse. | Make structured references and attribution foundational; deliver book filtering first and passage lookup next. |
| [BRI resource hub](https://adventistbiblicalresearch.org/) | Distinct destinations for articles, statements/documents, releases, Reflections, reference resources, and books. | Distinguish publication series, document status, and subject instead of mixing them into one category list. |
| [International Biblical-Theological Dictionary](https://dictionary.adventist.org/) | Search, alphabetical browsing, individual entries; explicitly describes continuing expansion. | Add a modest Lithuanian glossary and communicate actual coverage. |
| [Andrews Bible Commentary / Study Bible](https://www.universitypress.andrews.edu/store/Religion/Andrews-University-Press/Andrews-Bible-Commentary-and-Andrews-Study-Bible-%28NKJV%29-Hardcover/Book/2_267.action) | Book introductions, outlines, notes, thematic links, maps, chronology, and practical application; intended for multiple reader levels. | Give book introductions a consistent template and connect passages to context and broader themes. |
| [Bibleinfo](https://www.bibleinfo.com/en) | A Voice of Prophecy website organized around common questions and Bible topics, with study and question-submission routes. | Create an editorial Q&A publication type that links short answers to deeper treatments. |
| [Hope.Study](https://hope.study/en/) | Numbered foundation series; course cards show lessons and estimated duration; multilingual routes. | Make learning paths ordered, finite, and clear about effort. |
| [Hope Kurse: Das Wort, Germany](https://hopekurse.de/kurse/das-wort) | A 12-topic Bible course combining videos and exercises, with independent or personally supported study. | Include worked exercises and reflection prompts. Consider human support only when someone can staff it. |
| [Novo Tempo course catalogue, Brazil](https://lps.novotempo.com/cursos/?idioma=pt) | Multiple themed courses, including Bible introductions, archaeology, and an audio course designed for visually impaired learners. | Support useful alternative formats and transcripts; keep the local library's subject boundary focused on the Bible. |
| [Believe His Prophets](https://www.revivalandreformation.org/bhp) | A five-year Bible and selected Ellen White reading program, daily chapter access, and printable schedules. | Add a modest reading pathway and links to related resources; keep supplementary writings explicitly labelled. |
| [Adult Sabbath School archive](https://www.adventistarchives.org/sabbathschoollessons) | Historical resources organized by year, quarter, subject, and author; includes the 2020 second-quarter How to Interpret Scripture guide. | Catalogue relevant existing studies with series/lesson metadata and link to authorized local material. |

The selected examples span global church resources, international scholarship, North American ministries, Germany, and Brazil. They are a representative benchmark, not an exhaustive survey of every Adventist division.

## 2. Editorial identity and method

Publish a short, accessible **Kaip tyrinėjame Bibliją** page and a fuller editorial policy.

The General Conference's adopted [Methods of Bible Study](https://gc.adventist.org/documents/methods-of-bible-study/) supplies a relevant denominational foundation. It emphasizes Scripture's authority and divine-human character, prayer, contextual and literary reading, language and historical study, comparison across Scripture, Christ, and application. It cautions against constructing doctrine from one translation and against imposing preconceived conclusions. It also permits acknowledging unresolved difficulties. Its theological objections to historical-critical presuppositions should be explained accurately, without equating all historical research with those presuppositions.

Proposed article standards:

- Identify the question or passage under examination.
- Explain the argument using evidence and references.
- Distinguish historical observations, interpretive conclusions, and adopted church teaching.
- Identify material disagreement or uncertainty when relevant.
- Keep translations faithful; label editorial additions separately.
- Include practical application where appropriate, without adding devotional conclusions to every technical reference entry.

Source institution is not a substitute for document status. An individually authored BRI article is not automatically an adopted world-church statement.

Ellen White material may appear as clearly attributed supplementary commentary where relevant. It should remain distinguishable from the biblical text and from the author's analysis.

## 3. Visitor navigation

Primary destinations:

1. **Pradėkite čia** — introductions and recommended first readings.
2. **Biblijos knygos** — book introductions and available commentary.
3. **Temos** — subject library.
4. **Kaip tyrinėti** — methods, exercises, and learning paths.
5. **Klausimai ir atsakymai** — concise explanations leading to detailed resources.

Secondary utilities: library search, **Žodynėlis**, **Šaltiniai ir metodika**.

Retain the original subject areas: history/canon, manuscripts/transmission, languages, translations, interpretation, commentaries, inspiration/authority, and tools. Add historical/cultural background as a distinct subject where content supports it. Genre is a separate controlled attribute, not another competing top-level menu.

Prophetic interpretation belongs within methods and relevant book pages. Lifestyle and general church news remain in their existing site sections.

## 4. Landing page layout

Retain the site's header/footer and established design language.

1. Title and one-sentence introduction.
2. Labelled search field with examples limited to supported functionality.
3. Three starting cards: **Susipažinti su Biblija**, **Išmokti tyrinėti**, **Rasti ištraukos paaiškinimą**.
4. One featured learning path with steps and approximate effort.
5. Three useful questions with links to answers.
6. Available topic cards.
7. Selected book introductions and publications.
8. A compact methodology/source-policy link.

Show only useful, populated destinations. Use editorial selection on the landing page, rather than making publication date its organizing principle.

## 5. Reading templates

### Publication

Breadcrumbs → topic/type → title → summary → attribution → relevant passages → contents → body → notes/sources → next reading.

Provide separate original and local publication dates, translator/reviewer credits where appropriate, stable heading anchors, and footnote return links. A visible label distinguishes original article, full translation, editorial summary, or adaptation.

### Book introduction

Use the following sections consistently: overview; authorship/date with relevant qualifications; historical setting; outline; literary genres; main themes; place in the biblical story; suggested starting passage; available studies; sources.

Prepare a small number thoroughly. All biblical books can exist as internal classification terms without generating empty public encyclopedia pages.

### Q&A

Question → brief answer → passages to read → fuller explanation → limits or differing interpretations where relevant → further reading.

Q&A is a publication classification using the same underlying content type, not a separate database system. No public submission form until editorial ownership and response expectations are agreed.

### Practical study guide

Reading assignment → observation questions → context/genre → comparison and language notes → interpretive reasoning → application → further reading.

Use an accessible reflection/exercise block pattern. Visitors can study without creating accounts or submitting personal answers.

### Glossary

Begin with an alphabetically arranged page of approximately 15–20 short, reviewed definitions and stable anchors. Link terms manually from introductory articles. Initial candidates: canon, manuscript, textual variant, translation, context, genre, hermeneutics, exegesis, typology, apocalyptic, inspiration, Septuagint, and concordance.

A later dedicated entry type can add synonyms, transliteration, individual URLs, and links to publications. Avoid ambiguous automatic keyword linking in v1.

## 6. WordPress content model

Use one custom publication type initially. Keep standard content editing, drafts, previews, and scheduling.

| Data group | Proposed representation |
|---|---|
| Body, title, summary | WordPress publication content and excerpt |
| Subject, biblical book, level, publication type | Controlled taxonomies |
| Genre and series | Controlled classification, exposed only where useful |
| Original author(s) | Reusable contributor terms with optional biography; separate from WordPress login authors |
| Translator/reviewer/institution | Structured attribution fields |
| Original title, language, URL, date, issue, pages | Registered source metadata |
| Document status | Adopted church document, institutional resource, signed scholarly interpretation, local editorial guide; evidence required for adopted status |
| Local presentation | Original, translation, summary, adaptation |
| Biblical references | Repeatable normalized book/chapter/verse ranges and relationship |
| Related resources | Selected local publication IDs or existing resource URLs |
| Review/permission notes | Private metadata with restricted access |

Document status describes provenance, not a numerical truth or quality score. Peer-review status is a separate optional field and must be verified.

Store whole-book, whole-chapter, and verse-range references without artificial verse values. Distinguish a passage substantially discussed from one merely cited. Define the numbering convention and exceptions before implementing range validation.

Translation records must support several sources and several contributors. A full multilingual website is outside the first release.

## 7. Admin experience and workflow

Menu: **Biblijos biblioteka**, **Visos publikacijos**, **Pridėti publikaciją**, **Temos**, **Biblijos knygos**, **Autoriai**, **Nustatymai**.

Editor panels group classification, attribution, sources, passages, related resources, and private editorial notes. Publication rows expose readiness indicators: summary missing, source incomplete, review pending.

Workflow: select material → record source and intended use → draft/translate → subject review → Lithuanian language review → metadata/link check → publish.

Use native draft and pending-review states with private checklists initially. Define actual publishing permissions after identifying the site's editorial team. Test save/autosave/revision behavior for both the body and custom metadata; do not assume every custom field automatically participates in revisions.

## 8. Search and relationships

**Release 1:** title/summary/body search; filters by topic, book, difficulty, and publication type; contributor archive/filter where populated; shareable filter URLs; pagination and clear empty results.

**Release 1.1:** query parsing for Lithuanian book names/abbreviations and references such as `Pr 1,1–5`; overlap matching; substantial discussion ranked above incidental citation. Establish examples of chapter boundaries and range overlap as automated tests.

Only display verse-search examples after that feature ships. Optimize using measured data; create a reference index table if needed rather than prematurely replacing WordPress storage.

Model relationships explicitly: explains a term, discusses a passage, belongs to a path, translated from a source, and related resource. Use editorial relationships first; similarity suggestions must not override them.

## 9. Integration and reuse

Inventory current Lithuanian resources before commissioning new translations. Link to existing Sabbath School studies, videos, and theological articles wherever that avoids duplication.

For each external item, record source, language, presentation form, intended reuse, permission status, and last link check. Publication requires the appropriate reuse basis to be documented for copied or translated material. Pending items can remain internal candidates; linking and republishing are distinct choices.

Do not assume an API, feed, synchronization agreement, or translation permission from a publicly accessible website. Curated links and local editing are the baseline integration.

Keep existing URLs for resources that stay in place. When moving content, map and verify redirects. Reserve `/biblija/` for the landing page, with separate article, topic, and book prefixes after conflict checks.

## 10. Revised delivery roadmap

### Release 1 — usable foundation

- Publication type, structured references, attribution, source and status fields.
- Library, publication, topic, and populated book templates.
- Keyword search and basic filters.
- Three complete book introductions, four foundation/method articles, four Q&A articles, and three deeper studies: approximately 14 publications.
- One five-step learning path reusing those publications.
- A compact glossary page and methodology page.
- Responsive reading, keyboard access, usable footnotes, and print styling.

The 14-item mix is a planning target, not a claim that those texts are already available or licensed. Final selection follows the inventory.

### Release 1.1 — retrieval and learning improvements

- Chapter/verse lookup and reference-aware ranking.
- A second learning path and reusable ordered-path management if needed.
- Dedicated glossary entries when editorial capacity supports growth.
- Better links between terms, passages, and related materials.
- Optional teaching/discussion notes attached to selected guides.

### Later, demand-led

- Audio versions with transcripts.
- Maps and historical timelines with documented sources.
- Licensed translation comparison.
- Human-supported study referrals if staffed.
- Accounts, saved progress, or full courses only after evaluating whether integration with an existing Adventist platform is preferable.

## 11. Verification and success criteria

Test responsive layouts at 320, 375, 430, 768, 1024, 1280, and 1440 CSS pixels, plus 200% zoom. Check keyboard navigation, focus visibility, contrast, Hebrew directionality, Greek/Lithuanian characters, long URLs, footnotes, media transcripts, and print output.

Verify draft/private-field protection including REST exposure; validation and permissions; filter combinations; reference ranges; save/revision behavior; redirects; empty results; cache behavior after edits; and deactivation/reactivation without data loss. Preserve content on uninstall by default. Load plugin assets only where needed.

Before launch, ask representative readers to: find a book introduction, find help with a difficult verse, locate an explanation of a term, complete a short study guide, and identify who wrote a publication and what its status is.

Evaluate successful discovery, useful next-reading clicks, learning-path use, and editorial effort. Measure only what the site's existing privacy arrangements support. Establish a baseline before setting numerical targets.

## 12. First implementation milestone

Build the publication editor and one complete publication page with real pilot content, a source record, a normalized Bible reference, footnotes, and related reading. Review that end-to-end experience before expanding to the homepage and all archives. Use a staging environment and prepare an installation package, editor guide, and rollback procedure; production deployment remains a separate action.
