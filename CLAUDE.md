# Docs site on ZSF

A documentation-site template for **one** framework or library, built on the ZSF PHP framework
(`stoic/web`, PHP 8.4, Vue 3 front-ends). Clone, point it at one project, customize, deploy.
Example content named "tessel" (`createQuery`, `staleTime`, etc.) is fictional fixture data.

## Decisions already made (ask before changing)

- **Tenancy:** one install per documented library. No `library_id` anywhere.
- **Database:** MySQL only. `migrations/db` is committed directly; pgsql/sqlsrv support was removed.
- **PrimeVue:** unstyled mode in the public site. PrimeVue supplies behavior (Dialog, Tree, focus, keyboard);
  all visuals come from our own CSS built on design tokens. No Aura/Lara theme on the public site.
- **Version in URL:** library version is a URL segment (`/v4.2/...`), never only client state. `latest` redirects.
- **Language / package manager:** remembered client preferences (localStorage), not URL. They select which
  code-sample variant renders.
- **Cards** are always full content width. No multi-column card grids.
- **Contract is data:** signatures, params, defaults, returns, throws, since/changed/removed are structured data
  per symbol per version (`symbol_versions` with version ranges), not prose.
- **Search:** static per-version index generated at build time, searched client-side. Not DB full-text.
- **Removed symbols** render struck through rather than disappearing.
- **No AI-answer row** in search. **No "Edges" row** (gotchas / next-open / issues) on symbol pages.
- Learn mode ships **without live code execution** first.

## Design concept

Four reader modes (Diátaxis): Learn, Do, Reference, Explain, as header tabs. A persistent context bar
(version, language, package manager) drives every code sample, signature, default, and search result.
Symbol page = left rail (trail + module siblings + Browse ⌘B), center prose, right dark **contract pane**
that follows scroll via IntersectionObserver on `data-symbol-ref` elements. ⌘K = search palette,
⌘B = browse-tree palette. Upgrade diff view compares two versions. Mobile: contract becomes a bottom sheet.

Visual system: warm off-white ground, near-black ink, one blue accent (new/added/focus/after), one orange
(changed/breaking/before), dark pane for machine-truth content. IBM Plex Sans / IBM Plex Mono / Newsreader.
Real `<button>`/`<a>`/`<input>`+`<label>`, 44px touch targets, 4.5:1 contrast. Dark theme not yet designed.

## Layout

- `api/1.1/*.api.php` — controllers (`Api1_1` namespace, extend `Zibings\ApiController`, register endpoints in
  `registerEndpoints()`, swagger-php annotations). Auto-loaded by `web/api/1.1/index.php`.
- `inc/classes/*.cls.php` models (`StoicDbModel`), `inc/repositories/*.rpo.php` collections.
- `migrations/db/{up,down}` SQL, `migrations/cfg` config migrations.
- `ui/` is a pnpm workspace: `front` (public site: Vue 3, PrimeVue 4 unstyled, vite-ssg; `pnpm zsf` pre-renders
  every route into `web/`), `admin` (authoring UI: Vue 3, PrimeVue 4 styled with the Aura preset re-colored from the
  tokens; `pnpm zsf` builds into `web/admin/`), `shared` (Markdown directive renderer, highlighter, API types, design
  tokens; imported by both apps as `@docs/shared` through regex aliases in each app's Vite config).
- `api/1.1/Docs.api.php` is the public read API; `api/1.1/DocsAdmin.api.php` is the RESTful authoring API
  (GET/POST/PUT/DELETE, Administrator only) over `DocsAdminWriter`; bundles import/export via `DocBundleImporter` /
  `DocBundleExporter` (`fixtures/README.md` documents the format).
- Landing page (`ui/front/src/views/HomeView.vue`, route `/:version`; `/` redirects to `/latest`): hero from the one
  page in the reserved `home` mode (slug `index`: summary is the tagline, body is optional prose with `<<sample:…>>`
  embeds for install lines per package manager and a first example per language), then generated sections (mode
  cards from the manifest and tree, "New in" from the diff against the previous version). `home` is a storable page
  mode but never a header tab (`DocPageModes::readerModes()`), never in search, and never in "also covered in".
- Learn mode (`ui/front/src/views/LearnView.vue`, `components/learn/*`, `learn/steps.ts`): course spine, prose,
  one "Try it" step at a time, and a static dark workspace whose file tabs are the lesson's **titled** code samples and
  whose log comes from each step's `expectedOutput`. Progress lives in localStorage (`stores/progress.ts`, keyed by
  version and course); nothing runs in the browser. The Learn index reads `courses` from the build manifest.
- Responsive breakpoints: at 1180px and below the three-column layouts go single column (rail and pane become
  bottom sheets), the header's context bar moves into the chip row and search becomes an icon while the mode tabs
  stay; at 768px and below the tabs collapse into a mode-label button that opens Browse on that mode's tab. Every
  tappable control gets a 44px hit area (`--touch-target`), even when the visual is smaller. Use `100dvh` alongside
  `100vh` and pad fixed bottom sheets with `env(safe-area-inset-bottom)`.
- Vue views must derive state with `computed`, not `watch`, when it has to exist in pre-rendered HTML (watchers do
  not flush during SSR).
- `web/.htaccess` turns `DirectorySlash` off so a pre-rendered `foo.html` beats a same-named `foo/` folder (mode
  indexes sit next to their page folders); folders with their own index still get the slash redirect.
- PHP tests reset the `Doc*` tables of the configured database; `tests/bootstrap.php` refuses to run unless the DSN
  names a `*_test` database, so run them through `./Exec.ps1 test`, never `phpunit` against the dev settings.
- Importers (`scripts/import-openapi.php`, `scripts/import-php.php`) emit bundles, never touch the database: each
  builds one snapshot per library version (`DocOpenApiExtractor`, `DocPhpExtractor`) and `DocSnapshotMerger` folds
  them into contract ranges (a new contract row when signature, params, returns, throws, or status change; summary
  and source location just update). OpenAPI: module per tag plus `{prefix}/schemas`, `endpoint` symbols named by
  operationId. PHP: module per namespace, public members as children, `@internal` hidden. `scripts/scan-usage.php`
  (`DocUsageScanner`) writes the scan report the upgrade view loads (`{tool, package, fromVersion, imports}`), reading
  namespaces and the installed version from the consumer's `composer.lock`. Follow-ups not done: replacing `web/sui`
  with the docs site, a JavaScript usage scanner, pages generated from OpenAPI descriptions.
- `.claude/skills/docs-bundle/` — agent-facing guide for writing a bundle for any project: `SKILL.md` (workflow),
  `reference/bundle-format.md` (every field the importer reads and every Markdown directive, derived from the code),
  `reference/writing-guide.md` (Diátaxis rules per mode), `scripts/validate-bundle.mjs` (no-dependency validator).
  Keep the format reference in step with `DocBundleImporter` and `ui/shared/markdown/render.ts` when they change.
- `docker/` + `Exec.ps1` — dev loop (`./Exec.ps1 init|update|test|stop|down`). Web :8080, adminer :8081.

## Working agreements

- Proven tech in production. Blunt tradeoff discussion. Correctness over speed. Flag disagreements rather
  than silently choosing.
- Ask before adding new runtime dependencies. Run pnpm commands with `pnpm --dir <pkg>` (absolute paths); the
  shell's working directory drifts between calls.
- Verify claims about ZSF against the code before relying on them.
