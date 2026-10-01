<template>
	<aside class="rail toc-rail" aria-label="On this page">
		<section class="rail__section">
			<div class="label">On this page</div>
			<PageToc :headings="headings" :active-id="activeId" />
		</section>

		<button type="button" class="rail__browse" aria-label="Browse all (⌘B)" @click="emit('browse')">
			<TreeIcon />
			<span class="rail__browse-text">Browse all</span>
			<span class="kbd" aria-hidden="true">⌘B</span>
		</button>
	</aside>
</template>

<script setup lang="ts">
import TreeIcon from "components/icons/TreeIcon.vue";
import PageToc from "components/prose/PageToc.vue";
import type { OutlineHeading } from "markdown/render";

defineProps<{ headings: OutlineHeading[]; activeId: string | null }>();

const emit = defineEmits<{ (e: "browse"): void }>();
</script>
