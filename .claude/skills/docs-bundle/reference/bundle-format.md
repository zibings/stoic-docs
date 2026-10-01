# Bundle format reference

A bundle is one JSON object. This document lists every key the importer reads (`DocBundleImporter`) and every
Markdown directive the renderer understands (`ui/shared/markdown/render.ts`). Anything not listed here is
ignored on import. Field names are case-sensitive.

Records reference each other by **natural key**, never by database id, so a bundle is portable between installs:

| Reference to a | Form | Example |
|---|---|---|
| version | its `label` | `"v4.2"` |
| module | its `path` | `"tessel/query"` |
| symbol | `module/path#Name`, nested as `module/path#Parent.Child` (any depth) | `"tessel/query#QueryOptions.staleTime"` |
| page | `mode/slug` | `"learn/your-first-query"` |
| context option | kind + key | `{"kind": "language", "key": "ts"}` |

Import is an **upsert**: a record whose natural key already exists in the database is updated, otherwise it is
created. Nothing is deleted by an import. Import order is fixed: `contextOptions`, `versions`, `modules`,
`pages`, `changes`, `courses`. A reference must point at something imported earlier in that order (or already in
the database), with one exception: a course's lesson pages must be in the **same** bundle.

## Top level

```json
{
  "contextOptions": [],
  "versions": [],
  "modules": [],
  "pages": [],
  "changes": [],
  "courses": []
}
```

Every key is optional on import, but a useful bundle has at least one language, one version, one module, and one
page.

## contextOptions

The choices offered in the site's context bar. Sample `language` values must be language keys; sample `variant`
values must be package-manager keys.

```json
{ "kind": "language", "key": "ts", "label": "TypeScript", "sortOrder": 1, "default": true }
```

| Field | Type | Notes |
|---|---|---|
| `kind` | `"language"` \| `"packageManager"` | Required. |
| `key` | string | Required. Natural key within the kind. Short and lowercase (`ts`, `php`, `pnpm`). |
| `label` | string | Shown in the picker. Defaults to `key`. |
| `sortOrder` | integer | Picker order. Default 0. |
| `default` | boolean | The reader's initial choice until they pick another. Exactly one per kind. Default false. |

## versions

```json
{ "tag": "4.2.0", "label": "v4.2", "sortKey": 420, "releasedAt": "2025-09-09", "latest": true, "supported": true }
```

| Field | Type | Notes |
|---|---|---|
| `label` | string | Required. Natural key **and** the URL segment (`/v4.2/reference/...`). Keep it URL-safe. |
| `tag` | string | The release tag as the project names it. Shown next to the label. |
| `sortKey` | integer | Ordering. Strictly increasing with release order. `major*100 + minor*10` works up to minor 9; `major*10000 + minor*100 + patch` is safer. |
| `releasedAt` | `"YYYY-MM-DD"` \| null | Release date, UTC: the tag's date, or the version-bump commit's date when there is no tag. `null` when unknown; never invent one. |
| `latest` | boolean | The version `/latest/...` redirects to. Exactly one in the whole install, so always ship every version. Default false. |
| `supported` | boolean | Unsupported versions are still browsable but flagged. Default **true**. |

List versions oldest first.

## modules

```json
{
  "path": "tessel/query",
  "summary": "Declare, read, and invalidate server data.",
  "sortOrder": 1,
  "symbols": [ ... ]
}
```

| Field | Type | Notes |
|---|---|---|
| `path` | string | Required. Natural key. Lowercase, segments separated by `/`, because it becomes URL segments: `/v4.2/reference/tessel/query/createQuery`. A PHP namespace `Stoic\Pdo` is `stoic/pdo`; a Python package `requests.adapters` is `requests/adapters`; a subpath export `lib/server` stays `lib/server`. |
| `summary` | string | One sentence. Shown in the browse tree, the Reference index, and the landing page. |
| `sortOrder` | integer | Default 0. |
| `symbols` | symbol[] | Top-level symbols of the module. |

### symbol

```json
{
  "name": "createQuery",
  "kind": "fn",
  "sortOrder": 1,
  "versions": [ ...contract rows... ],
  "children": [ ...symbols... ]
}
```

