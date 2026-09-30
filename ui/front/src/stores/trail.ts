import { defineStore } from "pinia";

import type { Mode } from "types/docs";

// The reader's recent path through the docs. Client-only (localStorage), capped, deduplicated by route.
export const TRAIL_KEY = "docs.trail";
export const TRAIL_LIMIT = 12;

export interface TrailEntry {
	route: string;
	title: string;
	mode: Mode | null;
	versionLabel: string;
	versionSortKey: number;
	at: number;
}

function read(): TrailEntry[] {
	if (typeof window === "undefined") {
		return [];
	}

	try {
		const raw = window.localStorage.getItem(TRAIL_KEY);
		const parsed = raw ? (JSON.parse(raw) as unknown) : [];

		return Array.isArray(parsed) ? (parsed as TrailEntry[]) : [];
	} catch {
		return [];
	}
}

function write(entries: TrailEntry[]): void {
	if (typeof window === "undefined") {
		return;
	}

	try {
		window.localStorage.setItem(TRAIL_KEY, JSON.stringify(entries));
	} catch {
		// Storage unavailable; the in-memory trail still works for this visit.
	}
}

export const useTrailStore = defineStore("trail", {
	state: () => ({
		hydrated: false,
		entries: [] as TrailEntry[],
	}),

	getters: {
		/** Most recent entries other than the given route, newest first. */
		recent:
			(state) =>
			(excludeRoute: string | null, limit = 3): TrailEntry[] =>
				state.entries.filter((e) => e.route !== excludeRoute).slice(0, limit),

		/** Whether the reader has visited any page at a version older than the given sort key. */
		hasVersionOlderThan:
			(state) =>
			(sortKey: number): boolean =>
				state.entries.some((e) => e.versionSortKey < sortKey),
	},

	actions: {
		hydrate() {
			if (this.hydrated) {
				return;
			}

			this.entries = read();
			this.hydrated = true;
		},

		record(entry: Omit<TrailEntry, "at">) {
			this.hydrate();

			const next = [{ ...entry, at: Date.now() }, ...this.entries.filter((e) => e.route !== entry.route)];

			this.entries = next.slice(0, TRAIL_LIMIT);
			write(this.entries);
		},

		clear() {
			this.entries = [];
			write(this.entries);
		},
	},
});
