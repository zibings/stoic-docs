import { computed, onMounted, onServerPrefetch, ref, watch } from "vue";
import type { Ref } from "vue";

import { axiosStatus } from "api/docs";
import { useCacheStore } from "stores/cache";

/**
 * Loads one API response for a view. On the server the load runs before render and the result travels in the
 * initial state; on the client the cached value is used when present and fetched otherwise. `key` must change
 * whenever the request parameters do.
 */
export function useDocsData<T>(key: Ref<string>, loader: () => Promise<T>) {
	const cache = useCacheStore();
	const loading = ref(false);
	const error = ref<string | null>(null);

	const data = computed(() => cache.entries[key.value] as T | undefined);
	const status = computed(() => cache.misses[key.value] ?? null);
	const notFound = computed(() => status.value === 404);

	async function load(): Promise<void> {
		if (cache.entries[key.value] !== undefined || cache.misses[key.value] !== undefined) {
			return;
		}

		loading.value = true;
		error.value = null;

		try {
			cache.set(key.value, await loader());
		} catch (err) {
			const code = axiosStatus(err);

			if (code !== null) {
				cache.miss(key.value, code);
			} else {
				error.value = err instanceof Error ? err.message : String(err);
			}
		} finally {
			loading.value = false;
		}
	}

	onServerPrefetch(load);
	onMounted(load);
	watch(key, load);

	return { data, loading, error, notFound, status, reload: load };
}
