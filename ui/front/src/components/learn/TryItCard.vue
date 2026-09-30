<template>
	<section class="card card--accent tryit" :aria-label="`Try it, step ${step.ordinal} of ${total}`">
		<div class="tryit__label">Try it · step {{ step.ordinal }} of {{ total }}<span v-if="done" class="tryit__done-tag">done</span></div>
		<p class="tryit__prompt" v-html="renderInline(step.prompt)" />
		<div v-if="hintShown && step.hint" class="tryit__reveal">
			<span class="label">Hint</span>
			<p v-html="renderInline(step.hint)" />
		</div>
		<div v-if="answerShown && step.answer" class="tryit__reveal">
			<span class="label">Answer</span>
			<pre class="tryit__answer"><code class="hljs" v-html="highlight(step.answer, language)" /></pre>
		</div>
		<div class="tryit__actions">
			<button v-if="step.hint && !hintShown" type="button" class="tryit__btn tryit__btn--outline" @click="hintShown = true">Show a hint</button>
			<button v-if="step.answer && !answerShown" type="button" class="tryit__btn" @click="answerShown = true">Show the answer</button>
			<button v-if="!done" type="button" class="tryit__btn tryit__btn--primary" @click="emit('done')">Mark step done</button>
			<button v-else type="button" class="tryit__btn" @click="emit('undo')">Undo</button>
		</div>
	</section>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

import { highlight } from "markdown/highlight";
import { renderInline } from "markdown/render";
import type { CourseStep } from "types/docs";

const props = defineProps<{ step: CourseStep; total: number; done: boolean; language: string | null }>();
const emit = defineEmits<{ (e: "done"): void; (e: "undo"): void }>();

const hintShown = ref(false);
const answerShown = ref(false);

// Reveals reset when the card moves to another step.
watch(
	() => props.step.ordinal,
	() => {
		hintShown.value = false;
		answerShown.value = false;
	},
);
</script>

<style>
.tryit {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.tryit__label {
	display: flex;
	align-items: center;
	gap: 10px;
	font-family: var(--font-mono);
	font-size: var(--text-label);
	font-weight: 600;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: var(--color-accent-strong);
}

.tryit__done-tag {
	padding: 1px 6px;
	border-radius: 3px;
	background: var(--color-accent);
	color: var(--color-surface);
	font-size: 10px;
}

.tryit__prompt {
	font-size: var(--text-body);
	line-height: 1.55;
	color: var(--color-ink);
}

.tryit__reveal {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 10px 12px;
	border-radius: var(--radius-control);
	background: var(--color-surface);
	font-size: var(--text-small);
	color: var(--color-ink2);
}

.tryit__answer {
	font-size: var(--text-meta);
	line-height: 1.6;
	overflow-x: auto;
}

.tryit__actions {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
	font-size: var(--text-small);
}

.tryit__btn {
	min-height: 36px;
	padding: 0 14px;
	border-radius: var(--radius-control);
	border: 1px solid transparent;
	background: transparent;
	color: var(--color-muted);
}

.tryit__btn:hover {
	color: var(--color-ink);
}

.tryit__btn--outline {
	border-color: var(--color-accent-card-border);
	background: var(--color-surface);
	color: var(--color-accent-strong);
}

.tryit__btn--primary {
	background: var(--color-accent);
	color: var(--color-surface);
	font-weight: 500;
}

.tryit__btn--primary:hover {
	background: var(--color-accent-strong);
	color: var(--color-surface);
}
</style>
