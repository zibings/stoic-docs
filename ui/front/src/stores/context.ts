import { defineStore } from "pinia";

import { useSiteStore } from "stores/site";

// The reader's language and package manager. These are remembered client preferences, never part of the URL;
// the version is part of the URL and is read from the route instead.
export const STORAGE_KEY = "docs.context";

interface StoredContext {
	language?: string;
	packageManager?: string;
}

function readStorage(): StoredContext {
	if (typeof window === "undefined") {
		return {};
	}

	try {
		const raw = window.localStorage.getItem(STORAGE_KEY);

		return raw ? (JSON.parse(raw) as StoredContext) : {};
	} catch {
		return {};
	}
}

function writeStorage(value: StoredContext): void {
	if (typeof window === "undefined") {
		return;
	}

	try {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
	} catch {
		// Storage can be unavailable (private mode, blocked); the in-memory value still applies for this visit.
	}
}

export const useContextStore = defineStore("context", {
	state: () => ({
		hydrated: false,
		language: null as string | null,
		packageManager: null as string | null,
	}),

	getters: {
		// Effective values fall back to the site's defaults, then the first option, so code samples always resolve.
		effectiveLanguage(state): string | null {
			const site = useSiteStore();

			return pick(state.language, site.languages.map((o) => o.key), site.languages.find((o) => o.isDefault)?.key);
		},

		effectivePackageManager(state): string | null {
			const site = useSiteStore();

			return pick(state.packageManager, site.packageManagers.map((o) => o.key), site.packageManagers.find((o) => o.isDefault)?.key);
		},

		languageLabel(): string {
			const site = useSiteStore();

			return site.languages.find((o) => o.key === this.effectiveLanguage)?.label ?? "";
		},

		packageManagerLabel(): string {
			const site = useSiteStore();

			return site.packageManagers.find((o) => o.key === this.effectivePackageManager)?.label ?? "";
		},
	},

	actions: {
		hydrate() {
			if (this.hydrated) {
				return;
			}

			const stored = readStorage();

			this.language = stored.language ?? null;
			this.packageManager = stored.packageManager ?? null;
			this.hydrated = true;
		},

		setLanguage(key: string) {
			this.language = key;
			this.persist();
		},

		setPackageManager(key: string) {
			this.packageManager = key;
			this.persist();
		},

		persist() {
			writeStorage({
				language: this.language ?? undefined,
				packageManager: this.packageManager ?? undefined,
			});
		},
	},
});

function pick(preferred: string | null, allowed: string[], fallback: string | undefined): string | null {
	if (preferred && allowed.includes(preferred)) {
		return preferred;
	}

	return fallback ?? allowed[0] ?? null;
}