| Field | Type | Notes |
|---|---|---|
| `name` | string | Required. Unique among siblings (same module and parent). Must not contain `.` or `#`. Constructors are named as the language names them (`__construct`, `constructor`, `__init__`, `new`). |
| `kind` | `fn` \| `class` \| `method` \| `type` \| `option` \| `const` \| `error` | Required. See the mapping table below. |
| `sortOrder` | integer | Defaults to position in the array; omit on children unless you need a non-source order. |
| `versions` | contract[] | The symbol's contract rows. A symbol with no rows is invisible at every version. |
| `children` | symbol[] | Members: methods of a class, fields of a type, options of an options type. Referenced as `Parent.Child`. |

How the seven kinds map onto what languages actually have:

| Source construct | kind | Nesting |
|---|---|---|
| Free function, exported arrow function | `fn` | top level |
| Class, interface, trait, abstract class | `class` | top level; members as children |
| Method, static method, constructor | `method` | child of its class |
| Public property or field of a class | `option` | child of its class |
| Type alias, interface used as a record shape, options object type, TypedDict, struct | `type` | top level; fields as `option` children |
| Field of a type, key of an options object | `option` | child of its type |
| Enum (native or class-based) | `type` | cases or constants as `const` children |
| Constant, class constant, enum case | `const` | top level or child |
| Exception or error class the library itself defines | `error` | top level |

Built-in exceptions the library throws (`TypeError`, `PDOException`, `ValueError`) are not symbols; they appear
only as `throws[].type` text. `throws[].type` is free text and is never matched to a symbol.

Symbols have one nesting level per `.` in their ref, any depth. Only document a member on the class that declares
it; a subclass that inherits a method without overriding it does not repeat it.

Nested symbols get their own page at `/v4.2/reference/<module>/<Parent.Child>` and render as `Parent.Child`.

### contract row (`versions[]` entry)

A contract is valid from `introduced` (inclusive) until `removed` (exclusive; `null` while current). Rows of one
symbol must not overlap.

```json
{
  "introduced": "v2.0",
  "removed": "v4.0",
  "signature": "function createQuery<T>(key: QueryKey, fetcher: () => Promise<T>, options?: QueryOptions<T>): Query<T>",
  "summary": "Declare a query. Nothing fetches until it is read.",
  "status": "stable",
  "sourcePath": "src/query.ts",
  "sourceLine": 42,
  "params": [
    { "name": "key", "type": "QueryKey", "required": true, "default": null, "description": "Serializable identity of the data.", "since": null }
  ],
  "returns": { "type": "Query<T>", "description": "lazy, nothing fetches until read" },
  "throws": [
    { "type": "QueryKeyError", "description": "key isn't serializable" }
  ]
}
```

| Field | Type | Notes |
|---|---|---|
| `introduced` | version label | Required. Together with the symbol it is the row's natural key: changing it makes a new row instead of moving one. |
| `removed` | version label \| null | First version this row no longer applies to. On the symbol's last row it means the symbol was removed, and the symbol renders struck through from that version on. |
| `signature` | string | The declaration as users read it, copied from source. Multi-line is fine. Rendered in a code block with the reader's language highlighting. For a class, the class line with its extends/implements; for a property, its declaration; for an enum case, the case line. |
| `summary` | string | One sentence. Shown in the pane, search, and the browse tree. |
| `status` | `stable` \| `experimental` \| `deprecated` | Default `stable`. |
| `sourcePath` | string \| omit | Path inside the repository at that version. Combined with the site's source URL pattern (`{tag}`, `{path}`, `{line}`) to link to source. |
| `sourceLine` | integer \| omit | Line of the declaration itself (the `class`, `function`, or property line), not its doc comment. |
| `params` | param[] | Empty array when none. |
| `returns` | `{type, description}` \| `{}` | `{}` when there is nothing to say: `void`, `None`, unit, a constructor. The pane hides the returns row when `type` is empty. |
| `throws` | `{type, description}[]` | Empty array when none. |

