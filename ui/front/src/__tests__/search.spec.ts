import { describe, expect, it } from "vitest";

import { affectsReader, buildIndex, describeSymbol, flattenGroups, matchesChip, removedNote, runSearch, toDocs } from "search/index";
import type { SearchRecord } from "search/index";
import type { DocVersion } from "types/docs";

const records: SearchRecord[] = [
	{ type: "symbol", ref: "tessel/query#QueryOptions.staleTime", name: "QueryOptions.staleTime", kind: "option", module: "tessel/query", summary: "Milliseconds a result stays fresh.", signature: "staleTime?: number = 30_000", status: "stable", state: "current", sinceLabel: "v2.0", removedLabel: null, replacedBy: null, route: "/v4.2/reference/tessel/query/QueryOptions.staleTime" },
	{ type: "symbol", ref: "tessel/cache#markStale", name: "markStale", kind: "fn", module: "tessel/cache", summary: "Mark one query stale without refetching it.", signature: "function markStale(key: QueryKey): void", status: "stable", state: "current", sinceLabel: "v2.0", removedLabel: null, replacedBy: null, route: "/v4.2/reference/tessel/cache/markStale" },
	{ type: "symbol", ref: "tessel/cache#useStaleWhileRevalidate", name: "useStaleWhileRevalidate", kind: "fn", module: "tessel/cache", summary: "Enable stale-while-revalidate globally.", signature: "function useStaleWhileRevalidate(): void", status: "deprecated", state: "removed", sinceLabel: "v2.0", removedLabel: "v4.0", replacedBy: "useLegacyCache removed", route: "/v4.2/reference/tessel/cache/useStaleWhileRevalidate" },
	{ type: "symbol", ref: "tessel/errors#StaleCacheError", name: "StaleCacheError", kind: "error", module: "tessel/errors", summary: "A read required fresh data but only stale data was available.", signature: "class StaleCacheError extends TesselError", status: "stable", state: "current", sinceLabel: "v2.0", removedLabel: null, replacedBy: null, route: "/v4.2/reference/tessel/errors/StaleCacheError" },
	{ type: "symbol", ref: "tessel/server#hydrate", name: "hydrate", kind: "fn", module: "tessel/server", summary: "Load a serialized cache into the client.", signature: "function hydrate(state): void", status: "stable", state: "current", sinceLabel: "v3.0", removedLabel: null, replacedBy: null, route: "/v4.2/reference/tessel/server/hydrate" },
	{ type: "page", mode: "do", slug: "show-cached-data-while-refetching", title: "Show cached data while refetching", summary: "Render the stale value immediately.", minutes: 3, route: "/v4.2/do/show-cached-data-while-refetching" },
	{ type: "page", mode: "explain", slug: "cache-lifecycle", title: "Fresh, stale, and garbage-collected: the cache lifecycle", summary: "What happens to a result.", minutes: null, route: "/v4.2/explain/cache-lifecycle" },
	{ type: "page", mode: "learn", slug: "keep-data-fresh-without-refetching", title: "Keep data fresh without refetching", summary: "Choose a staleTime.", minutes: 12, route: "/v4.2/learn/tessel-in-an-afternoon/keep-data-fresh-without-refetching" },
	{ type: "change", id: 1, kind: "breaking", title: "staleTime default: 0 → 30s", versionLabel: "v4.0", hasCodemod: true, codemodCmd: "pnpm dlx tessel-migrate stale-time ./src", why: "Most apps set staleTime by hand.", route: "/upgrade/v3.8/v4.0" },
	{ type: "change", id: 2, kind: "added", title: "createServerClient", versionLabel: "v4.1", hasCodemod: false, codemodCmd: null, why: "Request-scoped client.", route: "/upgrade/v4.0/v4.1" },
];

const versions: DocVersion[] = [
	{ id: 1, tag: "3.8.0", label: "v3.8", sortKey: 380, releasedAt: null, isLatest: false, isSupported: true },
	{ id: 2, tag: "4.0.0", label: "v4.0", sortKey: 400, releasedAt: null, isLatest: false, isSupported: true },
	{ id: 3, tag: "4.1.0", label: "v4.1", sortKey: 410, releasedAt: null, isLatest: false, isSupported: true },
	{ id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true },
];

const docs = toDocs(records);
const index = buildIndex(docs);

describe("search", () => {
	it("groups matches for 'stale' with the option symbol on top", () => {
		const groups = runSearch(index, docs, "stale", "all");
		const keys = groups.map((g) => g.key);

		expect(keys).toEqual(["symbols", "tasks", "concepts", "lessons"]);
		// The option named stale… and the error type named Stale… are a near tie; both must lead the group.
		expect(groups[0].items.slice(0, 2).map((d) => d.title)).toEqual(expect.arrayContaining(["QueryOptions.staleTime"]));
		expect(groups[0].items.map((d) => d.title)).toContain("useStaleWhileRevalidate");
		expect(groups[0].items.map((d) => d.title)).toContain("markStale");
		expect(groups[1].items[0].title).toBe("Show cached data while refetching");
		expect(groups[2].items.map((d) => d.title)).toContain("staleTime default: 0 → 30s");
		expect(groups[3].items[0].title).toBe("Keep data fresh without refetching");
	});

	it("finds dotted and camel-cased names by their parts", () => {
		expect(flattenGroups(runSearch(index, docs, "staletime", "reference")).map((d) => d.title)).toContain("QueryOptions.staleTime");
		expect(flattenGroups(runSearch(index, docs, "queryoptions", "reference"))[0].title).toBe("QueryOptions.staleTime");
		expect(flattenGroups(runSearch(index, docs, "Revalidate", "reference")).map((d) => d.title)).toContain("useStaleWhileRevalidate");
	});

	it("filters by chip", () => {
		expect(flattenGroups(runSearch(index, docs, "stale", "errors")).map((d) => d.title)).toEqual(["StaleCacheError"]);
		expect(flattenGroups(runSearch(index, docs, "stale", "changelog")).every((d) => d.type === "change")).toBe(true);
		expect(flattenGroups(runSearch(index, docs, "stale", "do")).every((d) => d.mode === "do")).toBe(true);
		expect(matchesChip(docs[0], "explain")).toBe(false);
	});

	it("returns a browse-style sample for an empty query", () => {
		const groups = runSearch(index, docs, "   ", "all");

		expect(groups.find((g) => g.key === "symbols")?.items.length).toBe(5);
		expect(groups.find((g) => g.key === "concepts")?.items.length).toBe(3);
	});

	it("describes option symbols by their value and functions by module", () => {
		expect(describeSymbol(docs[0])).toBe("number = 30_000");
		expect(describeSymbol(docs[1])).toBe("tessel/cache");
	});

	it("annotates removed symbols with the replacing change and an upgrade link from the previous version", () => {
		const note = removedNote(docs[2], versions);

		expect(note).toEqual({ label: "removed in v4.0", route: "/upgrade/v3.8/v4.0", replacedBy: "useLegacyCache removed" });
		expect(removedNote(docs[1], versions)).toBeNull();
	});

	it("flags changes that affect a reader who visited an older version", () => {
		const change = docs.find((d) => d.type === "change" && d.record.versionLabel === "v4.0")!;

		expect(affectsReader(change, versions, () => false)).toBeNull();
		expect(affectsReader(change, versions, (sortKey) => sortKey > 380)).toBe("affects you if upgrading from v3.8");
	});
});
