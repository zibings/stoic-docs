<template>
	<div class="shell">
		<a class="skip-link" href="#main">Skip to content</a>
		<AppHeader />
		<main id="main" class="shell__main">
			<RouterView />
		</main>
		<ClientOnly>
			<SearchPalette />
			<BrowsePalette />
		</ClientOnly>
	</div>
</template>

<script setup lang="ts">
import { RouterView } from "vue-router";

import ClientOnly from "components/ClientOnly.vue";
import AppHeader from "components/header/AppHeader.vue";
import BrowsePalette from "components/palette/BrowsePalette.vue";
import SearchPalette from "components/palette/SearchPalette.vue";
</script>

<style>
.shell {
	min-height: 100vh;
	min-height: 100dvh;
	display: flex;
	flex-direction: column;
}

.shell__main {
	flex: 1 1 auto;
	display: flex;
	flex-direction: column;
	min-height: 0;
}

/* Views that need the page's content padding without a rail or pane use this. */
.shell-content {
	padding: 36px var(--page-pad-x);
	display: flex;
	flex-direction: column;
	gap: 28px;
	max-width: 1100px;
}

@media (max-width: 768px) {
	.shell-content {
		padding: 20px 18px;
		gap: 18px;
	}
}
</style>
