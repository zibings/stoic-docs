import { describe, expect, it } from "vitest";

import { flattenContract, pickActiveRef } from "contract/flatten";
import type { SymbolNode } from "types/docs";

function node(partial: Partial<SymbolNode> & { ref: string; shortName: string }): SymbolNode {
	return {
		name: partial.shortName,
		kind: "fn",
		state: "current",
		sinceLabel: "v2.0",
		changedLabel: null,
		removedLabel: null,
		route: "/",
		children: [],
		...partial,
		contract: {
			id: 1, symbolId: 1, introducedVersionId: 1, removedVersionId: null, introducedLabel: "v2.0", removedLabel: null,
			signature: "", summary: "", status: "stable", params: [], returns: {}, throws: [], sourcePath: null, sourceLine: null, sourceUrl: null,
			...(partial.contract ?? {}),
		},
	};
}

const createQuery = node({
	ref: "tessel/query#createQuery",
	shortName: "createQuery",
	contract: {
		params: [
			{ name: "key", type: "QueryKey", required: true, default: null, description: "Serializable key." },
			{ name: "fetcher", type: "(ctx: QueryContext) => Promise<T>", required: true, default: null, description: "Loads data." },
			{ name: "options", type: "QueryOptions<T>", required: false, default: null, description: "Overrides." },
		],
	} as SymbolNode["contract"],
});

const queryOptions = node({
	ref: "tessel/query#QueryOptions",
	shortName: "QueryOptions",
	kind: "type",
	contract: {
		params: [
			{ name: "enabled", type: "boolean", required: false, default: "true", description: "May run." },
			{ name: "staleTime", type: "number", required: false, default: "30_000", description: "Fresh window." },
			{ name: "legacy", type: "boolean", required: false, default: "false", description: "Gone." },
		],
	} as SymbolNode["contract"],
	children: [
		node({ ref: "tessel/query#QueryOptions.enabled", shortName: "enabled", kind: "option", contract: { summary: "Whether the query may run at all." } as SymbolNode["contract"] }),
		node({ ref: "tessel/query#QueryOptions.staleTime", shortName: "staleTime", kind: "option", changedLabel: "v4.0", contract: { summary: "Milliseconds a result stays fresh. Was 0 before v4.0." } as SymbolNode["contract"] }),
		node({ ref: "tessel/query#QueryOptions.legacy", shortName: "legacy", kind: "option", state: "removed", removedLabel: "v4.0" }),
	],
});

describe("flattenContract", () => {
	it("expands a related-type parameter into one row per live child, keyed by the child's ref", () => {
		const rows = flattenContract(createQuery, { QueryOptions: queryOptions, QueryKey: node({ ref: "tessel/query#QueryKey", shortName: "QueryKey", kind: "type" }) });

		expect(rows.map((r) => r.label)).toEqual(["key", "fetcher", "options.enabled", "options.staleTime"]);
		expect(rows[0]).toMatchObject({ ref: "tessel/query#createQuery.key", required: true, type: "QueryKey" });
		expect(rows[3]).toMatchObject({ ref: "tessel/query#QueryOptions.staleTime", type: "number", default: "30_000", changedLabel: "v4.0" });
		expect(rows[3].description).toContain("Was 0 before v4.0");
	});

	it("keeps plain parameters when no related type is known", () => {
		const rows = flattenContract(createQuery, {});

		expect(rows.map((r) => r.label)).toEqual(["key", "fetcher", "options"]);
	});
});

describe("pickActiveRef", () => {
	it("returns null when nothing is on screen", () => {
		expect(pickActiveRef([{ ref: "a", top: 1200, bottom: 1220 }], 900)).toBeNull();
	});

	it("picks the last element that has crossed the reading line", () => {
		const positions = [
			{ ref: "a", top: 40, bottom: 60 },
			{ ref: "b", top: 250, bottom: 270 },
			{ ref: "c", top: 700, bottom: 720 },
		];

		expect(pickActiveRef(positions, 900)).toBe("b");
	});

	it("falls back to the first visible element when none has crossed the line", () => {
		expect(pickActiveRef([{ ref: "c", top: 700, bottom: 720 }, { ref: "b", top: 500, bottom: 520 }], 900)).toBe("b");
	});
});
