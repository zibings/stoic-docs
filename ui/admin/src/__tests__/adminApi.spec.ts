import { describe, expect, it } from "vitest";

import { resourcePath } from "api/admin";

describe("admin api paths", () => {
	it("maps resources to the controller's routes", () => {
		expect(resourcePath("versions")).toBe("/1.1/Docs/Admin/Versions");
		expect(resourcePath("contextOptions", 7)).toBe("/1.1/Docs/Admin/ContextOptions/7");
		expect(resourcePath("contracts", 12)).toBe("/1.1/Docs/Admin/Contracts/12");
	});
});
