import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";
// Type-only import: pulls in vite-ssg's augmentation of Vite's UserConfig so `ssgOptions` type-checks.
import type {} from "vite-ssg";

import { collectRoutes, defaultFetcher, listAssetFiles, readApiBase, routeToFile, writeOutputList, writeSearchIndexes, writeSitemap } from "./build/ssg";
import type { SsgCollected } from "./build/ssg";

// `pnpm zsf` sets APP_TARGET so the pre-rendered site lands in ../../web next to the API. That directory also holds
// api/, sui/, and .htaccess, so it is never emptied; scripts/clean-web.mjs removes the previous build's files first.
const root = dirname(fileURLToPath(import.meta.url));
const target = process.env.APP_TARGET;
const outDir = target ?? "dist";

let collected: SsgCollected | null = null;
let renderedRoutes: string[] = [];

export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: [
			{ find: /^api\//, replacement: `${root}/src/api/` },
			{ find: /^browse\//, replacement: `${root}/src/browse/` },
			{ find: /^components\//, replacement: `${root}/src/components/` },
			{ find: /^composables\//, replacement: `${root}/src/composables/` },
			{ find: /^contract\//, replacement: `${root}/src/contract/` },
			{ find: /^design\//, replacement: `${root}/../shared/design/` },
			{ find: /^layout\//, replacement: `${root}/src/layout/` },
			{ find: /^learn\//, replacement: `${root}/src/learn/` },
			{ find: /^markdown\//, replacement: `${root}/../shared/markdown/` },
			{ find: /^search\//, replacement: `${root}/src/search/` },
			{ find: /^stores\//, replacement: `${root}/src/stores/` },
			{ find: /^styles\//, replacement: `${root}/src/styles/` },
			{ find: /^types\//, replacement: `${root}/../shared/types/` },
			{ find: /^upgrade\//, replacement: `${root}/src/upgrade/` },
			{ find: /^views\//, replacement: `${root}/src/views/` },
			{ find: /^@\//, replacement: `${root}/src/` },
		],
	},
	build: {
		outDir,
		emptyOutDir: target === undefined,
	},
	ssgOptions: {
		script: "async",
		formatting: "minify",
		concurrency: 10,

		// Every route the API reports for every version. SSG_ROUTES (comma-separated) limits a verification build
		// to specific routes instead of asking the API.
		async includedRoutes() {
			const extra = (process.env.SSG_ROUTES ?? "").split(",").map((r) => r.trim()).filter(Boolean);

			if (extra.length > 0) {
				renderedRoutes = ["/", ...extra];

				return renderedRoutes;
			}

			collected = await collectRoutes(readApiBase(root), defaultFetcher);
			renderedRoutes = collected.routes;

			console.log(`[ssg] ${collected.routes.length} routes across ${collected.versions.length} versions`);

			return renderedRoutes;
		},

		// After the pages: static search indexes, sitemap (when the site URL is configured), and the output list.
		onFinished() {
			const absOut = resolve(root, outDir);
			const files = renderedRoutes.map(routeToFile);

			if (collected) {
				files.push(...writeSearchIndexes(absOut, collected.manifests));

				const sitemap = writeSitemap(absOut, collected.siteUrl, collected.routes);

				if (sitemap) {
					files.push(sitemap);
				}

				console.log(`[ssg] wrote ${collected.manifests.length} search indexes${sitemap ? " and sitemap.xml" : ""}`);
			}

			files.push(...listAssetFiles(absOut));
			writeOutputList(absOut, files);
		},
	},
	server: {
		host: true,
		port: 5173,
		fs: {
			// ui/shared (Markdown renderer, API types) lives outside this app's root.
			allow: [".."],
		},
		watch: {
			usePolling: true,
		},
	},
});
