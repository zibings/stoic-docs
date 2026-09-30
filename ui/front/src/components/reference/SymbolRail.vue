<template>
	<aside class="rail" aria-label="Navigation rail">
		<section v-if="trail.length > 0 || current" class="rail__section">
			<div class="label">Your trail</div>
			<RouterLink v-for="entry in trail" :key="entry.route" :to="entry.route" class="rail__trail-link">{{ entry.title }}</RouterLink>
			<div v-if="current" class="rail__trail-current" aria-current="page">→ {{ current }}</div>
		</section>

		<section class="rail__section">
			<div class="label">{{ modulePath }}</div>
			<template v-for="node in siblings" :key="node.ref">
				<span v-if="node.ref === currentRef" class="rail__sibling rail__sibling--current" aria-current="page">{{ node.shortName }}</span>
				<RouterLink v-else :to="node.route" class="rail__sibling" :class="{ removed: node.state === 'removed' }">{{ node.shortName }}</RouterLink>
			</template>
		</section>

		<button type="button" class="rail__browse" aria-label="Browse all (⌘B)" @click="emit('browse')">
			<TreeIcon />
			<span class="rail__browse-text">Browse all</span>
			<span class="kbd" aria-hidden="true">⌘B</span>
		</button>
	</aside>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";

import TreeIcon from "components/icons/TreeIcon.vue";
import type { TrailEntry } from "stores/trail";
import type { SymbolNode } from "types/docs";

defineProps<{
	trail: TrailEntry[];
	current: string | null;
	modulePath: string;
	siblings: SymbolNode[];
	currentRef: string;
}>();

const emit = defineEmits<{ (e: "browse"): void }>();
</script>

<style>
.rail {
	display: flex;
	flex-direction: column;
	gap: 32px;
	padding: 28px 20px;
	border-right: 1px solid var(--color-rule);
	position: sticky;
	top: var(--header-height);
	height: calc(100vh - var(--header-height));
	overflow-y: auto;
}

.rail__section {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.rail__section .label {
	margin-bottom: 2px;
}

.rail__trail-link {
	font-size: var(--text-small);
	color: var(--color-muted);
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.rail__trail-current {
	font-family: var(--font-mono);
	font-size: var(--text-small);
	font-weight: 600;
	color: var(--color-ink);
}

.rail__sibling {
	font-family: var(--font-mono);
	font-size: var(--text-meta);
	color: var(--color-ink3);
	padding: 4px 8px;
	margin: 0 -8px;
	border-radius: var(--radius-chip);
}

.rail__sibling:hover {
	background: var(--color-surface-sunk);
	text-decoration: none;
}

.rail__sibling--current {
	background: var(--color-chip);
	color: var(--color-ink);
	font-weight: 500;
}

.rail__browse {
	margin-top: auto;
	height: var(--touch-target);
	padding: 0 12px;
	display: flex;
	align-items: center;
	gap: 10px;
	background: var(--color-surface);
	border: 1px solid var(--color-control-border);
	border-radius: var(--radius-card);
	color: var(--color-ink);
	font-size: var(--text-small);
	text-align: left;
}

.rail__browse:hover {
	border-color: var(--color-faint);
}

.rail__browse-text {
	flex: 1 1 auto;
}
</style>
