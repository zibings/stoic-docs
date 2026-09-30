import { createHead } from "@unhead/vue/client";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import PrimeVue from "primevue/config";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { createMemoryHistory, createRouter } from "vue-router";

import UpgradeView from "views/UpgradeView.vue";
import { useReviewStore } from "stores/review";
import { useSiteStore } from "stores/site";

const diff = {
	from: { id: 1, tag: "3.8.0", label: "v3.8", sortKey: 380, releasedAt: null, isLatest: false, isSupported: true },
	to: { id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true },
	versions: [],
	total: 3,
	route: "/upgrade/v3.8/v4.2",
	groups: [
		{
			kind: "breaking",
			count: 2,
			changes: [
				{ id: 1, kind: "breaking", title: "staleTime default: 0 → 30s", versionLabel: "v4.0", hasCodemod: true, codemodCmd: "pnpm dlx tessel-migrate stale-time ./src", why: "Most apps set it by hand.", rfcUrl: "https://example.com/discussions/412", beforeCode: "createQuery(key, fetcher)", afterCode: "createQuery(key, fetcher)", symbols: [{ ref: "tessel/query#createQuery", name: "createQuery", kind: "fn", route: "/v4.2/reference/tessel/query/createQuery" }], contractDiffs: [] },
				{ id: 2, kind: "breaking", title: "QueryKey must be serializable", versionLabel: "v4.0", hasCodemod: false, codemodCmd: null, why: "", rfcUrl: null, beforeCode: null, afterCode: null, symbols: [], contractDiffs: [] },
			],
		},
		{ kind: "added", count: 1, changes: [{ id: 3, kind: "added", title: "createServerClient", versionLabel: "v4.1", hasCodemod: false, codemodCmd: null, why: "", rfcUrl: null, beforeCode: null, afterCode: null, symbols: [], contractDiffs: [] }] },
	],
};

vi.mock("api/docs", () => ({
	docsApi: { diff: vi.fn(async () => diff) },
	axiosStatus: () => null,
	isNotFound: () => false,
}));

describe("UpgradeView", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		window.localStorage.clear();
		useSiteStore().apply({ libraryName: "tessel", repoUrl: "", siteUrl: null, latest: "v4.2", versions: [diff.from, diff.to], contextOptions: { languages: [{ key: "ts", label: "TypeScript", isDefault: true }], packageManagers: [] }, modes: ["learn", "do", "reference", "explain"] });
	});

	async function mountView(hash = "") {
		const router = createRouter({ history: createMemoryHistory(), routes: [{ path: "/upgrade/:from/:to", name: "upgrade", component: UpgradeView, props: true }, { path: "/:pathMatch(.*)*", component: { template: "<div />" } }] });

		await router.push(`/upgrade/v3.8/v4.2${hash}`);

		const wrapper = mount(UpgradeView, { props: { from: "v3.8", to: "v4.2" }, global: { plugins: [router, createHead(), [PrimeVue, { unstyled: true }]] }, attachTo: document.body });

		await flushPromises();
		await flushPromises();

		return { wrapper, router };
	}

	it("lists changes grouped by kind, selects the first unreviewed, and renders its detail", async () => {
		const { wrapper } = await mountView();

		expect(wrapper.text()).toContain("3 changes · 2 breaking");
		expect(wrapper.findAll(".change-list__group-label").map((el) => el.text())).toEqual(["Breaking", "Added", "Breaking", "Added"]);
		expect(wrapper.find(".upgrade-layout__list .change-row.is-selected").text()).toContain("staleTime default");
		expect(wrapper.find(".change-detail__title").text()).toBe("staleTime default: 0 → 30s");
		expect(wrapper.find(".codemod__cmd").text()).toBe("pnpm dlx tessel-migrate stale-time ./src");
		expect(wrapper.find(".before-after__bar--before").text()).toBe("v3.8 · you read this");
		expect(wrapper.find("a.btn").text()).toBe("Open createQuery at v4.2");
		expect(wrapper.text()).toContain("RFC #412");
	});

	it("opens on the change named in the hash", async () => {
		const { wrapper } = await mountView("#change-3");

		expect(wrapper.find(".change-detail__title").text()).toBe("createServerClient");
	});

	it("moves with J and K, toggles reviewed with R, and advances on mark reviewed", async () => {
		const { wrapper, router } = await mountView();
		const review = useReviewStore();

		window.dispatchEvent(new KeyboardEvent("keydown", { key: "j" }));
		await flushPromises();
		expect(wrapper.find(".change-detail__title").text()).toBe("QueryKey must be serializable");
		expect(router.currentRoute.value.hash).toBe("#change-2");

		window.dispatchEvent(new KeyboardEvent("keydown", { key: "r" }));
		await flushPromises();
		expect(review.isReviewed(2)).toBe(true);
		expect(wrapper.text()).toContain("1 of 3 reviewed");

		window.dispatchEvent(new KeyboardEvent("keydown", { key: "k" }));
		await flushPromises();
		expect(wrapper.find(".change-detail__title").text()).toBe("staleTime default: 0 → 30s");

		await wrapper.find(".btn--primary").trigger("click");
		await flushPromises();
		expect(review.isReviewed(1)).toBe(true);
		expect(wrapper.find(".change-detail__title").text()).toBe("QueryKey must be serializable");
		expect(wrapper.text()).toContain("2 of 3 reviewed");
	});

	it("filters by chip", async () => {
		const { wrapper } = await mountView();

		const addedChip = wrapper.findAll(".upgrade-layout__list .chip").find((c) => c.text().startsWith("Added"))!;

		await addedChip.trigger("click");
		await flushPromises();

		expect(wrapper.findAll(".upgrade-layout__list .change-row")).toHaveLength(1);
		expect(wrapper.find(".change-detail__title").text()).toBe("createServerClient");
	});
});
