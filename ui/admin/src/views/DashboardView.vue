<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">{{ catalog.libraryName }}</h1>
				<p class="page__subtitle">Latest version: <span class="mono">{{ latest?.label ?? "none marked" }}</span> · {{ catalog.versions.length }} versions</p>
			</div>
			<a v-if="catalog.siteUrl" :href="catalog.siteUrl" target="_blank" rel="noopener">View site ↗</a>
		</div>

		<div class="dash">
			<RouterLink v-for="tile in tiles" :key="tile.to" :to="tile.to" class="card dash__tile">
				<span class="dash__count">{{ tile.count ?? "…" }}</span>
				<span>{{ tile.label }}</span>
			</RouterLink>
		</div>

		<section class="card stack">
			<h2 class="resource-table__title">Getting started</h2>
			<ol class="dash__steps">
				<li>Create the <RouterLink to="/versions">versions</RouterLink> you document, oldest first, and mark the current one latest.</li>
				<li>Add <RouterLink to="/modules">modules and symbols</RouterLink>; each symbol gets a contract per version range.</li>
				<li>Write <RouterLink to="/pages">pages</RouterLink> in the four modes, link the symbols they discuss, and attach code samples per language.</li>
				<li>Record <RouterLink to="/changes">changes</RouterLink> per version so the upgrade view has something to show.</li>
				<li>Or <RouterLink to="/import-export">import a bundle</RouterLink> produced by a scanner or exported from another install.</li>
			</ol>
		</section>
	</div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import { adminApi } from "api/admin";
import { useCatalogStore } from "stores/catalog";

const catalog = useCatalogStore();
const latest = computed(() => catalog.versions.find((v) => v.isLatest) ?? null);

const counts = ref<Record<string, number | null>>({ pages: null, changes: null, courses: null });

const tiles = computed(() => [
	{ to: "/versions", label: "versions", count: catalog.versions.length },
	{ to: "/modules", label: "modules", count: catalog.modules.length },
	{ to: "/modules", label: "symbols", count: catalog.symbols.length },
	{ to: "/pages", label: "pages", count: counts.value.pages },
	{ to: "/changes", label: "changes", count: counts.value.changes },
	{ to: "/courses", label: "courses", count: counts.value.courses },
]);

onMounted(async () => {
	const [pages, changes, courses] = await Promise.all([adminApi.list("pages"), adminApi.list("changes"), adminApi.list("courses")]);

	counts.value = { pages: pages.length, changes: changes.length, courses: courses.length };
});
</script>

<style>
.dash {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
	gap: 12px;
}

.dash__tile {
	display: flex;
	flex-direction: column;
	gap: 4px;
	color: var(--color-ink);
}

.dash__tile:hover {
	text-decoration: none;
	border-color: var(--color-accent);
}

.dash__count {
	font-family: var(--font-mono);
	font-size: 26px;
	font-weight: 600;
}

.dash__steps {
	margin: 0;
	padding-left: 20px;
	display: flex;
	flex-direction: column;
	gap: 6px;
	color: var(--color-ink2);
}
</style>
