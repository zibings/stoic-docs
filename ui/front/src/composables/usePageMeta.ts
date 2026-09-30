import { useHead } from "@unhead/vue";
import { computed } from "vue";
import type { Ref } from "vue";

import { useSiteStore } from "stores/site";

/**
 * Per-page <title> and meta description, rendered into pre-rendered HTML by vite-ssg and kept in sync on the
 * client. The title reads "{title} · {library}"; pass the context (mode, module, version) inside `title`.
 */
export function usePageMeta(title: Ref<string | null>, description: Ref<string | null>) {
	const site = useSiteStore();

	useHead({
		title: computed(() => (title.value ? `${title.value} · ${site.libraryName}` : site.libraryName)),
		meta: computed(() => (description.value ? [{ name: "description", content: description.value }] : [])),
	});
}
