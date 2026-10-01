import { defineStore } from "pinia";

// The reader's color theme. A remembered client preference (localStorage), never part of the URL. "system" follows
// prefers-color-scheme through CSS alone (design/theme-dark.css); an explicit choice is applied as data-theme on
// <html>, which the inline script in index.html also does before first paint.
export const THEME_STORAGE_KEY = "docs.theme";

export type ThemePreference = "system" | "light" | "dark";
export type ResolvedTheme = "light" | "dark";

const THEME_COLORS: Record<ResolvedTheme, string> = { light: "#F6F4EF", dark: "#21201C" };
const DARK_QUERY = "(prefers-color-scheme: dark)";

function readPreference(): ThemePreference {
	if (typeof window === "undefined") {
		return "system";
	}

	try {
		const raw = window.localStorage.getItem(THEME_STORAGE_KEY);

		return raw === "light" || raw === "dark" ? raw : "system";
	} catch {
		return "system";
	}
}

function writePreference(value: ThemePreference): void {
	if (typeof window === "undefined") {
		return;
	}

	try {
		if (value === "system") {
			window.localStorage.removeItem(THEME_STORAGE_KEY);
		} else {
			window.localStorage.setItem(THEME_STORAGE_KEY, value);
		}
	} catch {
		// Storage can be unavailable (private mode, blocked); the in-memory value still applies for this visit.
	}
}

export const useThemeStore = defineStore("theme", {
	state: () => ({
		hydrated: false,
		preference: "system" as ThemePreference,
		/** What prefers-color-scheme says right now; only meaningful once hydrated. */
		systemDark: false,
	}),

	getters: {
		resolved(state): ResolvedTheme {
			if (state.preference === "system") {
				return state.systemDark ? "dark" : "light";
			}

			return state.preference;
		},
	},

	actions: {
		hydrate() {
			if (this.hydrated || typeof window === "undefined") {
				return;
			}

			this.preference = readPreference();

			if (typeof window.matchMedia === "function") {
				const query = window.matchMedia(DARK_QUERY);

				this.systemDark = query.matches;
				query.addEventListener?.("change", (event) => {
					this.systemDark = event.matches;
					this.apply();
				});
			}

			this.hydrated = true;
			this.apply();
		},

		setPreference(value: ThemePreference) {
			this.preference = value;
			writePreference(value);
			this.apply();
		},

		/** Flips between light and dark from whatever is showing now; the result is always an explicit choice. */
		toggle() {
			this.setPreference(this.resolved === "dark" ? "light" : "dark");
		},

		apply() {
			if (typeof document === "undefined") {
				return;
			}

			const root = document.documentElement;

			if (this.preference === "system") {
				root.removeAttribute("data-theme");
			} else {
				root.setAttribute("data-theme", this.preference);
			}

			document.querySelector<HTMLMetaElement>('meta[name="theme-color"]')?.setAttribute("content", THEME_COLORS[this.resolved]);
		},
	},
});
