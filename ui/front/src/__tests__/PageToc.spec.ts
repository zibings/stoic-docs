import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import PageToc from "components/prose/PageToc.vue";

const headings = [
	{ level: 2 as const, id: "install", text: "Install" },
	{ level: 3 as const, id: "with-pnpm", text: "With pnpm" },
	{ level: 2 as const, id: "first-query", text: "First query" },
];

describe("PageToc", () => {
	it("lists every heading as an in-page link, indents h3, and marks the active one", () => {
		const wrapper = mount(PageToc, { props: { headings, activeId: "with-pnpm" } });
		const links = wrapper.findAll("a");

		expect(links.map((a) => a.attributes("href"))).toEqual(["#install", "#with-pnpm", "#first-query"]);
		expect(wrapper.findAll(".toc__item--h3")).toHaveLength(1);
		expect(wrapper.find(".is-active a").text()).toBe("With pnpm");
		expect(wrapper.find(".is-active a").attributes("aria-current")).toBe("location");
	});

	it("emits navigate with the heading id when a link is clicked", async () => {
		const wrapper = mount(PageToc, { props: { headings, activeId: null } });

		await wrapper.findAll("a")[2].trigger("click");

		expect(wrapper.emitted("navigate")).toEqual([["first-query"]]);
	});
});
