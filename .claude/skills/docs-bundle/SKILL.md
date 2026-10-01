---
name: docs-bundle
description: Generate a documentation bundle (one JSON file) for a library or codebase at a specific version, ready to import into a "Docs site on ZSF" install. Use when asked to document a project for the docs site, write its API reference, produce or extend a docs bundle, or add a new version to existing documentation.
---

# Write a documentation bundle for a project

You are documenting **one** library or codebase at **one or more versions** for a docs site built on the
"Docs site on ZSF" template. The deliverable is a single JSON file, the *bundle*, which the site owner imports.
You never need the docs site's database, PHP stack, or containers. You need the project's source, plain Node
for the validator, and this folder.

Read these two files before writing anything. They are the contract and are meant to be sufficient on their own.
The fixture in the docs-site repo (`fixtures/tessel.json`) is a complete worked example if you have access to it:

- `reference/bundle-format.md`: every record, field, natural key, and Markdown directive the importer and
  renderer accept. Derived from the code, not from examples.
- `reference/writing-guide.md`: what goes in each of the four reader modes, how to write contracts, samples,
  lesson steps, and change entries, and what not to do.

## What the site does with your bundle

The site has four reader modes as header tabs (Learn, Do, Reference, Explain) plus a landing page. A context bar
(version, language, package manager) drives every code sample and signature. Each symbol page shows prose in the
middle and a dark *contract pane* built from structured data: signature, parameters with defaults, returns, throws,
and since/changed/removed per version. The upgrade view diffs two versions. Search is built from the bundle at
build time. Removed symbols stay visible, struck through. Nothing in your bundle is rendered as-is except page
bodies; everything else is data the site lays out.

So: **contract is data, not prose.** Signatures, parameters, defaults, return types, and thrown errors go in
structured fields, once per version range. Prose explains; it never restates the contract.

## Workflow

Work in this order. Each step's output is input to the next, and the validator at the end will catch dangling
references, so keep natural keys consistent from the start.

### 1. Pin the inputs

Establish these before reading code. Ask if they are not given and cannot be inferred:

- **Which library.** One bundle documents one library. A monorepo needs one bundle per published package.
- **Which version(s).** A version has a `tag` (the release tag, e.g. `4.2.0`) and a `label` (the URL segment,
  e.g. `v4.2`). Read the manifest (`package.json`, `composer.json`, `pyproject.toml`, `Cargo.toml`, a
  `VERSION` file, git tags). Do not guess a version. If several versions are wanted, you need a checkout of each.
- **Existing documentation.** If the install already has content, get an export of it (admin UI: Import/Export,
  "Download bundle") and extend that file rather than starting fresh. Import is an upsert on natural keys, so
  re-importing an extended export updates in place. Always ship every version in the bundle, not just the new one,
  so exactly one version carries `latest: true`.
- **Languages and package managers.** These become `contextOptions`. A TypeScript library typically offers
  `ts` and `js` languages and `pnpm`, `npm`, `yarn` package managers. A PHP library offers `php` and `composer`.
  A Python library offers `python` and `pip`, `uv`, `poetry`. Pick what the project's own README supports.
- **Output path.** Default to `docs-bundle.json` in the project root, or wherever you were told.

### 2. Survey the public surface

Find what is actually public before deciding how to organize it:

- Entry points: the package manifest's `main`, `exports`, `types`; `__init__.py`; `src/lib.rs`; the autoload
  namespaces in `composer.json`.
- Exported names from those entry points. Follow re-exports to their definitions.
- The changelog, release notes, migration guides, and tests. Tests are the most reliable statement of behavior.
- Existing README and docs, for vocabulary and for which tasks users actually ask about.

Record source locations as you go. Every contract row can carry `sourcePath` and `sourceLine`, and the site
links them.

Do not document internals, private members, or anything marked internal, deprecated-and-hidden, or test-only.
If the library is large, cover the whole public surface as *symbols with contracts* (that is cheap and it is
what search and the contract pane need), and be selective about *prose*.

### 3. Decide the module and symbol tree

- **Modules** are the top level of the Reference tab and of the browse tree. Use the unit users import from:
  subpath exports (`lib/query`, `lib/server`), namespaces, Python subpackages, Rust modules. A module `path` is
  lowercase with `/` between segments because it becomes URL segments: the PHP namespace `Stoic\Pdo` is the
  module `stoic/pdo`, the Python package `requests.adapters` is `requests/adapters`. A tiny library can be one
  module named after the package. Give every module a one-sentence `summary`.
- **Symbols** are the things users call or type: functions, classes, methods, types/interfaces, options, constants,
  error classes. Kinds are fixed: `fn`, `class`, `method`, `type`, `option`, `const`, `error`.
- **Nest** members under their owner: methods under a class, fields under a type, options under an options type.
  A nested symbol is referenced as `module/path#Parent.Child` and renders as `Parent.Child`.

