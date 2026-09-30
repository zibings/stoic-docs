# Documentation bundles

A bundle is the JSON interchange format for everything the docs site renders. `scripts/seed-docs.php` imports
one; future importers (OpenAPI, source extractors) emit one. Records reference each other by natural key, never by
database id, so a bundle is portable between installs:

| Reference | Form | Example |
|---|---|---|
| version | label | `"v4.2"` |
| module | path | `"tessel/query"` |
| symbol | `module/path#Name` or `module/path#Parent.Child` | `"tessel/query#QueryOptions.staleTime"` |
| page | `mode/slug` | `"do/prefetch-on-hover"` |
| context option | kind + key | `{"kind": "language", "key": "ts"}` |

Top-level keys, in import order: `contextOptions`, `versions`, `modules` (with nested `symbols`, each with `versions`
and `children`), `pages` (with `symbols` and `samples`), `changes` (with `symbols`), `courses` (with `lessons` and
`steps`). Importing is an upsert on the natural keys above, so re-running an import updates rather than duplicates.

Two fields matter more than they look. A sample's `title` (a file name such as `profile.ts`) makes it a card header in
prose and a file tab in the Learn workspace; untitled samples never appear in the workspace. A lesson step's
`expectedOutput` (one entry per line; lines starting with an HTTP verb or containing a status code become network
rows, lines starting with `CACHE` become cache rows) is what the workspace shows as the log, since nothing executes in
the browser.

The landing page is a page too: mode `home`, slug `index`. Its `summary` is the tagline under the library name and its
`body` is the optional prose beneath the hero, so `<<sample:...>>` embeds give it an install line per package manager
and a first example per language. The rest of the landing page (mode cards, what's new, modules) is generated from the
other records, so the bundle can omit the page entirely and still get a landing page.

`tessel.json` is a fictional data-fetching library used as fixture data. It is not a real API.

The field-by-field format reference, a writing guide, and a validator live in `.claude/skills/docs-bundle/`; that folder
is what to hand an AI agent that should write a bundle for a project.

## Generating bundles

Three scripts produce bundles or reports from real sources. Run them from the project root (inside the web container
during development). Import a bundle with `php scripts/seed-docs.php --file bundle.json` or the admin Import page.

| Script | Input | Output |
|---|---|---|
| `scripts/import-openapi.php --spec "v1.0=a.yaml,v1.1=b.yaml" --out bundle.json` | OpenAPI 3 documents (JSON or YAML), oldest first | Bundle with a module per tag plus `api/schemas`; `endpoint` symbols carry params (path, query, header, body), the 2xx return type, and other responses as throws |
| `scripts/import-php.php --src "v1.2=/tmp/lib-1.2,v1.3=/tmp/lib-1.3" --out bundle.json` | PHP source trees (one checkout per version) | Bundle with a module per namespace; classes, interfaces, traits, enums, and functions with public members as children |
| `scripts/scan-usage.php --project /path/to/app --package vendor/name --out scan.json` | A consuming project with `composer.lock` | Scan report for the upgrade view's "only symbols my code imports" filter, with the installed version as `fromVersion` |

Giving several versions to an importer is what produces contract ranges: a symbol's contract row is closed and a new
one opened whenever its signature, parameters, returns, throws, or status change between versions. Summaries and
source locations update in place. A single version still produces a valid bundle, just without history.
