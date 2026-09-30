import { describe, expect, it } from "vitest";

import { blank, slugify, toPayload, validate } from "lib/forms";

describe("forms", () => {
	it("reports missing required fields with readable messages", () => {
		expect(validate("versions", { tag: "", label: "v1", sortKey: 1 })).toEqual({ tag: "Tag is required." });
		expect(validate("contracts", { symbolId: 1, introducedVersionId: 2, removedVersionId: 2, signature: "x" })).toEqual({ removedVersionId: "Removed version must differ from the introduced version." });
		expect(validate("versions", { tag: "1", label: "v1", sortKey: "abc" })).toEqual({ sortKey: "Sort key must be a whole number." });
		expect(validate("pages", { mode: "do", slug: "a", title: "b", introducedVersionId: 3 })).toEqual({});
	});

	it("provides sensible blanks for every resource", () => {
		expect(blank("versions")).toMatchObject({ isSupported: true, isLatest: false });
		expect(blank("contracts")).toMatchObject({ status: "stable", params: [], throws: [] });
		expect(blank("symbols")).toMatchObject({ kind: "fn", parentSymbolId: null });
	});

	it("strips server-assigned fields from payloads", () => {
		expect(toPayload({ id: 4, ref: "a#b", created: "x", updated: "y", name: "n" })).toEqual({ name: "n" });
	});

	it("slugifies titles", () => {
		expect(slugify("Keep data fresh, without refetching!")).toBe("keep-data-fresh-without-refetching");
		expect(slugify("  Écrire des données ")).toBe("ecrire-des-donnees");
	});
});
