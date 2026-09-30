import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import PrimeVue from "primevue/config";
import { beforeEach, describe, expect, it } from "vitest";

import ResourceTable from "components/ResourceTable.vue";
import { useCatalogStore } from "stores/catalog";

describe("ResourceTable", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		useCatalogStore().versions = [{ id: 4, tag: "4.2.0", label: "v4.2", sortKey: 420, releasedAt: null, isLatest: true, isSupported: true }];
	});

	it("renders rows with typed cells, filters, and emits edit and delete", async () => {
		const rows = [
			{ id: 1, name: "createQuery", isLatest: true, versionId: 4 },
			{ id: 2, name: "prefetch", isLatest: false, versionId: 9 },
		];
		const wrapper = mount(ResourceTable, {
			props: {
				title: "Things",
				rows,
				columns: [
					{ field: "name", header: "Name", kind: "mono" },
					{ field: "isLatest", header: "Latest", kind: "bool" },
					{ field: "versionId", header: "Version", kind: "version" },
				],
				createLabel: "New thing",
			},
			global: { plugins: [[PrimeVue, { unstyled: true }]] },
		});

		expect(wrapper.text()).toContain("createQuery");
		expect(wrapper.text()).toContain("v4.2");
		expect(wrapper.text()).toContain("#9");
		expect(wrapper.findAll('[aria-label="Edit"]')).toHaveLength(2);

		await wrapper.findAll('[aria-label="Edit"]')[1].trigger("click");
		expect(wrapper.emitted("edit")?.[0]).toEqual([rows[1]]);

		await wrapper.findAll('[aria-label="Delete"]')[0].trigger("click");
		expect(wrapper.emitted("delete")?.[0]).toEqual([rows[0]]);

		const search = wrapper.find('input[placeholder="Filter"]');
		await search.setValue("prefetch");
		expect(wrapper.text()).not.toContain("createQuery");
		expect(wrapper.text()).toContain("prefetch");

		await wrapper.find("button.p-button, button").trigger("click");
	});
});