param: `name` and `type` (strings), `required` (boolean), `default` (string of source text, or null),
`description` (string), and optional `since` (version label the parameter first appeared in; omit or `null` when
it has always been there). An untyped parameter gets the type the language would infer or the language's
"anything" type (`mixed`, `any`, `object`). A variadic parameter is named as declared (`...$args`, `*args`) with
`required: false` and `default: null`.

`description` fields in params, returns, and throws are fragments or single short sentences. "Milliseconds a
result stays fresh." or "the live cache instance" are both fine. Keep them under about twelve words.

**Range rules across versions.** Open a new row when signature, params, returns, throws, or status change.
Summary and source location edits update the open row in place. Example for a symbol whose default changed in
v4.0 and which was removed in v4.2:

```json
"versions": [
  { "introduced": "v2.0", "removed": "v4.0", "signature": "...", "params": [ { "name": "staleTime", "default": "0", ... } ] },
  { "introduced": "v4.0", "removed": "v4.2", "signature": "...", "params": [ { "name": "staleTime", "default": "30_000", ... } ] }
]
```

The site derives "since v2.0", "changed in v4.0", and "removed in v4.2" from these rows. Do not write them into
prose.

## pages

```json
{
  "mode": "do",
  "slug": "prefetch-on-hover",
  "title": "Prefetch on hover",
  "summary": "Start loading before the click.",
  "body": "Markdown with directives, see below",
  "introduced": "v3.0",
  "removed": null,
  "minutes": 2,
  "symbols": [ { "ref": "tessel/query#prefetch", "role": "mentions" } ],
  "samples": [ ... ]
}
```

| Field | Type | Notes |
|---|---|---|
| `mode` | `learn` \| `do` \| `reference` \| `explain` \| `home` | Required. |
| `slug` | string | Required. Natural key with `mode` and `introduced`. URL: `/v4.2/do/prefetch-on-hover`. May contain `/`. Reference pages use the subject's path, `module/path/Name` or `module/path/Parent.Child`; the validator warns when they don't. |
| `title` | string | Required. Rendered as the page's h1 on Do, Explain, and Learn pages; do not repeat it as a heading in the body. On a reference page the symbol's own name is the h1 and the title is only shown in admin and "also covered in" lists, so use the symbol's dotted name (`PdoHelper.storeQuery`). |
| `summary` | string | One sentence. Shown under the title, in mode indexes, search, and the landing page. |
| `body` | string | Markdown. See directives below. |
| `introduced` | version label | Required. First version the page applies to. |
| `removed` | version label \| null | First version it no longer applies to. |
| `minutes` | integer \| null | Reading or doing time. Shown on Do, Explain, and lesson lists. `null` on reference and home pages. |
| `symbols` | `{ref, role}[]` | Symbols this page is about (`subject`) or refers to (`mentions`). Default role `mentions`. |
| `samples` | sample[] | Code samples embedded by key. |

Rules per mode:

- **`reference`**: one page per symbol that deserves prose. Link the symbol with role `subject`; the symbol page
  then renders this body under the contract pane. Slug convention: `module/path/Name`. At most one subject.
- **`do`** and **`explain`**: listed on their mode tab; the body is the whole page.
- **`learn`**: a lesson. Must be attached to a course (see `courses`); an unattached learn page still gets a
  URL but appears in no course. Its titled samples are the workspace file tabs.
- **`home`**: exactly one page, slug **`index`**. `summary` is the landing-page tagline under the library name,
  `body` is optional prose under the hero. Not a header tab, not in search, and never shown in a symbol's "also
  covered in" list, but it may still list `mentions` so that `{sym:...}` links in its body resolve.

`{sym:...}` links in the body resolve only for symbols listed in the page's `symbols` (any role). On a symbol page
the subject, its siblings in the module, and the types named by its parameters also resolve. List every symbol you
reference anyway; it also feeds the "also covered in" list on symbol pages.

### sample

```json
{
  "key": "install",
  "language": "ts",
  "variant": "pnpm",
  "code": "pnpm add tessel\n",
  "title": "terminal",
  "tested": false,
  "lastTestPass": null
}
```

