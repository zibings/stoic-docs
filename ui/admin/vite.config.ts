import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";

// `pnpm zsf` sets APP_TARGET so the built app lands in ../../web/admin, served under /admin/ next to the public site.
const target = process.env.APP_TARGET;

const root = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
	base: "/admin/",
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
	build: {
		outDir: target ?? "dist",
		emptyOutDir: true,
	},
	server: {
		host: true,
		port: 5174,
		fs: {
			allow: [".."],
		},
	},
});
