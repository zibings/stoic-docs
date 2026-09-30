# Public docs site (`ui/front`)

Vue 3 + vite-ssg. PrimeVue runs in **unstyled** mode: it supplies behavior (Select, Popover, Dialog, Tree, focus and
keyboard handling) and every visual comes from our CSS built on `src/design/tokens.css`.

## Layout

- `../shared/design/tokens.json` — the visual system (colors, type, radii, spacing), shared with the admin app.
  `pnpm tokens` regenerates `tokens.css` from it; every stylesheet references the generated custom properties.
- `../shared/markdown`, `../shared/types` — the Markdown directive renderer, highlighter, and API types, shared with
  the admin app through the `@docs/shared` workspace package.
- `src/styles/base.css` — reset and shared primitives (`.label`, `.pill`, `.card`, `.control`, `.kbd`, `.removed`).
- `src/api/docs.ts`, `src/types/docs.ts` — typed client for the public `/Docs` API.
- `src/stores` — `site` (versions, options, library identity), `context` (language and package manager, persisted in
  localStorage), `cache` (per-route API responses carried through the SSG initial state), `ui` (palette state),
  `trail` / `review` / `scan` (reader history, reviewed upgrade changes, the pasted scan report), `progress` (Learn
  steps and lessons completed, per version and course, localStorage only).
- `src/learn/steps.ts`, `src/components/learn/*` — Learn mode: the course spine, the "Try it" card (hint and answer
  reveals, mark done), and the static workspace (tabs from titled code samples, log rows from each step's
  `expectedOutput`, a "step passed" card once marked). No code executes in the browser. Under 1180px the spine
  collapses to a summary and the workspace becomes a bottom sheet (`src/styles/sheet.css`, shared with the contract
  sheet).
- `src/router/routes.ts` — the URL scheme; `src/router/index.ts` rewrites `latest` and rejects unknown versions.
- `src/layout/AppShell.vue`, `src/components/header/*` — header, mode tabs, search trigger, context bar, mobile chip.
- `src/views/*` — one view per route.

## Breakpoints

| Width | Layout |
|---|---|
| above 1180px | three columns; full header with tabs, search box, and context bar |
| 769 to 1180px | single column; contract and workspace become bottom sheets; context bar moves into the chip row under the header, search is an icon, tabs stay |
| 768px and below | tabs collapse into the mode label, which opens Browse on that mode's tab; sample cards drop the "rendered for" context |

Palettes go full screen at 768px and below. All controls keep a 44px hit area.

## Commands

```bash
pnpm install
pnpm dev          # regenerates tokens.css, starts Vite on :5173
pnpm test:unit    # vitest
pnpm type-check   # vue-tsc
pnpm build        # tokens → type-check → vite-ssg build into dist/
pnpm zsf          # same, but into ../../web next to the API
```

Runtime config comes from `public/config.json` (copy `config.example.json`, or let `Exec.ps1 init` copy
`docker/front-config.json`). During pre-rendering the same file is read from disk; `DOCS_API_BASE_URL` overrides it.
