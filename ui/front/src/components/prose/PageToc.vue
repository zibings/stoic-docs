<template>
	<nav class="toc" aria-label="On this page">
		<ol class="toc__list">
			<li v-for="heading in headings" :key="heading.id" class="toc__item" :class="[`toc__item--h${heading.level}`, { 'is-active': heading.id === activeId }]">
				<a :href="`#${heading.id}`" class="toc__link" :aria-current="heading.id === activeId ? 'location' : undefined" @click="emit('navigate', heading.id)">{{ heading.text }}</a>
			</li>
		</ol>
	</nav>
</template>

<script setup lang="ts">
import type { OutlineHeading } from "markdown/render";

defineProps<{ headings: OutlineHeading[]; activeId: string | null }>();

const emit = defineEmits<{ (e: "navigate", id: string): void }>();
</script>

<style>
.toc__list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
}

.toc__link {
	display: block;
	padding: 6px 8px;
	margin: 0 -8px;
	border-left: 2px solid transparent;
	border-radius: 0 var(--radius-chip) var(--radius-chip) 0;
	font-size: var(--text-small);
	line-height: 1.4;
	color: var(--color-ink3);
}

.toc__item--h3 .toc__link {
	padding-left: 20px;
	font-size: var(--text-meta);
	color: var(--color-muted);
}

.toc__link:hover {
	background: var(--color-surface-sunk);
	color: var(--color-ink);
	text-decoration: none;
}

.toc__item.is-active > .toc__link {
	border-left-color: var(--color-accent);
	color: var(--color-ink);
	font-weight: 500;
}
</style>
