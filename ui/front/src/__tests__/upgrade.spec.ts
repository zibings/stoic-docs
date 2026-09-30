import { describe, expect, it } from "vitest";

import { affectedSummary, allChanges, changeIdFromHash, contractDiffLines, defaultSelection, hashForChange, kindCounts, parseScanReport, stepChange, visibleChanges } from "upgrade/diff";
import type { Contract, DiffChange, DiffResponse } from "types/docs";

function change(id: number, kind: string, title: string, symbols: string[] = []): DiffChange {
	return {
		id,
		kind,
		title,
		versionLabel: "v4.0",
		hasCodemod: false,
		codemodCmd: null,
		why: "",
		rfcUrl: null,
		beforeCode: null,
		afterCode: null,
		symbols: symbols.map((ref) => ({ ref, name: ref.slice(ref.indexOf("#") + 1), kind: "fn", route: `/v4.2/reference/${ref.replace("#", "/")}` })),
		contractDiffs: [],
	};
}

const diff: DiffResponse = {
	from: { id: 1, tag: "3.8.0", label: "v3.8", sortKey: 380, releasedAt: null, isLatest: false, isSupported: true },
	to: { id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true },
	versions: [],
	total: 4,
	route: "/upgrade/v3.8/v4.2",
	groups: [
		{ kind: "added", count: 1, changes: [change(4, "added", "createServerClient", ["tessel/server#createServerClient"])] },
		{ kind: "breaking", count: 2, changes: [change(1, "breaking", "staleTime default", ["tessel/query#QueryOptions.staleTime", "tessel/query#createQuery"]), change(2, "breaking", "keys serializable", ["tessel/query#QueryKey"])] },
		{ kind: "behavior", count: 1, changes: [change(3, "behavior", "no focus refetch", ["tessel/query#QueryOptions"])] },
	],
};

function contract(params: Contract["params"], signature = "sig"): Contract {
	return { id: 1, symbolId: 1, introducedVersionId: 1, removedVersionId: null, introducedLabel: "v2.0", removedLabel: null, signature, summary: "", status: "stable", params, returns: {}, throws: [], sourcePath: null, sourceLine: null, sourceUrl: null };
}

describe("upgrade diff helpers", () => {
	it("orders changes by kind then keeps API order, and counts kinds in that order", () => {
		expect(allChanges(diff).map((c) => c.id)).toEqual([1, 2, 3, 4]);
		expect(kindCounts(diff)).toEqual([{ kind: "breaking", count: 2 }, { kind: "behavior", count: 1 }, { kind: "added", count: 1 }]);
	});

	it("filters by chip and by the imports in a scan report", () => {
		const report = parseScanReport(JSON.stringify({ tool: "scan", imports: [{ ref: "tessel/query#createQuery", callSites: 23, files: 9 }, { ref: "tessel/query#QueryKey" }] }));
		expect(report.fromVersion).toBeNull();
		expect(report.package).toBeNull();

		const scanned = parseScanReport(JSON.stringify({ tool: "scan-usage.php", package: "acme/tessel", fromVersion: "3.8.0", imports: [] }));
		expect(scanned.package).toBe("acme/tessel");
		expect(scanned.fromVersion).toBe("3.8.0");

		expect(visibleChanges(diff, "breaking", false, null).map((c) => c.id)).toEqual([1, 2]);
		expect(visibleChanges(diff, "all", true, report).map((c) => c.id)).toEqual([1, 2]);
		expect(visibleChanges(diff, "all", true, null).map((c) => c.id)).toEqual([1, 2, 3, 4]);
		expect(affectedSummary(allChanges(diff)[0], report)).toEqual({ callSites: 23, files: 9 });
		expect(affectedSummary(allChanges(diff)[2], report)).toBeNull();
	});

	it("steps through the visible list with clamping and picks the first unreviewed by default", () => {
		const visible = allChanges(diff);

		expect(stepChange(visible, 1, 1)).toBe(2);
		expect(stepChange(visible, 4, 1)).toBe(4);
		expect(stepChange(visible, 1, -1)).toBe(1);
		expect(stepChange(visible, null, 1)).toBe(1);
		expect(stepChange([], null, 1)).toBeNull();
		expect(defaultSelection(visible, (id) => id <= 2)).toBe(3);
		expect(defaultSelection(visible, () => true)).toBe(1);
	});

	it("round-trips the selection through the hash", () => {
		expect(hashForChange(12)).toBe("#change-12");
		expect(changeIdFromHash("#change-12")).toBe(12);
		expect(changeIdFromHash("#other")).toBeNull();
		expect(changeIdFromHash("")).toBeNull();
	});

	it("renders parameter changes as unified lines with unchanged context", () => {
		const before = contract([
			{ name: "staleTime", type: "number", required: false, default: "0", description: "" },
			{ name: "gcTime", type: "number", required: false, default: "300_000", description: "" },
			{ name: "legacy", type: "boolean", required: false, default: "false", description: "" },
		]);
		const after = contract([
			{ name: "staleTime", type: "number", required: false, default: "30_000", description: "" },
			{ name: "gcTime", type: "number", required: false, default: "300_000", description: "" },
			{ name: "signal", type: "AbortSignal", required: false, default: null, description: "" },
		]);

		const lines = contractDiffLines({
			ref: "tessel/query#QueryOptions",
			name: "QueryOptions",
			before,
			after,
			signatureChanged: true,
			paramsAdded: [after.params[2]],
			paramsChanged: [{ name: "staleTime", before: before.params[0], after: after.params[0] }],
			paramsRemoved: [before.params[2]],
		});

		expect(lines).toEqual([
			{ kind: "-", text: "staleTime?: number        // default 0" },
			{ kind: "+", text: "staleTime?: number        // default 30_000" },
			{ kind: " ", text: "gcTime?: number        // default 300_000" },
			{ kind: "+", text: "signal?: AbortSignal" },
			{ kind: "-", text: "legacy?: boolean        // default false" },
		]);
	});

	it("falls back to signature lines when only the signature changed, and to +/- when a contract is absent", () => {
		const before = contract([], "function invalidate(key: string): Promise<void>");
		const after = contract([], "function invalidate(key: QueryKey): Promise<void>");

		expect(contractDiffLines({ ref: "r", name: "n", before, after, signatureChanged: true, paramsAdded: [], paramsChanged: [], paramsRemoved: [] })).toEqual([
			{ kind: "-", text: "function invalidate(key: string): Promise<void>" },
			{ kind: "+", text: "function invalidate(key: QueryKey): Promise<void>" },
		]);
		expect(contractDiffLines({ ref: "r", name: "n", before: null, after, signatureChanged: true, paramsAdded: [], paramsChanged: [], paramsRemoved: [] })[0].kind).toBe("+");
		expect(contractDiffLines({ ref: "r", name: "n", before, after: null, signatureChanged: true, paramsAdded: [], paramsChanged: [], paramsRemoved: [] })[0].kind).toBe("-");
	});

	it("rejects malformed scan reports with readable messages", () => {
		expect(() => parseScanReport("not json")).toThrow(/valid JSON/);
		expect(() => parseScanReport("{}")).toThrow(/imports/);
		expect(() => parseScanReport(JSON.stringify({ imports: [{ ref: "no-hash" }] }))).toThrow(/module\/path#Name/);
		expect(parseScanReport(JSON.stringify({ imports: [] })).imports.size).toBe(0);
	});
});
