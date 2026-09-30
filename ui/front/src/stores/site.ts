import { defineStore } from "pinia";

import { docsApi } from "api/docs";
import type { ContextOption, DocVersion, Mode, SiteInfo } from "types/docs";

// Loaded once per app instance (pre-render and client) from /Docs/Site and carried to the client through the SSG
// initial state, so the header never flashes empty.
export const useSiteStore = defineStore("site", {
	state: () => ({
		loaded: false,
		failed: false,
		libraryName: "Documentation",
		repoUrl: "",
		siteUrl: null as string | null,
		latest: null as string | null,
		versions: [] as DocVersion[],
		languages: [] as ContextOption[],
		packageManagers: [] as ContextOption[],
		modes: ["learn", "do", "reference", "explain"] as Mode[],
	}),

	getters: {
		versionByLabel: (state) => (label: string): DocVersion | undefined => state.versions.find((v) => v.label === label),

		isKnownVersion(): (label: string) => boolean {
			return (label: string) => this.versionByLabel(label) !== undefined;
		},

		supportedVersions: (state): DocVersion[] => state.versions.filter((v) => v.isSupported),

		newestFirst: (state): DocVersion[] => [...state.versions].sort((a, b) => b.sortKey - a.sortKey),
	},

	actions: {
		apply(info: SiteInfo) {
			this.libraryName = info.libraryName;
			this.repoUrl = info.repoUrl;
			this.siteUrl = info.siteUrl ?? null;
			this.latest = info.latest;
			this.versions = info.versions;
			this.languages = info.contextOptions.languages;
			this.packageManagers = info.contextOptions.packageManagers;
			this.modes = info.modes;
			this.loaded = true;
			this.failed = false;
		},

		async load() {
			if (this.loaded) {
				return;
			}

			try {
				this.apply(await docsApi.site());
			} catch (error) {
				this.failed = true;

				if (import.meta.env.DEV) {
					console.error("Could not load /Docs/Site", error);
				}
			}
		},
	},
});