| Field | Type | Notes |
|---|---|---|
| `key` | string | Required. Referenced by `<<sample:key>>`. Letters, digits, `.`, `_`, `-`. |
| `language` | language key | Required. Which language picker choice this variant serves. |
| `variant` | package-manager key \| null | Which package-manager choice this serves; `null` for samples that don't depend on it. |
| `code` | string | The code. Trailing newline optional. |
| `title` | string \| null | A file name (`profile.ts`) or `terminal`. Becomes the card header and, on learn pages, a workspace file tab. Untitled samples never appear in the workspace. |
| `tested` | boolean | Only `true` if the sample was actually executed against the documented version. |
| `lastTestPass` | version label \| null | Shown as "tested on v4.2". |

Natural key: `key` + `language` + `variant`. Selection at render time: samples with the reader's language,
falling back to any language; then the one whose `variant` equals the reader's package manager, falling back to
`variant: null`, falling back to the first. So an install sample needs one entry per package manager, all with
the same `key`, and a code sample needs one entry per language with `variant: null`.

### Markdown directives in `body`

Standard Markdown (headings from `##` down, paragraphs, lists, emphasis, inline code, links, fenced code blocks)
plus four directives. Every `##` and `###` gets an anchor id derived from its text (`## Stale time` → `#stale-time`,
repeats get `-2`, `-3`), and on pages without a rail those headings become the "On this page" table of contents,
so give sections short, distinct headings.

| Directive | Where | Renders as |
|---|---|---|
| `<<sample:key>>` | alone on a line, blank lines around it | The sample card for `key` chosen by the reader's context, with copy button, title, and "rendered for v4.2 + TypeScript". Use this instead of fenced code whenever the code depends on language or package manager. |
| `{sym:module/path#Name}` | inline | A link to the symbol, showing its short name, struck through if removed at this version. Unresolved refs render as plain code. |
| `:::card[Label]` … `:::` | block | A full-width card with an optional small label. Use for "You'll leave able to", prerequisites, warnings. Markdown and the other directives work inside it. |
| `:::upgrade{from=v3.8 to=v4.0}` … `:::` | block | An upgrade note. Rendered in full for readers who arrived from an older version, otherwise as a one-line link to the upgrade view. |

