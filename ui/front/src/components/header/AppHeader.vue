<template>
	<header class="app-header">
		<div class="app-header__bar">
			<RouterLink :to="homeTarget" class="app-header__logo">{{ site.libraryName }}</RouterLink>
			<button v-if="activeMode" type="button" class="app-header__mode-label" :aria-label="`${modeLabel}: switch mode`" @click="ui.openBrowse(activeMode)">
				{{ modeLabel }}
				<ChevronDownIcon />
			</button>

			<ModeTabs class="app-header__tabs" :modes="site.modes" :active="activeMode" :version="tabVersion" />
			<SearchTrigger class="app-header__search" @open="ui.openSearch()" />
			<ContextBar ref="contextBar" class="app-header__context" />
			<ThemeToggle class="app-header__theme" />

			<div class="app-header__mobile-actions">
				<button type="button" class="icon-button" aria-label="Browse all" @click="ui.openBrowse()">
					<TreeIcon :size="20" />
				</button>
				<SearchTrigger compact @open="ui.openSearch()" />
				<ThemeToggle />
			</div>
		</div>
		<div class="app-header__mobile-context">
			<MobileContextChip ref="mobileChip" />
		</div>
	</header>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";

import ContextBar from "components/header/ContextBar.vue";
import ChevronDownIcon from "components/icons/ChevronDownIcon.vue";
import MobileContextChip from "components/header/MobileContextChip.vue";
import ModeTabs from "components/header/ModeTabs.vue";
import SearchTrigger from "components/header/SearchTrigger.vue";
import ThemeToggle from "components/header/ThemeToggle.vue";
import TreeIcon from "components/icons/TreeIcon.vue";
import { useCurrentVersion } from "composables/useCurrentVersion";
import { useSiteStore } from "stores/site";
import { useUiStore } from "stores/ui";
import type { Mode } from "types/docs";

const route = useRoute();
const site = useSiteStore();
const ui = useUiStore();
const { label } = useCurrentVersion();

const activeMode = computed<Mode | undefined>(() => route.meta.mode);
const tabVersion = computed(() => label.value ?? site.latest ?? "latest");
const homeTarget = computed(() => `/${tabVersion.value}`);

const modeLabels: Record<Mode, string> = { learn: "Learn", do: "Do", reference: "Reference", explain: "Explain" };
const modeLabel = computed(() => (activeMode.value ? modeLabels[activeMode.value] : ""));

// ⌘. from a palette: focus the version picker, or open the context popover where the bar is collapsed into the chip.
const contextBar = ref<InstanceType<typeof ContextBar> | null>(null);
const mobileChip = ref<InstanceType<typeof MobileContextChip> | null>(null);

watch(
	() => ui.contextFocusTick,
	async () => {
		await nextTick();

		if (window.matchMedia("(max-width: 1180px)").matches) {
			mobileChip.value?.open();
		} else {
			contextBar.value?.focusVersion();
		}
	},
);

// ⌘K / Ctrl K opens search, ⌘B / Ctrl B opens browse. The palettes themselves arrive in phase 6.
function onKeydown(event: KeyboardEvent): void {
	if (!(event.metaKey || event.ctrlKey) || event.altKey || event.shiftKey) {
		return;
	}

	const key = event.key.toLowerCase();

	if (key === "k") {
		event.preventDefault();
		ui.openSearch();
	} else if (key === "b") {
		event.preventDefault();
		ui.openBrowse();
	}
}

onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onKeydown));
</script>

<style>
.app-header {
	position: sticky;
	top: 0;
	z-index: 20;
	background: var(--color-ground);
	border-bottom: 1px solid var(--color-rule);
}

.app-header__bar {
	height: var(--header-height);
	padding: 0 24px;
	display: flex;
	align-items: center;
	gap: 28px;
}

.app-header__logo {
	font-family: var(--font-mono);
	font-weight: 600;
	font-size: 17px;
	letter-spacing: -0.02em;
	color: var(--color-ink);
	white-space: nowrap;
}

.app-header__logo:hover {
	text-decoration: none;
	color: var(--color-ink);
}

/* On phones the mode tabs collapse into this label; tapping it opens Browse on the current mode's tab. */
.app-header__mode-label {
	display: none;
	align-items: center;
	gap: 4px;
	min-height: var(--touch-target);
	padding: 0 6px;
	margin-left: -6px;
	border-radius: var(--radius-control);
	font-size: var(--text-meta);
	color: var(--color-muted);
}

.app-header__mode-label:hover {
	color: var(--color-ink);
}

.app-header__search {
	flex: 1 1 auto;
	min-width: 0;
}

/* Narrow desktops: the pickers keep their labels, the "Your context" caption goes */
@media (max-width: 1400px) {
	.app-header__context .context-bar__label {
		display: none;
	}
}

.app-header__context {
	margin-left: auto;
}

.app-header__theme {
	margin-left: -12px;
}

.app-header__mobile-actions {
	display: none;
	margin-left: auto;
}

.app-header__mobile-context {
	display: none;
	padding: 8px 18px;
	border-top: 1px solid var(--color-rule);
}

/* Tablets: the full context bar and search box no longer fit beside the tabs, so the context moves into the chip
   row and search becomes an icon. The mode tabs stay. */
@media (max-width: 1180px) {
	.app-header__bar {
		padding: 0 8px 0 18px;
		gap: 16px;
	}

	.app-header__search,
	.app-header__context,
	.app-header__theme {
		display: none;
	}

	.app-header__mobile-actions,
	.app-header__mobile-context {
		display: flex;
	}
}

/* Phones: tabs collapse into the mode label. */
@media (max-width: 768px) {
	.app-header__bar {
		height: var(--header-height-mobile);
		gap: 10px;
	}

	.app-header__tabs {
		display: none;
	}

	.app-header__mode-label {
		display: flex;
	}
}
</style>
