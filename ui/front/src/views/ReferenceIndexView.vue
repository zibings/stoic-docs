<template>
	<div class="shell-content">
		<div>
			<div class="label">Reference · {{ version }}</div>
			<h1 class="page-title">Modules</h1>
		</div>

		<p v-if="loading && !data" class="muted">Loading…</p>
		<p v-else-if="error" class="muted">Could not load the module list: {{ error }}</p>

		<section v-for="module in data?.modules ?? []" :key="module.path" class="module-block">
			<h2 class="module-block__title">
				<span class="mono">{{ module.path }}</span>
				<span class="muted">{{ module.count }}</span>
			</h2>
			<p v-if="module.summary" class="muted">{{ module.summary }}</p>
			<ul class="symbol-list">
				<li v-for="node in module.symbols" :key="node.ref">
					<RouterLink :to="node.route" class="mono" :class="{ removed: node.state === 'removed' }">{{ node.name }}</RouterLink>
					<span class="pill">{{ node.kind }}</span>
					<span v-if="node.removedLabel" class="pill pill--warn">removed {{ node.removedLabel }}</span>
					<span v-else-if="node.sinceLabel === version" class="pill pill--accent">new in {{ node.sinceLabel }}</span>
				</li>
			</ul>
		</section>
	</div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink } from "vue-router";

import { docsApi } from "api/docs";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";

const props = defineProps<{ version: string }>();

const key = computed(() => `tree:${props.version}`);
const { data, loading, error } = useDocsData(key, () => docsApi.tree(props.version));

usePageMeta(
	computed(() => `Reference · ${props.version}`),
	computed(() => (data.value ? `${data.value.modules.length} modules documented at ${props.version}` : null)),
);
</script>

<style>
.page-title {
	font-family: var(--font-serif);
	font-weight: 500;
	font-size: var(--text-page-title);
	line-height: 1.2;
}

.muted {
	color: var(--color-muted);
}

.mono {
	font-family: var(--font-mono);
}

.module-block {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.module-block__title {
	display: flex;
	align-items: baseline;
	gap: 10px;
	font-size: var(--text-h2);
	font-weight: 600;
}

.symbol-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.symbol-list li {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: var(--text-meta);
}
</style>
