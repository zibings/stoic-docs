<template>
	<div class="shell-content">
		<div>
			<div class="label">404</div>
			<h1 class="page-title">Not found</h1>
		</div>
		<p class="muted">
			Nothing lives at <span class="mono">{{ route.path }}</span>.
			<RouterLink :to="home">Go to the latest version</RouterLink>
		</p>
	</div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";

import { usePageMeta } from "composables/usePageMeta";
import { useSiteStore } from "stores/site";

const route = useRoute();
const site = useSiteStore();
const home = computed(() => `/${site.latest ?? "latest"}`);

usePageMeta(computed(() => "Not found"), computed(() => null));
</script>
