<template>
	<div class="pane workspace" :class="{ 'workspace--compact': compact }" aria-label="Workspace">
		<div v-if="tabs.length > 0" class="workspace__tabs" role="tablist" aria-label="Files">
			<button v-for="tab in tabs" :key="tab.title ?? tab.key" type="button" role="tab" class="workspace__tab" :class="{ 'is-active': tab === activeTab }" :aria-selected="tab === activeTab" @click="active = tab.title ?? tab.key">
				{{ tab.title }}
			</button>
			<button type="button" class="workspace__copy" @click="copy">{{ copied ? "Copied" : "Copy" }}</button>
		</div>
		<pre v-if="activeTab" class="workspace__code"><code class="hljs" v-html="highlight(activeTab.code.replace(/\n$/, ''), activeTab.language)" /></pre>
		<p v-else class="workspace__empty">This lesson has no workspace files yet. Steps still work from the prose.</p>

		<div v-if="step" class="workspace__actions">
			<button v-if="!stepDone" type="button" class="workspace__btn workspace__btn--primary" @click="emit('done')">Mark step {{ step.ordinal }} done</button>
			<button v-else type="button" class="workspace__btn" @click="emit('undo')">Undo step {{ step.ordinal }}</button>
		</div>

		<div v-if="lines.length > 0" class="workspace__log">
			<div class="workspace__log-label">{{ stepDone ? "Network log" : "Expected network log" }}</div>
			<div v-for="(line, i) in lines" :key="i" class="workspace__log-row">
				<span class="workspace__log-time">{{ line.time }}</span>
				<span class="workspace__log-kind" :class="`is-${line.kind.toLowerCase()}`">{{ line.kind }}</span>
				<span class="workspace__log-text">{{ line.text }}</span>
			</div>
		</div>

		<div v-if="step && stepDone" class="workspace__result">
			<div class="workspace__result-label">Step {{ step.ordinal }} passed</div>
			<div class="workspace__result-text">
				{{ step.expectedOutput ? "Output matched what the lesson expects." : "Marked done." }}
				<template v-if="nextStep"> Step {{ nextStep.ordinal }} is next in the lesson.</template>
				<template v-else-if="allDone"> That was the last step; the next lesson is unlocked.</template>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";

import { logLines } from "learn/steps";
import { highlight } from "markdown/highlight";
import type { CodeSample, CourseStep } from "types/docs";

const props = defineProps<{
	tabs: CodeSample[];
	step: CourseStep | null;
	nextStep: CourseStep | null;
	stepDone: boolean;
	allDone: boolean;
	compact?: boolean;
}>();

const emit = defineEmits<{ (e: "done"): void; (e: "undo"): void }>();

const active = ref<string | null>(null);
const activeTab = computed(() => props.tabs.find((t) => (t.title ?? t.key) === active.value) ?? props.tabs[0] ?? null);
const lines = computed(() => logLines(props.step?.expectedOutput));
const copied = ref(false);

watch(
	() => props.tabs,
	(tabs) => {
		if (!tabs.some((t) => (t.title ?? t.key) === active.value)) {
			active.value = tabs[0] ? (tabs[0].title ?? tabs[0].key) : null;
		}
	},
	{ immediate: true },
);

async function copy(): Promise<void> {
	if (!activeTab.value) {
		return;
	}

	try {
		await navigator.clipboard.writeText(activeTab.value.code);
		copied.value = true;
		window.setTimeout(() => (copied.value = false), 1500);
	} catch {
		// Clipboard unavailable; the code is selectable.
	}
}
</script>

<style>
.workspace {
	display: flex;
	flex-direction: column;
	background: var(--pane-bg);
	color: var(--pane-text);
	font-family: var(--font-mono);
	min-height: 100%;
}

.workspace__tabs {
	display: flex;
	align-items: center;
	gap: 4px;
	padding: 12px 20px 0;
	border-bottom: 1px solid var(--pane-rule);
}

.workspace__tab {
	height: 34px;
	padding: 0 12px;
	border-bottom: 2px solid transparent;
	color: var(--pane-muted);
	font-size: var(--text-meta);
}

.workspace__tab.is-active {
	border-bottom-color: var(--pane-link);
	color: #ffffff;
}

.workspace__copy {
	margin-left: auto;
	margin-bottom: 6px;
	height: 30px;
	padding: 0 10px;
	border: 1px solid var(--pane-control-border);
	border-radius: var(--radius-control);
	color: var(--pane-text-soft);
	font-size: var(--text-label);
}

.workspace__code {
	padding: 18px 20px;
	font-size: var(--text-code);
	line-height: 1.7;
	overflow-x: auto;
}

.workspace__empty {
	padding: 18px 20px;
	font-family: var(--font-sans);
	font-size: var(--text-small);
	color: var(--pane-muted);
}

.workspace__actions {
	display: flex;
	gap: 10px;
	padding: 0 20px 16px;
}

.workspace__btn {
	height: 40px;
	padding: 0 16px;
	border-radius: var(--radius-control);
	border: 1px solid var(--pane-control-border);
	color: var(--pane-text);
	font-size: var(--text-meta);
}

.workspace__btn--primary {
	border-color: var(--pane-text);
	background: var(--pane-text);
	color: var(--pane-bg);
	font-weight: 600;
}

.workspace__log {
	padding: 16px 20px;
	border-top: 1px solid var(--pane-rule);
	display: flex;
	flex-direction: column;
}

.workspace__log-label {
	font-size: var(--text-label);
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: var(--pane-muted);
	padding-bottom: 8px;
}

.workspace__log-row {
	display: flex;
	gap: 14px;
	padding: 7px 0;
	border-top: 1px solid var(--pane-rule);
	font-size: var(--text-meta);
}

.workspace__log-time {
	width: 48px;
	color: var(--pane-muted);
	flex-shrink: 0;
}

.workspace__log-kind {
	width: 72px;
	font-weight: 600;
	flex-shrink: 0;
}

.workspace__log-kind.is-network {
	color: var(--pane-warn);
}

.workspace__log-kind.is-cache {
	color: var(--pane-link);
}

.workspace__log-kind.is-output {
	color: var(--pane-text-soft);
}

.workspace__result {
	margin: auto 20px 20px;
	padding: 14px 16px;
	border-radius: var(--radius-card);
	background: var(--pane-success-bg);
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.workspace__result-label {
	font-size: var(--text-label);
	font-weight: 600;
	letter-spacing: 0.06em;
	text-transform: uppercase;
	color: var(--pane-success-label);
}

.workspace__result-text {
	font-family: var(--font-sans);
	font-size: var(--text-small);
	color: var(--pane-success-text);
}

.workspace--compact {
	min-height: 0;
}

.workspace--compact .workspace__result {
	margin-top: 16px;
}
</style>
