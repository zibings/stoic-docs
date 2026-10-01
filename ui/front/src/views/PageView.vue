<template>
	<div class="page-layout" :class="{ 'page-layout--toc': hasToc }">
		<MobileToc v-if="hasToc" class="page-layout__mobile-toc" :headings="headings" :active-id="activeId" />
		<TocRail v-if="hasToc" class="page-layout__rail" :headings="headings" :active-id="activeId" @browse="ui.openBrowse()" />

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

				<ProseBody ref="prose" :body="data.page.body" :context="renderContext" />
			</template>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";

import { docsApi } from "api/docs";
import MobileToc from "components/prose/MobileToc.vue";
import ProseBody from "components/prose/ProseBody.vue";
import TocRail from "components/prose/TocRail.vue";
import { useDocsData } from "composables/useDocsData";
import { useHeadingFollow } from "composables/useHeadingFollow";
import { usePageMeta } from "composables/usePageMeta";
import { useProseContext } from "composables/useProseContext";
import { docOutline } from "markdown/render";
import { useTrailStore } from "stores/trail";
import { useUiStore } from "stores/ui";
import type { Mode } from "types/docs";

const props = defineProps<{ version: string; slug: string }>();
const route = useRoute();
const trail = useTrailStore();
const ui = useUiStore();

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

// These pages have no rail of their own, so the body's headings stand in as one. Computed from the body (not from
// the rendered DOM) so the list is in the pre-rendered HTML; only the active entry is client-side.
const headings = computed(() => (data.value ? docOutline(data.value.page.body) : []));
const hasToc = computed(() => headings.value.length > 0);
const headingIds = computed(() => headings.value.map((h) => h.id));

const prose = ref<InstanceType<typeof ProseBody> | null>(null);
const proseRoot = computed(() => prose.value?.root ?? null);
const { activeId } = useHeadingFollow(proseRoot, headingIds);

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
.page-layout {
	flex: 1 1 auto;
	display: grid;
	grid-template-columns: minmax(0, 1fr);
	align-content: start;
	min-height: 0;
}

.page-layout--toc {
	grid-template-columns: var(--rail-width) minmax(0, 1fr);
	align-content: stretch;
}

.page-layout__mobile-toc {
	grid-column: 1 / -1;
}

.page-layout .shell-content {
	min-width: 0;
}

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

@media (max-width: 1180px) {
	.page-layout--toc {
		grid-template-columns: minmax(0, 1fr);
		align-content: start;
	}

	.page-layout__rail {
		display: none;
	}
}
</style>
