<template>
	<div class="shell-content">
		<div>
			<div class="label">{{ mode }} · {{ version }}</div>
			<h1 class="page-title">{{ titles[mode] }}</h1>
		</div>

		<p v-if="error" class="muted">Could not load the page list: {{ error }}</p>
		<p v-else-if="!data" class="muted">Loading…</p>
		<p v-else-if="pages.length === 0" class="muted">Nothing here yet at {{ version }}.</p>

		<ul v-else class="page-list">
			<li v-for="page in pages" :key="String(page.route)">
				<RouterLink :to="String(page.route)">{{ page.title }}</RouterLink>
				<span v-if="page.minutes" class="muted">· {{ page.minutes }} min</span>
				<p v-if="page.summary" class="muted">{{ page.summary }}</p>
			</li>
		</ul>
	</div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";

import { docsApi } from "api/docs";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import type { Mode } from "types/docs";

const props = defineProps<{ version: string }>();
const route = useRoute();

const mode = computed<Mode>(() => route.meta.mode ?? "do");
const titles: Record<Mode, string> = { learn: "Courses", do: "Tasks", reference: "Reference", explain: "Concepts" };

usePageMeta(
	computed(() => `${titles[mode.value]} · ${props.version}`),
	computed(() => null),
);

// There is no per-mode listing endpoint; the build manifest already carries every page at a version.
const key = computed(() => `manifest:${props.version}`);
const { data, error } = useDocsData(key, () => docsApi.manifest(props.version));

const pages = computed(() =>
	(data.value?.searchRecords ?? [])
		.filter((r) => r.type === "page" && r.mode === mode.value)
		.map((r) => r as { title: string; summary: string; minutes: number | null; route: string }),
);
</script>

<style>
.page-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 14px;
	max-width: 720px;
}

.page-list p {
	font-size: var(--text-small);
}
</style>
