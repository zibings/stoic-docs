import { defineStore } from "pinia";

// Which changes the reader has marked reviewed, per upgrade range. Client-only (localStorage), no accounts.
export const REVIEW_KEY_PREFIX = "docs.reviewed:";

function storageKey(from: string, to: string): string {
	return `${REVIEW_KEY_PREFIX}${from}->${to}`;
}

function read(key: string): number[] {
	if (typeof window === "undefined") {
		return [];
	}

	try {
		const raw = window.localStorage.getItem(key);
		const parsed = raw ? (JSON.parse(raw) as unknown) : [];

		return Array.isArray(parsed) ? parsed.filter((v): v is number => typeof v === "number") : [];
	} catch {
		return [];
	}
}

function write(key: string, ids: number[]): void {
	if (typeof window === "undefined") {
		return;
	}

	try {
		window.localStorage.setItem(key, JSON.stringify(ids));
	} catch {
		// Storage unavailable; the in-memory marks still apply for this visit.
	}
}

export const useReviewStore = defineStore("review", {
	state: () => ({
		range: null as string | null,
		reviewed: [] as number[],
	}),

	getters: {
		isReviewed: (state) => (id: number): boolean => state.reviewed.includes(id),
		count: (state): number => state.reviewed.length,
	},

	actions: {
		/** Switches to a range's marks (loading them from storage). */
		load(from: string, to: string) {
			const key = storageKey(from, to);

			if (this.range === key) {
				return;
			}

			this.range = key;
			this.reviewed = read(key);
		},

		toggle(id: number) {
			this.reviewed = this.reviewed.includes(id) ? this.reviewed.filter((r) => r !== id) : [...this.reviewed, id];
			this.persist();
		},

		mark(id: number) {
			if (!this.reviewed.includes(id)) {
				this.reviewed = [...this.reviewed, id];
				this.persist();
			}
		},

		persist() {
			if (this.range) {
				write(this.range, this.reviewed);
			}
		},
	},
});
