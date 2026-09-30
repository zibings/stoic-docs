import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import PrimeVue from "primevue/config";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { createMemoryHistory, createRouter } from "vue-router";

import SearchPalette from "components/palette/SearchPalette.vue";
import routes from "@/router/routes";
import { resetIndexes } from "search/loader";
import { useSiteStore } from "stores/site";
import { useUiStore } from "stores/ui";

vi.mock("api/docs", () => ({
	docsApi: {
		manifest: vi.fn(async () => ({
			version: { id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true },
			previous: null,
			routes: [],
			searchRecords: [
				{ type: "symbol", ref: "tessel/query#QueryOptions.staleTime", name: "QueryOptions.staleTime", kind: "option", module: "tessel/query", summary: "Fresh window.", signature: "staleTime?: number = 30_000", status: "stable", state: "current", sinceLabel: "v2.0", removedLabel: null, replacedBy: null, route: "/v4.2/reference/tessel/query/QueryOptions.staleTime" },
				{ type: "symbol", ref: "tessel/cache#markStale", name: "markStale", kind: "fn", module: "tessel/cache", summary: "Mark stale.", signature: "function markStale(): void", status: "stable", state: "current", sinceLabel: "v2.0", removedLabel: null, replacedBy: null, route: "/v4.2/reference/tessel/cache/markStale" },
				{ type: "page", mode: "do", slug: "show-cached", title: "Show cached data while refetching", summary: "", minutes: 3, route: "/v4.2/do/show-cached" },
			],
		})),
	},
	axiosStatus: () => null,
	isNotFound: () => false,
}));

function makeRouter() {
	return createRouter({ history: createMemoryHistory(), routes });
}

describe("SearchPalette", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		resetIndexes();
		document.body.innerHTML = "";

		useSiteStore().apply({
			libraryName: "tessel",
			repoUrl: "",
			siteUrl: null,
			latest: "v4.2",
			versions: [{ id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true }],
			contextOptions: { languages: [{ key: "ts", label: "TypeScript", isDefault: true }], packageManagers: [] },
			modes: ["learn", "do", "reference", "explain"],
		});
	});

	async function openPalette() {
		const router = makeRouter();

		await router.push("/v4.2/reference");

		const wrapper = mount(SearchPalette, { global: { plugins: [router, [PrimeVue, { unstyled: true }]] }, attachTo: document.body });

		useUiStore().openSearch();
		await flushPromises();
		await flushPromises();

		return { wrapper, router };
	}

	it("opens with the version scope, builds the index, and lists grouped results as links", async () => {
		await openPalette();

		expect(document.body.textContent).toContain("scoped to v4.2 · TypeScript");
		expect(document.querySelectorAll(".search__group-label").length).toBeGreaterThan(0);
		expect(document.querySelector<HTMLAnchorElement>('a.search__item[href="/v4.2/do/show-cached"]')).not.toBeNull();
	});

	it("filters as you type, moves with arrows, and opens with Enter", async () => {
		const { router } = await openPalette();
		const push = vi.spyOn(router, "push");
		const input = document.querySelector<HTMLInputElement>("#palette-search-input")!;

		input.value = "stale";
		input.dispatchEvent(new Event("input", { bubbles: true }));
		await flushPromises();

		const items = [...document.querySelectorAll(".search__item")].map((el) => el.textContent?.trim() ?? "");
		expect(items[0]).toContain("QueryOptions.staleTime");

		const palette = document.querySelector(".palette")!;
		palette.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }));
		await flushPromises();
		expect(document.querySelectorAll(".search__item")[1].classList).toContain("is-active");

		palette.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true }));
		await flushPromises();

		expect(push).toHaveBeenCalledWith("/v4.2/reference/tessel/cache/markStale");
		expect(useUiStore().searchOpen).toBe(false);
	});

	it("cycles mode chips with Tab while the input has focus", async () => {
		await openPalette();

		const input = document.querySelector<HTMLInputElement>("#palette-search-input")!;
		const tab = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });

		input.dispatchEvent(tab);
		await flushPromises();

		expect(tab.defaultPrevented).toBe(true);
		expect(document.querySelector(".search__chip.is-active")?.textContent?.trim()).toBe("Reference");

		input.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab", shiftKey: true, bubbles: true, cancelable: true }));
		await flushPromises();
		expect(document.querySelector(".search__chip.is-active")?.textContent?.trim()).toBe("All");
	});

	it("routes ⌘. to the context-focus request", async () => {
		await openPalette();

		const before = useUiStore().contextFocusTick;
		document.querySelector(".palette")!.dispatchEvent(new KeyboardEvent("keydown", { key: ".", metaKey: true, bubbles: true }));
		await flushPromises();

		expect(useUiStore().contextFocusTick).toBe(before + 1);
		expect(useUiStore().searchOpen).toBe(false);
	});
});
