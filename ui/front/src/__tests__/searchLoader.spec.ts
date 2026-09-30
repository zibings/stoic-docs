import { createPinia, setActivePinia } from "pinia";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import { buildSearchFile } from "../../build/ssg";
import { getVersionIndex, resetIndexes } from "search/loader";

const records = [
	{ type: "symbol", ref: "tessel/query#createQuery", name: "createQuery", kind: "fn", module: "tessel/query", summary: "Declare data.", signature: "function createQuery()", state: "current", route: "/v4.2/reference/tessel/query/createQuery" },
	{ type: "page", mode: "do", slug: "show-cached", title: "Show cached data", summary: "", minutes: 3, route: "/v4.2/do/show-cached" },
];

const manifestMock = vi.fn(async () => ({ version: { label: "v4.2" }, previous: null, routes: [], searchRecords: records }));

vi.mock("api/docs", () => ({
	docsApi: { manifest: (...args: unknown[]) => manifestMock(...(args as [])) },
	axiosStatus: () => null,
	isNotFound: () => false,
}));

describe("search loader", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		resetIndexes();
		manifestMock.mockClear();
	});

	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it("loads the static per-version file when the build produced one", async () => {
		const file = buildSearchFile({ version: { id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, isLatest: true, isSupported: true }, routes: [], searchRecords: records as never });

		vi.stubGlobal(
			"fetch",
			vi.fn(async (url: string) => {
				expect(url).toBe("/search/v4.2.json");

				return new Response(JSON.stringify(file), { status: 200, headers: { "content-type": "application/json" } });
			}),
		);

		const loaded = await getVersionIndex("v4.2");

		expect(loaded.source).toBe("static");
		expect(loaded.docs).toHaveLength(2);
		expect(loaded.index.search("create")).toHaveLength(1);
		expect(manifestMock).not.toHaveBeenCalled();
	});

	it("falls back to the manifest endpoint when the static file is missing (dev server)", async () => {
		vi.stubGlobal("fetch", vi.fn(async () => new Response("<!doctype html>", { status: 200, headers: { "content-type": "text/html" } })));

		const loaded = await getVersionIndex("v4.2");

		expect(loaded.source).toBe("api");
		expect(manifestMock).toHaveBeenCalledTimes(1);
		expect(loaded.index.search("cached").map((h) => h.id)).toEqual(["page:do/show-cached"]);
	});

	it("caches the built index per version", async () => {
		vi.stubGlobal("fetch", vi.fn(async () => new Response("", { status: 404 })));

		const first = await getVersionIndex("v4.2");
		const second = await getVersionIndex("v4.2");

		expect(second).toBe(first);
		expect(manifestMock).toHaveBeenCalledTimes(1);
	});
});
