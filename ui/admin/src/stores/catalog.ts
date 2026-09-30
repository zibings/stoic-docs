import { defineStore } from "pinia";

import { adminApi } from "api/admin";
import type { AdminContextOption, AdminModule, AdminSymbol, AdminVersion } from "types/admin";

// Reference data the editors need for selects and labels. Reloaded after anything that changes them.
export const useCatalogStore = defineStore("catalog", {
	state: () => ({
		libraryName: "Documentation",
		siteUrl: "",
		versions: [] as AdminVersion[],
		modules: [] as AdminModule[],
		symbols: [] as AdminSymbol[],
		contextOptions: [] as AdminContextOption[],
		loaded: false,
	}),

	getters: {
		versionsNewestFirst: (state) => [...state.versions].sort((a, b) => b.sortKey - a.sortKey),
		versionLabel: (state) => (id: number | null | undefined): string => state.versions.find((v) => v.id === id)?.label ?? (id ? `#${id}` : "—"),
		modulePath: (state) => (id: number | null | undefined): string => state.modules.find((m) => m.id === id)?.path ?? "—",
		symbolRef: (state) => (id: number | null | undefined): string => state.symbols.find((s) => s.id === id)?.ref ?? "—",
		languages: (state) => state.contextOptions.filter((o) => o.kind === "language"),
		packageManagers: (state) => state.contextOptions.filter((o) => o.kind === "packageManager"),
	},

	actions: {
		async load(force = false) {
			if (this.loaded && !force) {
				return;
			}

			const [site, versions, modules, symbols, contextOptions] = await Promise.all([
				adminApi.site().catch(() => null),
				adminApi.list("versions"),
				adminApi.list("modules"),
				adminApi.list("symbols"),
				adminApi.list("contextOptions"),
			]);

			if (site) {
				this.libraryName = site.libraryName;
				this.siteUrl = site.siteUrl ?? "";
			}

			this.versions = versions;
			this.modules = modules;
			this.symbols = symbols;
			this.contextOptions = contextOptions;
			this.loaded = true;
		},

		async reloadVersions() {
			this.versions = await adminApi.list("versions");
		},

		async reloadModules() {
			this.modules = await adminApi.list("modules");
		},

		async reloadSymbols() {
			this.symbols = await adminApi.list("symbols");
		},

		async reloadContextOptions() {
			this.contextOptions = await adminApi.list("contextOptions");
		},
	},
});
