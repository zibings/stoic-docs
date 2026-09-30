import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import { createMemoryHistory, createRouter } from "vue-router";

import ModeTabs from "components/header/ModeTabs.vue";

const router = createRouter({
	history: createMemoryHistory(),
	routes: [{ path: "/:pathMatch(.*)*", component: { template: "<div />" } }],
});

describe("ModeTabs", () => {
	it("links every mode into the given version and marks the active one", async () => {
		const wrapper = mount(ModeTabs, {
			props: { modes: ["learn", "do", "reference", "explain"], active: "reference", version: "v4.2" },
			global: { plugins: [router] },
		});

		const links = wrapper.findAll("a");

		expect(links.map((l) => l.attributes("href"))).toEqual(["/v4.2/learn", "/v4.2/do", "/v4.2/reference", "/v4.2/explain"]);
		expect(links[2].classes()).toContain("is-active");
		expect(links[2].attributes("aria-current")).toBe("page");
		expect(links[0].classes()).not.toContain("is-active");
		expect(links[0].attributes("aria-current")).toBeUndefined();
	});

	it("marks nothing active when the route has no mode", () => {
		const wrapper = mount(ModeTabs, {
			props: { modes: ["learn", "do", "reference", "explain"], active: undefined, version: "latest" },
			global: { plugins: [router] },
		});

		expect(wrapper.findAll(".is-active")).toHaveLength(0);
		expect(wrapper.find("a").attributes("href")).toBe("/latest/learn");
	});
});
