import { dirname } from "node:path";
import { fileURLToPath, URL } from "node:url";

import vue from "@vitejs/plugin-vue";
import { configDefaults, defineConfig } from "vitest/config";

const root = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: [
			{ find: /^api\//, replacement: `${root}/src/api/` },
			{ find: /^components\//, replacement: `${root}/src/components/` },
			{ find: /^composables\//, replacement: `${root}/src/composables/` },
			{ find: /^design\//, replacement: `${root}/../shared/design/` },
			{ find: /^layout\//, replacement: `${root}/src/layout/` },
			{ find: /^lib\//, replacement: `${root}/src/lib/` },
			{ find: /^markdown\//, replacement: `${root}/../shared/markdown/` },
			{ find: /^stores\//, replacement: `${root}/src/stores/` },
			{ find: /^styles\//, replacement: `${root}/src/styles/` },
			{ find: /^types\//, replacement: `${root}/src/types/` },
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
