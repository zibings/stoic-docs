<template>
	<div ref="root" class="prose" @click="onClick" v-html="html" />
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

import { renderDocBody } from "markdown/render";
import type { RenderContext } from "markdown/render";

const props = defineProps<{ body: string; context: RenderContext }>();

const root = ref<HTMLElement | null>(null);
const html = computed(() => renderDocBody(props.body, props.context));

// Copy buttons are plain markup from the renderer; one delegated handler serves them all.
async function onClick(event: MouseEvent): Promise<void> {
	const button = (event.target as HTMLElement).closest<HTMLButtonElement>("[data-copy]");

	if (!button) {
		return;
	}

	const code = root.value?.querySelector<HTMLElement>(`#${button.dataset.copy}`);

	if (!code) {
		return;
	}

	try {
		await navigator.clipboard.writeText(code.textContent ?? "");
		button.textContent = "Copied";
		window.setTimeout(() => (button.textContent = "Copy"), 1500);
	} catch {
		button.textContent = "Copy failed";
		window.setTimeout(() => (button.textContent = "Copy"), 1500);
	}
}

defineExpose({ root, html });
</script>

<style>
.prose {
	display: flex;
	flex-direction: column;
	gap: 14px;
	color: var(--color-ink2);
}

.prose > p,
.prose > ul,
.prose > ol {
	max-width: 640px;
}

.prose h2 {
	margin-top: 14px;
	font-size: var(--text-h2);
	font-weight: 600;
	color: var(--color-ink);
}

.prose h3 {
	font-size: var(--text-body);
	font-weight: 600;
	color: var(--color-ink);
}

.prose ul,
.prose ol {
	margin: 0;
	padding-left: 22px;
}

.prose code {
	font-size: var(--text-small);
}

.prose :not(pre) > code {
	padding: 1px 4px;
	border-radius: 3px;
	background: var(--color-chip);
}

.prose .sym-ref {
	font-family: var(--font-mono);
	font-size: var(--text-small);
	padding: 1px 4px;
	border-radius: 3px;
	background: var(--color-accent-tint);
	color: var(--color-accent);
}

.prose .sym-ref:hover {
	background: var(--color-accent);
	color: var(--color-surface);
	text-decoration: none;
}

.prose .sym-ref.removed {
	background: var(--color-chip);
}

.prose .sym-ref--unresolved {
	background: var(--color-chip);
	color: var(--color-ink3);
}

/* Full-row cards */
.doc-card {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.doc-card__label {
	font-family: var(--font-mono);
	font-size: var(--text-label);
	font-weight: 600;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: var(--color-muted);
}

.doc-card__body > :last-child {
	margin-bottom: 0;
}

.upgrade-note {
	display: flex;
	flex-direction: column;
	gap: 4px;
	font-size: var(--text-small);
}

.upgrade-note__label {
	font-family: var(--font-mono);
	font-size: var(--text-label);
	font-weight: 600;
	text-transform: uppercase;
	color: var(--color-warn-text);
}

.upgrade-note--collapsed {
	flex-direction: row;
	align-items: center;
	gap: 8px;
}

/* Code */
.code-block {
	padding: 14px 16px;
	border: 1px solid var(--color-rule);
	border-radius: var(--radius-card);
	background: var(--color-surface);
	overflow-x: auto;
}

.code-sample {
	padding: 0;
	overflow: hidden;
}

.code-sample__bar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	padding: 8px 14px;
	border-bottom: 1px solid var(--color-rule-soft);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-muted);
}

.code-sample__meta {
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.code-sample__copy {
	color: var(--color-accent);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	min-height: 36px;
	min-width: var(--touch-target);
	margin: -4px -8px -4px 0;
	padding: 0 8px;
	border-radius: var(--radius-chip);
	flex-shrink: 0;
}

/* Phones: keep the file name and tested mark, drop the "rendered for" context (the chip in the header says it) */
@media (max-width: 768px) {
	.code-sample__context {
		display: none;
	}

	.code-sample__copy {
		min-height: var(--touch-target);
		margin: -8px -8px -8px 0;
	}
}

.code-sample__copy:hover {
	background: var(--color-accent-tint);
}

.code-sample__pre {
	padding: 14px 16px;
	overflow-x: auto;
	color: var(--color-ink);
}

.code-sample--missing {
	padding: 10px 14px;
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-muted);
}
</style>
