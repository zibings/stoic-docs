<template>
	<button v-if="compact" type="button" class="icon-button" aria-label="Search" @click="emit('open')">
		<SearchIcon :size="20" />
	</button>
	<button v-else type="button" class="search-trigger" aria-label="Search (⌘K)" @click="emit('open')">
		<SearchIcon />
		<span class="search-trigger__text">Search symbols, tasks, errors…</span>
		<span class="kbd" aria-hidden="true">{{ shortcut }}</span>
	</button>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";

import SearchIcon from "components/icons/SearchIcon.vue";

defineProps<{ compact?: boolean }>();

const emit = defineEmits<{ (e: "open"): void }>();

// Rendered as ⌘K on the server and swapped for Ctrl K on non-Apple clients after mount, so pre-rendered markup
// stays deterministic.
const shortcut = ref("⌘K");

onMounted(() => {
	if (!/Mac|iPhone|iPad/.test(navigator.platform ?? "")) {
		shortcut.value = "Ctrl K";
	}
});
</script>

<style>
.search-trigger {
	flex: 1 1 auto;
	max-width: 460px;
	height: 40px;
	padding: 0 12px;
	display: flex;
	align-items: center;
	gap: 10px;
	background: var(--color-surface);
	border: 1px solid var(--color-control-border);
	border-radius: var(--radius-card);
	color: var(--color-muted);
	font-size: var(--text-small);
	text-align: left;
}

.search-trigger:hover {
	border-color: var(--color-faint);
}

.search-trigger .kbd {
	white-space: nowrap;
	flex-shrink: 0;
}

.search-trigger__text {
	flex: 1 1 auto;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
</style>
