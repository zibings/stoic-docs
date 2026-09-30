import { dirname } from "node:path";
import { fileURLToPath, URL } from "node:url";

import vue from "@vitejs/plugin-vue";
import { configDefaults, defineConfig } from "vitest/config";

// Kept independent of vite.config.ts so Vite's native config loader can run it without extension gymnastics.
const root = dirname(fileURLToPath(import.meta.url));

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
	server: {
		fs: {
			allow: [".."],
		},
	},
	test: {
		environment: "jsdom",
		exclude: [...configDefaults.exclude, "e2e/**"],
		root: fileURLToPath(new URL("./", import.meta.url)),
	},
});
