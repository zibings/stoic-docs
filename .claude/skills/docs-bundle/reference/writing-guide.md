# Writing guide

The site follows Diátaxis: four kinds of documentation that answer four different questions, kept apart so each
can be good at its job. Readers pick the tab that matches what they need right now. Mixing the kinds is the most
common way documentation goes wrong, so the rules below are mostly about keeping them separate.

## The four modes

| Mode | Reader's question | Form | Never contains |
|---|---|---|---|
| **Learn** | "Teach me, I'm new." | A course: ordered lessons, each building one small thing, with steps the reader performs. | Every option, edge cases, alternatives, history. |
| **Do** | "How do I accomplish X?" | A recipe: goal in the title, a numbered path, the finished code, the trap to avoid. | Background theory, teaching from scratch, the full parameter list. |
| **Reference** | "What exactly is this?" | The contract (data) plus prose about usage and gotchas for one symbol. | Tutorials, opinions, long narrative. |
| **Explain** | "Why is it like this?" | A discussion of a concept, design decision, or tradeoff. | Steps, tasks, contracts. |

A quick test for a paragraph: if it tells the reader to do something in order, it belongs in Learn or Do. If it
states a fact about the API, it belongs in the contract or reference prose. If it argues, compares, or gives
background, it belongs in Explain.

## What counts as public

Public means a consumer is expected to use it: exported from an entry point, or reachable from an exported
object, and not marked internal or private. Two cases the word "public" hides:

- **Extension points are public.** A protected method that subclasses are meant to override or call
  (`__setupModel`, `setColumn`, `onError`, an abstract method) is API, because the library's own docs tell users
  to subclass. Document it. A protected helper that only the base class's own code calls is not.
- **Built-ins are not yours.** Exceptions, types, and interfaces from the language or another package appear
  only as text in signatures, `throws`, and params. Only what this library declares becomes a symbol.

When unsure, ask whether a user who reads the README would ever type the name. If yes, it is a symbol.

## Contracts

The contract pane is the machine truth of a symbol. Readers scan it; they do not read it. So:

- **Copy, don't paraphrase.** Signature text comes from source. Parameter types come from source. Defaults are
  the literal source expression (`"30_000"`, `"[]"`, `"null"`), not "thirty seconds".
- **One sentence per description.** "Milliseconds a result stays fresh." Not "This parameter controls how many
  milliseconds a result stays fresh before it is considered stale and refetched."
- **Summary is the search snippet.** One sentence that lets someone decide whether this is the symbol they want.
  Start with a verb for functions ("Declare a query..."), a noun phrase for types ("Behavior overrides for a
  query.").
- **Options are children.** An options object's fields become child `option` symbols so each has its own page,
  route, and version history, and so search finds `staleTime` directly.
- **The library's own errors are symbols.** Every exception class the library declares gets an `error` symbol,
  and `throws` entries name its type. Built-in exceptions are only text in `throws`.
- **Version history is data.** Never write "since v4.0" or "changed in v4.1" in prose. The contract rows produce
  those labels.

## Reference prose

Write a reference page only when a symbol needs more than its contract: interactions with other symbols, a
non-obvious default, ordering constraints, a common mistake. A page that would only restate the parameter table
is worse than none.

Structure that works: a minimal example first (`<<sample:minimal>>`), then two to four `##` sections each
answering one question ("How freshness works", "Keys must be serializable"). Link every symbol you name with
`{sym:...}`. Use `:::upgrade{from= to=}` blocks where behavior changed between versions, one per change, short.

## Do pages

- Title is the task as the reader would say it: "Force a refetch after a mutation", "Prefetch on hover".
  Not "Refetching" or "Using prefetch".
- Summary is the outcome in one line: "Start loading before the click."
- Body: state the situation in one or two sentences, give the code (`<<sample:...>>`), then the one or two things
  that go wrong when people try this. Stop.
- Set `minutes` honestly: how long to do it, including reading.
- Link the symbols used with role `mentions` so the symbol pages show "also covered in".

Three to eight Do pages is a good first set. Draw them from the README's examples, the most-viewed issues, and
what the tests exercise. When the library is PHP, add one more: "See only the upgrade changes that affect my code",
telling readers to run the docs site's `scripts/scan-usage.php --project . --package vendor/name --out scan.json`
against their project and load the report on the upgrade view. Nothing else on the site tells them the filter
exists.

## Explain pages

- Title names the concept or the decision: "Fresh, stale, and garbage-collected: the cache lifecycle", "Why
  prefetch doesn't subscribe".
- Explain the model, then the consequences, then the tradeoff the design accepted. Compare with the obvious
  alternative if there is one.
- No steps, no "first do this". Code appears only to illustrate a point, and a fenced block is fine when the
  language doesn't matter.
- Two to five pages. If you cannot name a concept a newcomer misunderstands, skip Explain rather than pad it.

## Learn: the course

One course, three to six lessons, each a `learn` page plus one to three steps. The reader builds one small,
concrete thing across the course, and each lesson adds one capability.

- **Lesson page body** is short prose: what this lesson adds and why it matters. One or two paragraphs and
  perhaps a `:::card[You'll leave able to]` block. The workspace next to it shows the files and the log, so the
  body does not need to show the code.
- **Titled samples** on the lesson page are the workspace file tabs. Give each file the state it is in *after*
  the lesson's steps, per language. Two or three files is plenty; name them like real files (`profile.ts`,
  `app.ts`, `api.ts`).
