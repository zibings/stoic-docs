import { describe, expect, it } from "vitest";

import { buildPagesTree, buildReferenceTree, defaultExpandedKeys, flattenNodes } from "browse/tree";
import type { SearchRecord } from "search/index";
import type { SymbolNode, TreeResponse } from "types/docs";

function sym(ref: string, kind: string, state: "current" | "removed" = "current", children: SymbolNode[] = []): SymbolNode {
	const name = ref.slice(ref.indexOf("#") + 1);

	return {
		ref,
		name,
		shortName: name.includes(".") ? name.slice(name.lastIndexOf(".") + 1) : name,
		kind,
		state,
		sinceLabel: "v2.0",
		changedLabel: null,
		removedLabel: state === "removed" ? "v4.0" : null,
		route: `/v4.2/reference/${ref.replace("#", "/")}`,
		contract: { id: 1, symbolId: 1, introducedVersionId: 1, removedVersionId: null, introducedLabel: "v2.0", removedLabel: null, signature: `function ${name}(): void`, summary: `${name} summary`, status: "stable", params: [], returns: {}, throws: [], sourcePath: null, sourceLine: null, sourceUrl: null },
		children,
	};
}

const tree: TreeResponse = {
	version: { id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true },
	modules: [
		{ path: "tessel/query", summary: "Queries", count: 3, symbols: [sym("tessel/query#createQuery", "fn"), sym("tessel/query#QueryOptions", "type", "current", [sym("tessel/query#QueryOptions.staleTime", "option")]), sym("tessel/query#useLegacyCache", "fn", "removed")] },
		{ path: "tessel/cache", summary: "Cache", count: 1, symbols: [sym("tessel/cache#markStale", "fn")] },
	],
};

describe("buildReferenceTree", () => {
	it("builds modules with symbols and nested children, marking the current route", () => {
		const nodes = buildReferenceTree(tree, { includeRemoved: true, filter: "", currentRoute: "/v4.2/reference/tessel/query/createQuery", versionLabel: "v4.2" });

		expect(nodes.map((n) => n.label)).toEqual(["tessel/query", "tessel/cache"]);
		expect(nodes[0].data.count).toBe(3);
		expect(nodes[0].children![0].data.current).toBe(true);
		expect(nodes[0].children![1].children![0].key).toBe("tessel/query#QueryOptions.staleTime");
		expect(nodes[0].children![2].data.state).toBe("removed");
	});

	it("drops removed symbols when asked", () => {
		const nodes = buildReferenceTree(tree, { includeRemoved: false, filter: "", currentRoute: null, versionLabel: "v4.2" });

		expect(nodes[0].data.count).toBe(2);
		expect(flattenNodes(nodes).some((n) => n.data.state === "removed")).toBe(false);
	});

	it("filters to matching symbols and keeps their ancestors", () => {
		const nodes = buildReferenceTree(tree, { includeRemoved: true, filter: "stale", currentRoute: null, versionLabel: "v4.2" });
		const labels = flattenNodes(nodes).map((n) => n.label);

		expect(labels).toEqual(["tessel/query", "QueryOptions", "staleTime", "tessel/cache", "markStale"]);
		expect(defaultExpandedKeys(nodes, true)).toEqual({ "module:tessel/query": true, "tessel/query#QueryOptions": true, "module:tessel/cache": true });
	});

	it("expands the module holding the current symbol by default, else the first module", () => {
		const current = buildReferenceTree(tree, { includeRemoved: true, filter: "", currentRoute: "/v4.2/reference/tessel/cache/markStale", versionLabel: "v4.2" });
		expect(defaultExpandedKeys(current, false)).toEqual({ "module:tessel/cache": true });

		const none = buildReferenceTree(tree, { includeRemoved: true, filter: "", currentRoute: null, versionLabel: "v4.2" });
		expect(defaultExpandedKeys(none, false)).toEqual({ "module:tessel/query": true });
	});
});

describe("buildPagesTree", () => {
	const records: SearchRecord[] = [
		{ type: "page", mode: "do", slug: "a", title: "Task A", summary: "", minutes: 3, route: "/v4.2/do/a" },
		{ type: "page", mode: "learn", slug: "l1", title: "Lesson 1", summary: "", minutes: 8, route: "/v4.2/learn/course-x/l1" },
		{ type: "page", mode: "learn", slug: "l2", title: "Lesson 2", summary: "", minutes: 10, route: "/v4.2/learn/course-x/l2" },
		{ type: "page", mode: "learn", slug: "m1", title: "Other 1", summary: "", minutes: 5, route: "/v4.2/learn/course-y/m1" },
		{ type: "symbol", ref: "x#y", name: "y", route: "/x" },
	];

	it("lists do pages flat and groups learn pages by course", () => {
		expect(buildPagesTree(records, "do", "", null).map((n) => n.label)).toEqual(["Task A"]);

		const learn = buildPagesTree(records, "learn", "", "/v4.2/learn/course-x/l2");

		expect(learn.map((n) => n.label)).toEqual(["course x", "course y"]);
		expect(learn[0].data.count).toBe(2);
		expect(learn[0].children![1].data.current).toBe(true);
		expect(defaultExpandedKeys(learn, false)).toEqual({ "course:course-x": true });
	});

	it("filters pages by title", () => {
		expect(buildPagesTree(records, "learn", "other", null).map((n) => n.label)).toEqual(["course y"]);
	});
});
