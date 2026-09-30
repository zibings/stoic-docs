# Docs Site on ZSF

A documentation-site template for a single framework or library, built on the
[Zibings Site Framework (ZSF)](https://github.com/zibings/zsf). One install documents one library.

This repo is MySQL-only. The upstream ZSF pgsql and sqlsrv migrations and Docker files have been removed,
and `migrations/db` is committed directly.

## Requirements

* PHP 8.4 and Composer (inside the container if you use Docker)
* Node 22+ and pnpm 11
* Docker with Compose v2 for the local dev loop
* PowerShell 7 (`pwsh`) to run `Exec.ps1`

## Local development with Docker

Initialize the containers, install Composer dependencies, apply migrations, and start both Vite dev servers:

```powershell
./Exec.ps1 init
```

This exposes:

| Service | URL |
|---|---|
| Web (`web/`) + API | http://localhost:8080/ |
| Adminer | http://localhost:8081/ |
| Front UI dev server | http://localhost:5173/ |
| Admin UI dev server | http://localhost:5174/ |
| Fake SMTP inbox | http://localhost:1080/ |

Adminer connection: host `db`, user `root`, password `P@55word`, database `zsf` (tests use `zsf_test`).

`init` writes `docker/.env` (gitignored). To skip the interactive prompts, create it first:

```
PROJECT_NAME=docsite
UI_FRONT_DOCKER=False
UI_ADMIN_DOCKER=False
SMTP_DOCKER=True
```

Other commands:

```powershell
./Exec.ps1 update        # re-run migrations against zsf_test and zsf
./Exec.ps1 test          # run phpunit against zsf_test
./Exec.ps1 stop          # stop containers and dev servers
./Exec.ps1 down          # remove containers and docker/.env
./Exec.ps1 php scripts/generate-openapi.php -v 1.1 -o ./web/sui/openapi.yaml -f yaml
./Exec.ps1 -i php scripts/add-user.php   # interactive script
```

## Manual setup without Docker

```
composer update
vendor/bin/stoic-create --site
vendor/bin/stoic-configure
vendor/bin/stoic-migrate
```

## Configure your library

Four settings in `siteSettings.json` name the library the install documents. Nothing in the UI edits them; set them
once per install with `stoic-configure` (inside the container: `./Exec.ps1 -i php vendor/bin/stoic-configure ...`):

| Setting | Used for | Example |
|---|---|---|
| `docs.libraryName` | Header logo, landing-page title, `<title>` of every page | `Tessel` |
| `docs.repoUrl` | The "Source" button on the landing page | `https://github.com/example/tessel` |
| `docs.sourceUrlPattern` | Source links in the contract pane; `{tag}` is the version tag, `{path}` and `{line}` come from each contract row | `https://github.com/example/tessel/blob/{tag}/{path}#L{line}` |
| `docs.siteUrl` | Canonical origin; enables `sitemap.xml` at build time. Leave `<changeme>` to disable | `https://docs.example.com` |

```
php vendor/bin/stoic-configure -P"docs.libraryName"="Tessel" -P"docs.repoUrl"="https://github.com/example/tessel"
```

Until `docs.libraryName` is set the site calls itself "Your Library". The context bar's languages and package
managers are not settings; they are `contextOptions` records in the content (admin UI or bundle).

## UIs

`ui/` is a pnpm workspace with three packages:

| Package | What | Dev server | Build target |
|---|---|---|---|
| `ui/front` | public docs site (Vue 3, PrimeVue unstyled, vite-ssg) | :5173 | `web/` |
| `ui/admin` | authoring UI (Vue 3, PrimeVue styled, Aura re-colored from the tokens) | :5174 `/admin/` | `web/admin/` |
| `ui/shared` | Markdown directive renderer, highlighter, API types, design tokens | — | — |

```bash
cd ui
pnpm install          # once, for the whole workspace
pnpm --dir front dev
pnpm --dir admin dev
pnpm --dir front zsf  # pre-render every route into web/ (needs the API running)
pnpm --dir admin zsf  # build the authoring UI into web/admin/
```

Authoring requires an account with the Administrator role: `./Exec.ps1 -i php scripts/add-user.php` or
`php scripts/add-user.php --non-interactive --email you@example.com --name you --password ... --make-admin`.

## Authoring in the admin UI

Everything the site renders is content in the `Doc*` tables, edited at `/admin/` or imported as a bundle. Records
reference each other, so create them in this order (the dashboard lists the same steps):

1. **Context options**: the languages and package managers the context bar offers. Sample variants can only use
   keys that exist here.
2. **Versions**, oldest first. Exactly one is marked latest; `/latest/...` redirects to it, and if none is marked
   the highest sort key wins.
3. **Modules and symbols**. Each symbol gets one contract per version range: a new row whenever the signature,
   parameters, returns, throws, or status change. Members nest under their owner (methods under a class, options
   under a type).
4. **Pages** in the four modes plus the single `home/index` landing page. A reference page links its symbol with
   the *subject* role; every other symbol a page mentions gets the *mentions* role so `{sym:...}` links resolve.
   Code samples attach to pages, one per language and, for install lines, one per package manager.
5. **Changes** per version, linked to the symbols they affect, and **courses** built from learn-mode pages.

Import/Export on the same UI round-trips all of it as one JSON bundle. Import is a non-destructive upsert on natural
keys (version label, module path, `module#Symbol`, `mode/slug`), so exporting, editing the file, and re-importing is
a safe way to make bulk changes. Only `scripts/seed-docs.php --reset` deletes content.

## Importing documentation

Content lives in the `Doc*` tables and travels as JSON bundles (`fixtures/README.md` documents the format). Seed the
fictional fixture with `php scripts/seed-docs.php`, or generate a bundle from real sources:

- `scripts/import-openapi.php` turns OpenAPI 3 documents into endpoint and schema symbols.
- `scripts/import-php.php` extracts the public API of PHP source trees.
- `scripts/scan-usage.php` scans a consuming project and writes the report the upgrade view's "only symbols my code
  imports" filter loads.

Give the importers one input per library version, oldest first, to get per-version contracts. Run them inside the web
container during development (`docker exec docsite-web php scripts/import-openapi.php --help`).

### The upgrade scan, for your readers

`scripts/scan-usage.php` is the one script meant for the library's *users*, not the site operator. Run against their
own project it writes a small JSON report (`{tool, package, fromVersion, imports}`) listing which of the library's
symbols their code imports and which version `composer.lock` has installed. On the upgrade view (`/upgrade/v4.1/v4.2`)
a "load a scan report" control takes that file and hides every change that does not touch a symbol they use. Tell
readers about it in a Do page ("See only the changes that affect my code"); nothing else on the site advertises it.
The scanner reads PHP projects today; a JavaScript scanner is a known follow-up.

To have an AI agent write a bundle for any project (any language), point it at `.claude/skills/docs-bundle/`. It is a
Claude Code skill (copy the folder into `~/.claude/skills/` and run `/docs-bundle` in the project) and also plain
Markdown any agent can follow: a workflow, the complete bundle format, a writing guide, and a dependency-free validator
(`node .claude/skills/docs-bundle/scripts/validate-bundle.mjs bundle.json`) that catches dangling references before
import.

## Releasing a new version

The version is a URL segment and every contract, page, and change is scoped to a version range, so a release is a
content change followed by a rebuild:

1. **Export** the current content from Import/Export (or keep the bundle you last imported).
2. **Add the version** to `versions`, with a higher `sortKey`, `latest: true`, and `latest: false` on the previous
   one. Keep every earlier version in the bundle so the flags stay consistent.
3. **Update contracts.** For PHP or OpenAPI libraries, run the importer with every version's checkout, oldest first,
   and let `DocSnapshotMerger` fold them into ranges. Otherwise, for each symbol whose signature, parameters, returns,
   throws, or status changed, set `removed` on its open row and add a new row `introduced` at the new version. A
   symbol that vanished keeps its last row with `removed` set; it renders struck through from then on.
4. **Record changes** for the new version (`breaking`, `behavior`, `deprecated`, `added`, `removed`) with `why`,
   before/after code, and the affected symbols. These become the upgrade view and the landing page's "New in".
5. **Review pages.** A page with no `removed` stays valid at the new version; set `removed` on any that no longer
   apply and add new ones `introduced` at the new version. Update samples whose code changed.
6. **Validate and import.** `node .claude/skills/docs-bundle/scripts/validate-bundle.mjs bundle.json`, then import.
7. **Rebuild and deploy** (next sections). The pre-render adds the new version's routes and search index; old
   versions stay browsable.

## Generating the OpenAPI spec

```
php scripts/generate-openapi.php -h
```

The generated spec can be placed under `web/sui/` for Swagger UI.

## Webserver notes

The public site is pre-rendered to flat HTML files (`/v4.2/do/prefetch-on-hover` is `v4.2/do/prefetch-on-hover.html`),
with the app shell as the fallback for anything not pre-rendered. The webserver must try the `.html` file first, then
fall back to `index.html`. `web/.htaccess` already does this for Apache.

### Apache2
```apacheconf
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME}.html -f
  RewriteRule ^(.*)$ $1.html [L]
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

### nginx
```nginx
location / {
  try_files $uri $uri.html /index.html;
}
```

## Building the public site

`pnpm zsf` in `ui/front` pre-renders every route the API reports for every version into `web/`, writes a search
index per version to `web/search/{version}.json`, and, when `docs.siteUrl` is set in `siteSettings.json`, a
`web/sitemap.xml`. The build needs the API reachable (the Docker `web` container); set `DOCS_API_BASE_URL` to point
elsewhere. Files from the previous build are removed first using the list the build leaves in `web/.ssg-output.json`.

## Deploying

The deployable is this repository with two build outputs and one config file added:

| What | Where | Produced by |
|---|---|---|
| PHP app: `api/`, `inc/`, `migrations/`, `vendor/`, `web/api/` | as checked out, plus `composer install` | you |
| `siteSettings.json` | project root, per install, never committed | `stoic-configure` |
| Public site | `web/` (flat `.html` per route, `web/search/`, `web/sitemap.xml`) | `pnpm --dir ui/front zsf` |
| Admin UI | `web/admin/` | `pnpm --dir ui/admin zsf` |

The webserver's document root is `web/`; the API is served from `web/api/1.1/index.php` and the rewrite rules
above serve the pre-rendered file for every route with the app shell as fallback. The pre-render is not static in
the sense of "no backend": each page carries its own data in the HTML, but after hydration the browse palette,
symbol previews, and client-side navigation to anything not in that state call the API (search reads the static
index and falls back to the API only when the index is missing). So the API must be reachable from the browser at
the base URL in `web/config.json`, and from the build machine while `pnpm zsf` runs (`DOCS_API_BASE_URL` overrides
where the build looks).

A minimal release, in order: import content, run the database migrations if any changed (`stoic-migrate`), build
the admin UI, build the public site, sync the checkout including `web/` to the host. Build outputs are ignored by
git, so build on the host or ship the built `web/` folder alongside the code.

## Tests

`./Exec.ps1 test` runs the PHP suite inside the web container against the `zsf_test` database and restores the
development DSN afterwards. The suite truncates every `Doc*` table of whatever database is configured, and
`tests/bootstrap.php` refuses to run unless the DSN names a `*_test` database, so never run `phpunit` directly
against the development settings. Front-end unit tests run with `pnpm --dir ui/front test:unit` and need no API.

## Design tokens and theming

Colors, type, spacing, and radii live in `ui/shared/design/tokens.json`. Both apps run `pnpm tokens` before every
build to regenerate `tokens.css`; the public site's CSS is written entirely against those variables and the admin
UI re-colors the PrimeVue Aura preset from them, so a re-skin is an edit to that one file. A dark theme is not
designed yet.

