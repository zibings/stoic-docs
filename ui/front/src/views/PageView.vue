<template>
	<div class="shell-content page-view">
		<p v-if="notFound" class="muted">
			No {{ mode }} page <span class="mono">{{ slug }}</span> exists at {{ version }}.
			<RouterLink :to="`/${version}/${mode}`">Back to {{ mode }}</RouterLink>
		</p>
		<p v-else-if="error" class="muted">Could not load this page: {{ error }}</p>
		<p v-else-if="!data" class="muted">Loading…</p>

		<template v-else>
			<div class="page-head">
				<div class="label">{{ mode }}<template v-if="data.page.minutes"> · {{ data.page.minutes }} min</template></div>
				<h1 class="page-title">{{ data.page.title }}</h1>
				<p v-if="data.page.summary" class="page-summary">{{ data.page.summary }}</p>
			</div>

			<ProseBody :body="data.page.body" :context="renderContext" />
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";

import { docsApi } from "api/docs";
import ProseBody from "components/prose/ProseBody.vue";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import { useProseContext } from "composables/useProseContext";
import { useTrailStore } from "stores/trail";
import type { Mode } from "types/docs";

const props = defineProps<{ version: string; slug: string }>();
const route = useRoute();
const trail = useTrailStore();

const mode = computed<Mode>(() => route.meta.mode ?? "do");
const key = computed(() => `page:${props.version}:${mode.value}/${props.slug}`);
const { data, error, notFound } = useDocsData(key, () => docsApi.page(props.version, mode.value, props.slug));

const modeLabels: Record<Mode, string> = { learn: "Learn", do: "Do", reference: "Reference", explain: "Explain" };

usePageMeta(
	computed(() => (data.value ? `${data.value.page.title} · ${modeLabels[mode.value]} · ${props.version}` : null)),
	computed(() => data.value?.page.summary || null),
);

const versionLabel = computed(() => props.version);
const symbols = computed(() => data.value?.symbols ?? []);
const samples = computed(() => data.value?.samples ?? []);
const renderContext = useProseContext(versionLabel, symbols, samples);

watch(
	data,
	(value) => {
		if (!value || typeof window === "undefined") {
			return;
		}

		trail.record({
			route: value.page.route,
			title: value.page.title,
			mode: value.page.mode === "home" ? null : value.page.mode,
			versionLabel: value.version.label,
			versionSortKey: value.version.sortKey,
		});
	},
	{ immediate: true },
);
</script>

<style>
.page-head {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.page-summary {
	font-family: var(--font-serif);
	font-size: var(--text-summary);
	line-height: 1.4;
	color: var(--color-ink2);
	max-width: 720px;
}

.page-view .prose {
	max-width: 760px;
}
</style>
