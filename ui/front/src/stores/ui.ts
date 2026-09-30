import { defineStore } from "pinia";

import type { Mode } from "types/docs";

// Cross-cutting UI state: which palette is open, a contract-pane override, and a request to focus the context bar.
export const useUiStore = defineStore("ui", {
	state: () => ({
		searchOpen: false,
		browseOpen: false,
		/** Mode tab the browse palette should open on; null means the current route's mode. */
		browseMode: null as Mode | null,
		/** Symbol ref shown in the contract pane instead of the page's own symbol (⌘↵ from a palette). */
		paneOverrideRef: null as string | null,
		/** Incremented to ask the header to focus the version picker (⌘.). */
		contextFocusTick: 0,
	}),

	actions: {
		openSearch() {
			this.browseOpen = false;
			this.searchOpen = true;
		},

		openBrowse(mode: Mode | null = null) {
			this.searchOpen = false;
			this.browseMode = mode;
			this.browseOpen = true;
		},

		closePalettes() {
			this.searchOpen = false;
			this.browseOpen = false;
		},

		openInPane(ref: string) {
			this.paneOverrideRef = ref;
		},

		clearPaneOverride() {
			this.paneOverrideRef = null;
		},

		requestContextFocus() {
			this.closePalettes();
			this.contextFocusTick += 1;
		},
	},
});
