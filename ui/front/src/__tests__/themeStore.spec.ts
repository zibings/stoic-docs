import { createPinia, setActivePinia } from "pinia";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import { THEME_STORAGE_KEY, useThemeStore } from "stores/theme";

function stubMatchMedia(matches: boolean): void {
	vi.stubGlobal(
		"matchMedia",
		vi.fn().mockImplementation((query: string) => ({ matches, media: query, addEventListener: vi.fn(), removeEventListener: vi.fn() })),
	);
}

describe("theme store", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		window.localStorage.clear();
		document.documentElement.removeAttribute("data-theme");
		document.head.innerHTML = '<meta name="theme-color" content="#F6F4EF">';
	});

	afterEach(() => vi.unstubAllGlobals());

	it("follows the system preference until the reader picks one", () => {
		stubMatchMedia(true);

		const theme = useThemeStore();

		theme.hydrate();

		expect(theme.preference).toBe("system");
		expect(theme.resolved).toBe("dark");
		expect(document.documentElement.hasAttribute("data-theme")).toBe(false);
		expect(document.querySelector('meta[name="theme-color"]')?.getAttribute("content")).toBe("#21201C");
	});

	it("toggles to the opposite of what is showing and remembers it", () => {
		stubMatchMedia(true);

		const theme = useThemeStore();

		theme.hydrate();
		theme.toggle();

		expect(theme.preference).toBe("light");
		expect(theme.resolved).toBe("light");
		expect(document.documentElement.getAttribute("data-theme")).toBe("light");
		expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBe("light");
		expect(document.querySelector('meta[name="theme-color"]')?.getAttribute("content")).toBe("#F6F4EF");

		theme.toggle();
		expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
	});

	it("restores a remembered choice and clears it when going back to system", () => {
		stubMatchMedia(false);
		window.localStorage.setItem(THEME_STORAGE_KEY, "dark");

		const theme = useThemeStore();

		theme.hydrate();
		expect(theme.resolved).toBe("dark");
		expect(document.documentElement.getAttribute("data-theme")).toBe("dark");

		theme.setPreference("system");
		expect(theme.resolved).toBe("light");
		expect(document.documentElement.hasAttribute("data-theme")).toBe(false);
		expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBeNull();
	});
});
