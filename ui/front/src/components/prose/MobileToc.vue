<template>
	<details class="mobile-toc" :open="open" @toggle="open = ($event.target as HTMLDetailsElement).open">
		<summary class="mobile-toc__summary">
			<span class="label">On this page</span>
			<span class="mobile-toc__current">{{ current }}</span>
			<ChevronDownIcon class="mobile-toc__chevron" />
		</summary>
		<div class="mobile-toc__body">
			<PageToc :headings="headings" :active-id="activeId" @navigate="open = false" />
		</div>
	</details>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

import ChevronDownIcon from "components/icons/ChevronDownIcon.vue";
import PageToc from "components/prose/PageToc.vue";
import type { OutlineHeading } from "markdown/render";

const props = defineProps<{ headings: OutlineHeading[]; activeId: string | null }>();

const open = ref(false);
const current = computed(() => props.headings.find((h) => h.id === props.activeId)?.text ?? `${props.headings.length} sections`);
</script>

<style>
/* Shown by the owning layout at 1180px and below, where the rail column is gone. */
.mobile-toc {
	display: none;
	border-bottom: 1px solid var(--color-rule);
}

.mobile-toc__summary {
	display: flex;
	gap: 12px;
	align-items: center;
	min-height: var(--touch-target);
	padding: 10px 18px;
	cursor: pointer;
	font-size: var(--text-small);
	list-style: none;
}

.mobile-toc__summary::-webkit-details-marker {
	display: none;
}

.mobile-toc__current {
	flex: 1 1 auto;
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	color: var(--color-ink3);
}

.mobile-toc__chevron {
	color: var(--color-muted);
	transition: transform 120ms ease;
}

.mobile-toc[open] .mobile-toc__chevron {
	transform: rotate(180deg);
}

.mobile-toc__body {
	padding: 0 18px 12px 26px;
}

@media (max-width: 1180px) {
	.mobile-toc {
		display: block;
	}
}
</style>
