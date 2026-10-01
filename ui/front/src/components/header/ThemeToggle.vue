<template>
	<button type="button" class="icon-button theme-toggle" :aria-label="label" :title="label" @click="theme.toggle()">
		<SunIcon v-if="showing === 'dark'" :size="20" />
		<MoonIcon v-else :size="20" />
	</button>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import MoonIcon from "components/icons/MoonIcon.vue";
import SunIcon from "components/icons/SunIcon.vue";
import { useThemeStore } from "stores/theme";

const theme = useThemeStore();

// Pre-rendered markup always shows the light state; the real one appears after mount so hydration stays clean.
const mounted = ref(false);

onMounted(() => (mounted.value = true));

const showing = computed(() => (mounted.value ? theme.resolved : "light"));
const label = computed(() => (showing.value === "dark" ? "Switch to light theme" : "Switch to dark theme"));
</script>

<style>
.theme-toggle {
	color: var(--color-muted);
	flex-shrink: 0;
}

.theme-toggle:hover {
	color: var(--color-ink);
}
</style>
