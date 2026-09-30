import { defineStore } from "pinia";

// Per-route API responses. Filled during pre-rendering and carried to the client through the SSG initial state, so a
// pre-rendered page hydrates without refetching; on client-side navigation entries are filled on demand.
export const useCacheStore = defineStore("cache", {
	state: () => ({
		entries: {} as Record<string, unknown>,
		misses: {} as Record<string, number>,
	}),

	actions: {
		set(key: string, value: unknown) {
			this.entries[key] = value;
			delete this.misses[key];
		},

		miss(key: string, status: number) {
			this.misses[key] = status;
			delete this.entries[key];
		},
	},
});
