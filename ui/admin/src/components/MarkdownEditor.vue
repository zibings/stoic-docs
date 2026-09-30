<template>
	<div class="md-editor">
		<div class="md-editor__pane">
			<div class="row md-editor__bar">
				<span class="label">Markdown</span>
				<span class="field__hint">Directives: <code>:::card[Label]</code>, <code>:::upgrade{from=v3.8 to=v4.0}</code>, <code>{sym:module/path#Name}</code>, <code>&lt;&lt;sample:key&gt;&gt;</code></span>
			</div>
			<Textarea :model-value="modelValue" :rows="rows" auto-resize fluid class="md-editor__text mono" spellcheck="false" @update:model-value="emit('update:modelValue', $event)" />
		</div>
		<div class="md-editor__pane">
			<div class="row md-editor__bar">
				<span class="label">Preview</span>
				<span class="field__hint">Rendered exactly as the site renders it, for {{ context.languageLabel }}.</span>
			</div>
			<div class="md-editor__preview prose" v-html="html" />
		</div>
	</div>
</template>

<script setup lang="ts">
import Textarea from "primevue/textarea";
import { computed } from "vue";

import { renderDocBody } from "markdown/render";
import type { RenderContext } from "markdown/render";

const props = withDefaults(defineProps<{ modelValue: string; context: RenderContext; rows?: number }>(), { rows: 18 });
const emit = defineEmits<{ (e: "update:modelValue", value: string): void }>();

const html = computed(() => renderDocBody(props.modelValue ?? "", props.context));
</script>

<style>
.md-editor {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 16px;
}

.md-editor__pane {
	display: flex;
	flex-direction: column;
	gap: 8px;
	min-width: 0;
}

.md-editor__bar {
	justify-content: space-between;
}

.md-editor__text {
	font-size: var(--text-meta) !important;
	line-height: 1.6;
	min-height: 320px;
}

.md-editor__preview {
	border: 1px solid var(--color-rule);
	border-radius: var(--radius-card);
	background: var(--color-surface);
	padding: 18px 20px;
	min-height: 320px;
	max-height: 70vh;
	overflow: auto;
	font-size: var(--text-body);
	line-height: 1.6;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.md-editor__preview h2 {
	font-size: 17px;
	font-weight: 600;
	margin-top: 8px;
}

.md-editor__preview .sym-ref {
	font-family: var(--font-mono);
	font-size: var(--text-small);
	padding: 1px 4px;
	border-radius: 3px;
	background: var(--color-accent-tint);
	color: var(--color-accent);
}

.md-editor__preview .sym-ref--unresolved {
	background: var(--color-warn-tint);
	color: var(--color-warn-text);
}

.md-editor__preview .card,
.md-editor__preview .doc-card {
	border: 1px solid var(--color-rule);
	border-radius: var(--radius-card);
	padding: 12px 14px;
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.md-editor__preview .upgrade-note {
	border-color: var(--color-warn-card-border);
	background: var(--color-warn-card-bg);
}

.md-editor__preview .doc-card__label,
.md-editor__preview .upgrade-note__label {
	font-family: var(--font-mono);
	font-size: var(--text-label);
	font-weight: 600;
	text-transform: uppercase;
	color: var(--color-muted);
}

.md-editor__preview .code-sample {
	border: 1px solid var(--color-rule);
	border-radius: var(--radius-card);
	overflow: hidden;
	padding: 0;
}

.md-editor__preview .code-sample__bar {
	display: flex;
	justify-content: space-between;
	padding: 6px 12px;
	border-bottom: 1px solid var(--color-rule-soft);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-muted);
}

.md-editor__preview .code-sample__copy {
	display: none;
}

.md-editor__preview pre {
	padding: 12px 14px;
	font-size: var(--text-meta);
	line-height: 1.6;
	overflow-x: auto;
}

.md-editor__preview .code-sample--missing {
	padding: 8px 12px;
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-warn-text);
}

@media (max-width: 1100px) {
	.md-editor {
		grid-template-columns: minmax(0, 1fr);
	}
}
</style>