Write the tree down (module paths and symbol names) before writing contracts. Every later reference uses these
exact strings.

### 4. Write the contracts

For each symbol, one contract row per version range. A single version means one row with `introduced` set to
that version's label and `removed: null`. See the format reference for the row shape and the range rules.

- `signature` is the declaration copied from source as a user would read it, multi-line for long option types.
  The format reference has a table mapping language constructs (properties, enums, constructors, extension
  points) onto the seven symbol kinds; use it rather than inventing a mapping.
- `params` carry `name`, `type`, `required`, `default` (as source text, e.g. `"30_000"`, or `null`), and one
  sentence of `description`. Options of an options type are usually both a `params` entry on the parent and a
  child `option` symbol; do both when users will look them up by name.
- `returns` is `{type, description}`; `throws` is a list of `{type, description}`.
- `status` is `stable` unless the source marks it `experimental` or `deprecated`.

Multiple versions: a new row when signature, params, returns, throws, or status change between versions;
otherwise extend the existing row. A symbol that disappears keeps its last row with `removed` set to the first
version it is missing from.

### 5. Write the reference prose

One `reference` page per symbol worth explaining beyond its contract, linked with `symbols: [{ref, role:
"subject"}]`. Not every symbol needs one. Prose covers usage, gotchas, and interactions, never a restatement of
the parameter table. Embed the minimal example with `<<sample:minimal>>` and give the sample per language.

### 6. Write Do, Explain, and the landing page

- **Do** pages: three to eight task-shaped recipes drawn from the README, issues, and tests. Title them as
  tasks ("Retry only on network errors"). Give `minutes`.
- **Explain** pages: two to five concept pages about *why* the library behaves the way it does. No steps.
- **Home** page: mode `home`, slug `index`. Its `summary` is the tagline under the library name; its `body` is
  optional prose under the hero. At minimum an `install` sample with one variant per package manager, embedded
  with `<<sample:install>>`; ideally also a five-line `hello` sample per language. The rest of the landing page
  (mode cards, what's new) is generated.

### 7. Write one course

Learn mode is a course spine of `learn` pages, each with one to three steps. Three to six lessons that build one
small thing end to end. Each lesson page's body is the prose; its **titled** samples (`title` like `app.ts`)
become the file tabs of the workspace; each step's `expectedOutput` becomes the log. Nothing runs in the browser,
so the `answer` and `expectedOutput` must be right by construction. Skip the course only if the library has no
sensible "first thing to build".

### 8. Record changes

Only when the bundle (or the install) has more than one version: write `changes` entries for the newer version,
kinds `breaking`, `behavior`, `deprecated`, `added`, `removed`. Each links the affected symbol refs and gives a
`why`, and for breaking or behavior changes, `beforeCode` and `afterCode`. Sources, in order: the changelog, the
migration guide, then the git diff between the two version tags. These power the upgrade view and the "New in"
section of the landing page, both of which compare against the previous version, so with one version they have
nothing to show.

### 9. Validate, then hand off

```
node <this folder>/scripts/validate-bundle.mjs docs-bundle.json
```

It exits non-zero on any error and prints the JSON path of each problem. Fix every error. Read the warnings; most
of them are real omissions.

Then tell the owner how to import. The web container mounts the docs-site checkout at `/var/www/html`, so copy
the bundle somewhere inside that checkout and run, from the docs-site root:

```
docker exec -i docsite-web php scripts/seed-docs.php --file path/inside/checkout/docs-bundle.json
```

or use the admin UI's Import/Export page ("Choose bundle…"), which takes any local file. Add `--reset` to the
script only when replacing all content. After import, the site is rebuilt with `pnpm --dir ui/front zsf`.

## Shortcuts for PHP and OpenAPI

The docs-site repo has extractors that produce a bundle with modules, symbols, and contracts already filled:

- `php scripts/import-php.php --src "v1.2=/path/to/1.2,v1.3=/path/to/1.3" --out bundle.json`
- `php scripts/import-openapi.php --spec "v1.0=a.yaml,v1.1=b.yaml" --out bundle.json`

They run inside the docs-site web container, not in the target project. If you can run them, do, then add
context options, pages, samples, changes, and a course to their output. If you cannot, write the bundle by hand
following the same shapes.

## Quality bar

- Every signature, default, and type is copied from source, not recalled from memory. When the source and the
  README disagree, the source wins and the disagreement is worth a Do or Explain page.
- Every sample would run against the documented version. Set `tested: true` only if you actually executed it.
- Every `{sym:...}` reference and `<<sample:...>>` embed resolves. The validator checks this.
- Prose is short, second person, and specific. See the writing guide.
- No AI-generated filler: no "In this section we will", no restating the heading, no summaries of what was just
  said.