- **Steps** are the "Try it" cards, one at a time. `prompt` says what to do, in one line. `hint` is what you'd say
  if they got stuck. `answer` is the exact code that completes the step. `expectedOutput` is what the log shows
  afterward, one line per event, using the network and cache conventions from the format reference.
- Nothing runs in the browser. Progress is "I did this step", kept in the reader's browser. So answers and
  expected output must be correct by construction; run them yourself if you can.

Lesson order for a typical library: install and first call; reading results and handling failure; the one
concept that makes the library different; writing or mutating; the advanced mode most users eventually need
(server, batch, streaming). Stop when the reader can build the thing.

## The landing page

The `home/index` page's `summary` is the tagline: one sentence that says what the library is for and what makes
it different. Its `body` is optional prose under the hero, typically: one paragraph of what it does and doesn't
make you do, `## Install` with `<<sample:install>>` (one variant per package manager), `## Your first ...` with a
five-line example per language, and one sentence pointing at the course and the Do tab. The mode cards and
"New in" section are generated; do not write them.

## Samples

- One `key` per idea, one entry per language with `variant: null`, plus an entry per package manager for
  install lines. Same `key` across all of them.
- Every sample is complete enough to run: imports included, values realistic, no `...`. Prefer twelve lines that
  work to forty that show everything. Lesson step `answer`s are the exception: they are the fragment the reader
  types, and the lesson's titled samples hold the complete files.
- `title` is a file name for anything that is a file, `terminal` for commands. Untitled samples are fine in Do,
  Reference, and Explain pages; on lesson pages they never reach the workspace.
- `tested: true` only if you executed it against the documented version. `false` is not a failure, it is honesty.
- Never paste code that depends on the reader's language into a fenced block; use a sample so the picker works.

## Changes

A change entry is a single fact about a single version: what changed, why, what to do. Changes only render when
the install has an earlier version to compare against (the upgrade view and the landing page's "New in" section
both diff against the previous version), so with a single documented version, skip them. When you later add a
version, git history between the two version tags is a legitimate source when there is no changelog. Kinds:

- `breaking`: code that worked before fails or misbehaves. Always give `beforeCode` and `afterCode`.
- `behavior`: the same code does something different. Give before and after too.
- `deprecated`: still works, will go away; say what replaces it.
- `added`: new capability; link the new symbols.
- `removed`: gone; link the symbols and make sure their last contract row has `removed` set.

`title` states the delta, not the topic: "staleTime default: 0 → 30s", not "Stale time". `why` is the reason
the maintainers gave, in one paragraph, not a defense. Link every affected symbol; the upgrade view filters by
what a reader's code imports.

## Style

- Second person, present tense, active voice. "Tessel refetches when..." not "A refetch will be triggered".
- Short sentences. One idea each. No semicolons.
- Concrete over abstract: name the file, the option, the value.
- No filler: no "In this section", no "Let's", no "Note that", no summarizing what was just said, no
  "Congratulations". No emoji.
- Do not write about the docs ("This page covers..."). Write about the library.
- British or American spelling as the project's README uses.

## What not to do

- **Do not invent API.** If you cannot find it in source at the documented version, it does not exist. A name
  that appears only in an issue, a roadmap, or your memory of a similar library is not a symbol.
- **Do not document internals** because they are visible. Public means exported from an entry point and not
  marked internal.
- **Do not restate the contract in prose.** The pane already shows it.
- **Do not write one giant page per mode.** Many small pages with precise titles are what search and the
  browse tree are built for.
- **Do not skip the validator.** A dangling reference imports as an error and stops the whole import.