Fenced code blocks (```` ```ts ````) are fine for output, config, or anything that is the same for every reader.

## changes

```json
{
  "version": "v4.0",
  "kind": "breaking",
  "title": "staleTime default: 0 → 30s",
  "why": "Most apps set staleTime by hand everywhere to stop refetch storms, so v4 picks the default people were already choosing.",
  "rfcUrl": "https://github.com/example/tessel/discussions/412",
  "beforeCode": "createQuery(key, fetcher)\n// read at 5s → network",
  "afterCode": "createQuery(key, fetcher)\n// read at 5s → cache",
  "codemodCmd": "pnpm dlx tessel-migrate stale-time ./src",
  "sortOrder": 1,
  "symbols": [ "tessel/query#QueryOptions.staleTime" ]
}
```

| Field | Type | Notes |
|---|---|---|
| `version` | version label | Required. The version the change ships in. Natural key with `title`. |
| `kind` | `breaking` \| `behavior` \| `deprecated` \| `added` \| `removed` | Required. |
| `title` | string | Required. Short, specific, states the delta. |
| `why` | string | The reason, one paragraph. |
| `rfcUrl` | string \| null | Link to the discussion or RFC. |
| `beforeCode` / `afterCode` | string \| null | Side-by-side snippets. Expected for `breaking` and `behavior`. |
| `codemodCmd` | string \| null | A command that applies the migration, if one exists. |
| `sortOrder` | integer | Order within the version. |
| `symbols` | symbol ref[] | Affected symbols. Drives the "only symbols my code imports" filter on the upgrade view. |

## courses

```json
{
  "slug": "tessel-in-an-afternoon",
  "title": "Tessel in an afternoon",
  "summary": "Five lessons from first query to server rendering.",
  "sortOrder": 1,
  "lessons": [
    {
      "page": "learn/your-first-query",
      "ordinal": 1,
      "steps": [
        {
          "ordinal": 1,
          "prompt": "Declare a query for `/me` and read it in the component.",
          "hint": "createQuery takes a key and a fetcher.",
          "answer": "export const profile = createQuery([\"profile\", \"me\"], () => api.get(\"/me\"));",
          "expectedOutput": "GET /me 200"
        }
      ]
    }
  ]
}
```

| Field | Type | Notes |
|---|---|---|
| `slug` | string | Required. Natural key. URL: `/v4.2/learn/<course>/<lesson-slug>`. |
| `title`, `summary` | string | Shown on the Learn index and the landing page. |
| `sortOrder` | integer | |
| `lessons[].page` | `learn/<slug>` | Required. Must be a `learn` page **in this bundle**. |
| `lessons[].ordinal` | integer | Natural key within the course; defaults to position. |
| `steps[].ordinal` | integer | Natural key within the lesson; defaults to position. |
| `steps[].prompt` | string | Required. Markdown, one line. What the reader should do. |
| `steps[].hint` | string \| null | Revealed on request. |
| `steps[].answer` | string \| null | The code that completes the step: a fragment, not a whole file. When it touches two files, separate the parts with a comment line naming each file (`// app.php`). Revealed on request. |
| `steps[].expectedOutput` | string \| null | What the workspace log shows after the step. One entry per line. `null` for a step that produces no output (installing, creating a file); the card then just says "Marked done". |

`expectedOutput` line conventions: a line starting with an HTTP method (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`,
`HEAD`, `OPTIONS`), with `HTTP/`, or with a three-digit status code becomes a network row; a line starting with
`CACHE` becomes a cache row; anything else is plain output, even if it contains numbers. Nothing executes in the
browser, so these lines are the only "run" the reader sees.

Each lesson page is self-contained: its titled samples are the whole workspace for that lesson, so a file that
appears in three lessons is repeated on all three pages, in the state it has after that lesson's steps.

## Minimal complete bundle

```json
{
  "contextOptions": [
    { "kind": "language", "key": "ts", "label": "TypeScript", "sortOrder": 1, "default": true },
    { "kind": "packageManager", "key": "npm", "label": "npm", "sortOrder": 1, "default": true }
  ],
  "versions": [
    { "tag": "1.0.0", "label": "v1.0", "sortKey": 10000, "releasedAt": "2026-01-15", "latest": true, "supported": true }
  ],
  "modules": [
    {
      "path": "tiny",
      "summary": "The whole library.",
      "sortOrder": 1,
      "symbols": [
        {
          "name": "greet",
          "kind": "fn",
          "versions": [
            {
              "introduced": "v1.0",
              "removed": null,
              "signature": "function greet(name: string): string",
              "summary": "Returns a greeting.",
              "status": "stable",
              "sourcePath": "src/index.ts",
              "sourceLine": 3,
              "params": [ { "name": "name", "type": "string", "required": true, "default": null, "description": "Who to greet." } ],
              "returns": { "type": "string", "description": "the greeting" },
              "throws": []
            }
          ],
          "children": []
        }
      ]
    }
  ],
  "pages": [
    {
      "mode": "home",
      "slug": "index",
      "title": "tiny",
      "summary": "One function, no dependencies.",
      "body": "## Install\n\n<<sample:install>>\n",
      "introduced": "v1.0",
      "removed": null,
      "minutes": null,
      "symbols": [],
      "samples": [
        { "key": "install", "language": "ts", "variant": "npm", "code": "npm install tiny\n", "title": "terminal", "tested": false, "lastTestPass": null }
      ]
    },
    {
      "mode": "reference",
      "slug": "tiny/greet",
      "title": "greet",
      "summary": "Returns a greeting.",
      "body": "<<sample:minimal>>\n",
      "introduced": "v1.0",
      "removed": null,
      "minutes": null,
      "symbols": [ { "ref": "tiny#greet", "role": "subject" } ],
      "samples": [
        { "key": "minimal", "language": "ts", "variant": null, "code": "import { greet } from \"tiny\";\n\ngreet(\"world\");\n", "title": "hello.ts", "tested": false, "lastTestPass": null }
      ]
    }
  ],
  "changes": [],
  "courses": []
}
```
