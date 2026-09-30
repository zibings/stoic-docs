import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, it } from "vitest";

import { useUiStore } from "stores/ui";

describe("ui store", () => {
	beforeEach(() => setActivePinia(createPinia()));

	it("opens browse on a requested mode and closes search", () => {
		const ui = useUiStore();

		ui.openSearch();
		ui.openBrowse("learn");

		expect(ui.searchOpen).toBe(false);
		expect(ui.browseOpen).toBe(true);
		expect(ui.browseMode).toBe("learn");

		ui.openBrowse();
		expect(ui.browseMode).toBeNull();
	});
});
