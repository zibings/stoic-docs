import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, it } from "vitest";

import { TRAIL_KEY, TRAIL_LIMIT, useTrailStore } from "stores/trail";

describe("trail store", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		window.localStorage.clear();
	});

	it("records visits newest first, deduplicates by route, and caps the length", () => {
		const trail = useTrailStore();

		for (let i = 0; i < TRAIL_LIMIT + 3; i++) {
			trail.record({ route: `/v4.2/do/p${i}`, title: `P${i}`, mode: "do", versionLabel: "v4.2", versionSortKey: 420 });
		}

		trail.record({ route: "/v4.2/do/p3", title: "P3 again", mode: "do", versionLabel: "v4.2", versionSortKey: 420 });

		expect(trail.entries).toHaveLength(TRAIL_LIMIT);
		expect(trail.entries[0].route).toBe("/v4.2/do/p3");
		expect(trail.entries.filter((e) => e.route === "/v4.2/do/p3")).toHaveLength(1);
		expect(JSON.parse(window.localStorage.getItem(TRAIL_KEY) ?? "[]")).toHaveLength(TRAIL_LIMIT);
	});

	it("lists recent entries excluding the current route", () => {
		const trail = useTrailStore();

		trail.record({ route: "/a", title: "A", mode: "explain", versionLabel: "v4.2", versionSortKey: 420 });
		trail.record({ route: "/b", title: "B", mode: "reference", versionLabel: "v4.2", versionSortKey: 420 });

		expect(trail.recent("/b", 3).map((e) => e.title)).toEqual(["A"]);
	});

	it("knows whether an older version was visited, driving upgrade-note visibility", () => {
		const trail = useTrailStore();

		trail.record({ route: "/v4.2/x", title: "X", mode: "do", versionLabel: "v4.2", versionSortKey: 420 });
		expect(trail.hasVersionOlderThan(400)).toBe(false);

		trail.record({ route: "/v3.8/y", title: "Y", mode: "do", versionLabel: "v3.8", versionSortKey: 380 });
		expect(trail.hasVersionOlderThan(400)).toBe(true);
	});

	it("restores from localStorage", () => {
		const first = useTrailStore();

		first.record({ route: "/a", title: "A", mode: null, versionLabel: "v4.2", versionSortKey: 420 });

		setActivePinia(createPinia());

		const second = useTrailStore();

		second.hydrate();

		expect(second.entries.map((e) => e.route)).toEqual(["/a"]);
	});
});
