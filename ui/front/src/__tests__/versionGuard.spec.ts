import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, it } from "vitest";
import { createMemoryHistory, createRouter } from "vue-router";
import type { RouteRecordRaw } from "vue-router";

import { installVersionGuard } from "@/router";
import routes from "@/router/routes";
import { useSiteStore } from "stores/site";

// The real route table lazy-loads view components; swap them for stubs so nothing keeps loading after teardown.
const Stub = { template: "<div />" };

function stubRoutes(records: RouteRecordRaw[]): RouteRecordRaw[] {
	return records.map((record) => {
		const copy = { ...record } as RouteRecordRaw & { component?: unknown; children?: RouteRecordRaw[] };

		if ("component" in copy && copy.component) {
			copy.component = Stub;
		}

		if (copy.children) {
			copy.children = stubRoutes(copy.children);
		}

		return copy as RouteRecordRaw;
	});
}

function makeRouter() {
	const router = createRouter({ history: createMemoryHistory(), routes: stubRoutes(routes) });

	installVersionGuard(router);

	return router;
}

describe("version guard", () => {
	beforeEach(() => {
		setActivePinia(createPinia());

		const site = useSiteStore();

		site.apply({
			libraryName: "tessel",
			repoUrl: "",
			siteUrl: null,
			latest: "v4.2",
			versions: [
				{ id: 1, tag: "3.8.0", label: "v3.8", sortKey: 380, releasedAt: null, isLatest: false, isSupported: true },
				{ id: 2, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true },
			],
			contextOptions: { languages: [], packageManagers: [] },
			modes: ["learn", "do", "reference", "explain"],
		});
	});

	it("rewrites the latest alias to the latest label", async () => {
		const router = makeRouter();

		await router.push("/latest/reference/tessel/query/createQuery");

		expect(router.currentRoute.value.path).toBe("/v4.2/reference/tessel/query/createQuery");
		expect(router.currentRoute.value.name).toBe("reference-symbol");
	});

	it("sends the root to the landing page at the latest version", async () => {
		const router = makeRouter();

		await router.push("/");

		expect(router.currentRoute.value.path).toBe("/v4.2");
		expect(router.currentRoute.value.name).toBe("home");
	});

	it("rewrites latest in upgrade ranges too", async () => {
		const router = makeRouter();

		await router.push("/upgrade/v3.8/latest");

		expect(router.currentRoute.value.path).toBe("/upgrade/v3.8/v4.2");
	});

	it("routes unknown versions to not-found and keeps the path", async () => {
		const router = makeRouter();

		await router.push("/v9.9/do/anything");

		expect(router.currentRoute.value.name).toBe("not-found");
		expect(router.currentRoute.value.path).toBe("/v9.9/do/anything");
	});

	it("exposes the mode through route meta", async () => {
		const router = makeRouter();

		await router.push("/v4.2/explain/cache-lifecycle");

		expect(router.currentRoute.value.meta.mode).toBe("explain");
		expect(router.currentRoute.value.params.slug).toBe("cache-lifecycle");
	});
});
