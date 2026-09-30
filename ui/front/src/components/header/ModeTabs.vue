<template>
	<nav class="mode-tabs" aria-label="Modes">
		<RouterLink
			v-for="mode in modes"
			:key="mode"
			:to="`/${version}/${mode}`"
			class="mode-tab"
			:class="{ 'is-active': mode === active }"
			:aria-current="mode === active ? 'page' : undefined"
		>
			{{ labels[mode] }}
		</RouterLink>
	</nav>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";

import type { Mode } from "types/docs";

defineProps<{
	modes: Mode[];
	active: Mode | undefined;
	/** Version label the tabs link into; `latest` when no version is in the URL yet. */
	version: string;
}>();

const labels: Record<Mode, string> = {
	learn: "Learn",
	do: "Do",
	reference: "Reference",
	explain: "Explain",
};
</script>

<style>
.mode-tabs {
	display: flex;
	gap: 4px;
}

.mode-tab {
	padding: 8px 12px;
	border-radius: var(--radius-control);
	color: var(--color-muted);
	font-size: var(--text-small);
	white-space: nowrap;
}

.mode-tab:hover {
	color: var(--color-ink);
	text-decoration: none;
	background: var(--color-surface-sunk);
}

.mode-tab.is-active {
	color: var(--color-ink);
	font-weight: 600;
	background: var(--color-chip);
}
</style>
