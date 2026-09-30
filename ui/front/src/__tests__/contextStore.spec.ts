import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, it } from "vitest";

import { STORAGE_KEY, useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";

function seedSite() {
	useSiteStore().apply({
		libraryName: "tessel",
		repoUrl: "",
			siteUrl: null,
		latest: "v4.2",
		versions: [],
		contextOptions: {
			languages: [
				{ key: "ts", label: "TypeScript", isDefault: true },
				{ key: "js", label: "JavaScript", isDefault: false },
			],
			packageManagers: [
				{ key: "pnpm", label: "pnpm", isDefault: true },
				{ key: "npm", label: "npm", isDefault: false },
			],
		},
		modes: ["learn", "do", "reference", "explain"],
	});
}

describe("context store", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		window.localStorage.clear();
		seedSite();
	});

	it("falls back to the site defaults before anything is chosen", () => {
		const context = useContextStore();

		context.hydrate();

		expect(context.effectiveLanguage).toBe("ts");
		expect(context.effectivePackageManager).toBe("pnpm");
		expect(context.languageLabel).toBe("TypeScript");
	});

	it("persists choices to localStorage and restores them", () => {
		const context = useContextStore();

		context.hydrate();
		context.setLanguage("js");
		context.setPackageManager("npm");

		expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? "{}")).toEqual({ language: "js", packageManager: "npm" });

		setActivePinia(createPinia());
		seedSite();

		const restored = useContextStore();

		restored.hydrate();

		expect(restored.effectiveLanguage).toBe("js");
		expect(restored.effectivePackageManager).toBe("npm");
	});

	it("ignores a stored choice the site no longer offers", () => {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ language: "rust" }));

		const context = useContextStore();

		context.hydrate();

		expect(context.language).toBe("rust");
		expect(context.effectiveLanguage).toBe("ts");
	});
});
