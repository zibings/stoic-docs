# Authoring UI (`ui/admin`)

Vue 3 + PrimeVue 4 (styled, Aura preset re-colored from the shared design tokens). Administrators sign in with their
ZSF account and edit versions, modules, symbols and contracts, pages, code samples, changes, courses, and context
options through the `/Docs/Admin` API. Import and export move whole bundles.

```bash
pnpm install        # from ui/ (workspace root) or here
pnpm dev            # http://localhost:5174/admin/
pnpm test:unit
pnpm type-check
pnpm zsf            # builds into ../../web/admin
```

Runtime config comes from `public/config.json` (see `config.example.json`; the dev loop copies
`docker/admin-config.json`). `siteUrl` is where "view on site" links point.
