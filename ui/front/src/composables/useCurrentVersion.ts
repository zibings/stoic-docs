import { computed } from "vue";
import { useRoute } from "vue-router";

import { useSiteStore } from "stores/site";
import type { DocVersion } from "types/docs";

/**
 * The version the reader is looking at, taken from the route. On the upgrade view the "to" version is current and
 * "from" is exposed separately so the context bar can show the range.
 */
export function useCurrentVersion() {
	const route = useRoute();
	const site = useSiteStore();

	const label = computed<string | null>(() => {
		const raw = route.params.to ?? route.params.version;

		return typeof raw === "string" ? raw : null;
	});

	const fromLabel = computed<string | null>(() => (typeof route.params.from === "string" ? route.params.from : null));
	const version = computed<DocVersion | undefined>(() => (label.value ? site.versionByLabel(label.value) : undefined));
	const isUpgrade = computed(() => route.name === "upgrade");

	return { label, fromLabel, version, isUpgrade };
}
