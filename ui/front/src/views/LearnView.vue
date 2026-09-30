<template>
	<div v-if="notFound" class="shell-content">
		<p class="muted">
			No lesson <span class="mono">{{ lesson }}</span> exists at {{ version }}.
			<RouterLink :to="`/${version}/learn`">Back to courses</RouterLink>
		</p>
	</div>
	<div v-else-if="error" class="shell-content"><p class="muted">Could not load this lesson: {{ error }}</p></div>
	<div v-else-if="!data" class="shell-content"><p class="muted">Loading…</p></div>

	<div v-else class="learn-layout" :class="{ 'learn-layout--no-course': !data.course }">
		<details v-if="data.course" class="learn-layout__mobile-spine">
			<summary class="learn-layout__mobile-summary">
				<span class="mono">Lesson {{ data.course.currentLesson }} of {{ data.course.lessonCount }}</span>
				<span class="muted">{{ data.course.title }}</span>
			</summary>
			<CourseSpine :course="data.course" :is-done="progress.isLessonDone" />
		</details>

		<CourseSpine v-if="data.course" class="learn-layout__spine" :course="data.course" :is-done="progress.isLessonDone" />

		<article class="learn-layout__main">
			<header class="learn-head">
				<div class="learn-head__meta mono">
					<template v-if="data.course">Lesson {{ data.course.currentLesson }} of {{ data.course.lessonCount }}</template>
					<template v-if="data.page.minutes"> · {{ data.page.minutes }} min</template>
				</div>
				<h1 class="learn-head__title">{{ data.page.title }}</h1>
			</header>

			<ProseBody :body="data.page.body" :context="renderContext" />

			<TryItCard v-if="step" :step="step" :total="steps.length" :done="stepDone" :language="context.effectiveLanguage" @done="markDone" @undo="undo" />
			<div v-if="steps.length > 1" class="learn-steps-nav">
				<button v-for="s in steps" :key="s.ordinal" type="button" class="learn-steps-nav__dot" :class="{ 'is-done': doneOrdinals.includes(s.ordinal), 'is-current': step?.ordinal === s.ordinal }" :aria-label="`Step ${s.ordinal}${doneOrdinals.includes(s.ordinal) ? ', done' : ''}`" @click="focusStep = s.ordinal"><span class="learn-steps-nav__circle">{{ s.ordinal }}</span></button>
			</div>

			<footer class="learn-foot">
				<RouterLink v-if="prevLesson" :to="prevLesson.route">← {{ prevLesson.title }}</RouterLink>
				<span v-else />
				<template v-if="nextLesson">
					<RouterLink v-if="canContinue" :to="nextLesson.route" class="learn-foot__next">{{ nextLesson.title }} →</RouterLink>
					<span v-else class="muted">Finish all {{ steps.length }} steps to continue: {{ nextLesson.title }} →</span>
				</template>
				<span v-else-if="canContinue" class="muted">That was the last lesson. 🎉</span>
			</footer>
		</article>

		<aside class="learn-layout__workspace" aria-label="Workspace">
			<Workspace :tabs="tabs" :step="step" :next-step="nextStep" :step-done="stepDone" :all-done="allDone" @done="markDone" @undo="undo" />
		</aside>

		<ClientOnly>
			<WorkspaceSheet class="learn-layout__sheet" :tabs="tabs" :step="step" :next-step="nextStep" :step-done="stepDone" :all-done="allDone" @done="markDone" @undo="undo" />
		</ClientOnly>
	</div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";

import { docsApi } from "api/docs";
import ClientOnly from "components/ClientOnly.vue";
import CourseSpine from "components/learn/CourseSpine.vue";
import TryItCard from "components/learn/TryItCard.vue";
import Workspace from "components/learn/Workspace.vue";
import WorkspaceSheet from "components/learn/WorkspaceSheet.vue";
import ProseBody from "components/prose/ProseBody.vue";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import { useProseContext } from "composables/useProseContext";
import { allStepsDone, currentStep, neighbors, workspaceTabs } from "learn/steps";
import { useContextStore } from "stores/context";
import { useProgressStore } from "stores/progress";
import { useTrailStore } from "stores/trail";

const props = defineProps<{ version: string; course: string; lesson: string }>();

const context = useContextStore();
const progress = useProgressStore();
const trail = useTrailStore();

const key = computed(() => `page:${props.version}:learn/${props.lesson}`);
const { data, error, notFound } = useDocsData(key, () => docsApi.page(props.version, "learn", props.lesson));

usePageMeta(
	computed(() => (data.value ? `${data.value.page.title} · ${data.value.course?.title ?? "Learn"} · ${props.version}` : null)),
	computed(() => data.value?.page.summary || null),
);

const versionLabel = computed(() => props.version);
const symbols = computed(() => data.value?.symbols ?? []);
const samples = computed(() => data.value?.samples ?? []);
const renderContext = useProseContext(versionLabel, symbols, samples);

// Progress is per version and course; load on the client (the server has no storage).
onMounted(() => progress.load(props.version, props.course));
watch(() => [props.version, props.course], ([version, course]) => progress.load(version, course));

const steps = computed(() => data.value?.course?.steps ?? []);
const doneOrdinals = computed(() => progress.doneSteps(props.lesson));
const focusStep = ref<number | null>(null);
const step = computed(() => (focusStep.value !== null ? steps.value.find((s) => s.ordinal === focusStep.value) ?? null : currentStep(steps.value, doneOrdinals.value)));
const stepDone = computed(() => (step.value ? doneOrdinals.value.includes(step.value.ordinal) : false));
const nextStep = computed(() => (step.value ? steps.value.find((s) => s.ordinal > step.value!.ordinal && !doneOrdinals.value.includes(s.ordinal)) ?? null : null));
const allDone = computed(() => steps.value.length > 0 && allStepsDone(steps.value, doneOrdinals.value));
const canContinue = computed(() => steps.value.length === 0 || allDone.value);

const tabs = computed(() => workspaceTabs(samples.value, context.effectiveLanguage));

const lessonNeighbors = computed(() => (data.value?.course ? neighbors(data.value.course.lessons, data.value.course.currentLesson) : { previous: null, next: null }));
const prevLesson = computed(() => lessonNeighbors.value.previous);
const nextLesson = computed(() => lessonNeighbors.value.next);

function markDone(): void {
	if (step.value) {
		progress.markStep(props.lesson, step.value.ordinal, steps.value.length);
		focusStep.value = null;
	}
}

function undo(): void {
	if (step.value) {
		progress.unmarkStep(props.lesson, step.value.ordinal);
	}
}

// A lesson without steps counts as done once read.
watch(
	data,
	(value) => {
		if (!value || typeof window === "undefined") {
			return;
		}

		progress.load(props.version, props.course);

		if ((value.course?.steps.length ?? 0) === 0) {
			progress.markLessonDone(props.lesson);
		}

		trail.record({ route: value.page.route, title: value.page.title, mode: "learn", versionLabel: value.version.label, versionSortKey: value.version.sortKey });
	},
	{ immediate: true },
);
</script>

<style>
.learn-layout {
	flex: 1 1 auto;
	display: grid;
	grid-template-columns: 260px minmax(0, 1fr) var(--pane-width);
	min-height: 0;
}

.learn-layout--no-course {
	grid-template-columns: minmax(0, 1fr) var(--pane-width);
}

.learn-layout__main {
	min-width: 0;
	padding: 36px 44px 48px;
	display: flex;
	flex-direction: column;
	gap: 22px;
}

.learn-layout__workspace {
	position: sticky;
	top: var(--header-height);
	height: calc(100vh - var(--header-height));
	overflow-y: auto;
}

.learn-layout__mobile-spine,
.learn-layout__sheet {
	display: none;
}

.learn-head {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.learn-head__meta {
	font-size: var(--text-meta);
	color: var(--color-muted);
}

.learn-head__title {
	font-family: var(--font-serif);
	font-weight: 500;
	font-size: 36px;
	line-height: 1.15;
	letter-spacing: -0.01em;
}

/* 44px hit areas around 28px visual dots */
.learn-steps-nav {
	display: flex;
	margin: -8px 0 -8px -8px;
}

.learn-steps-nav__dot {
	width: var(--touch-target);
	height: var(--touch-target);
	display: flex;
	align-items: center;
	justify-content: center;
}

.learn-steps-nav__circle {
	width: 28px;
	height: 28px;
	border-radius: 14px;
	border: 1px solid var(--color-control-border);
	background: var(--color-surface);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-muted);
	display: flex;
	align-items: center;
	justify-content: center;
}

.learn-steps-nav__dot.is-done .learn-steps-nav__circle {
	border-color: var(--color-accent);
	color: var(--color-accent-strong);
}

.learn-steps-nav__dot.is-current .learn-steps-nav__circle {
	background: var(--color-ink);
	border-color: var(--color-ink);
	color: var(--color-surface);
}

.learn-foot {
	margin-top: auto;
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 16px;
	padding-top: 18px;
	border-top: 1px solid var(--color-rule);
	font-size: var(--text-small);
}

.learn-foot__next {
	font-weight: 500;
}

.muted {
	color: var(--color-muted);
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 1180px) {
	.learn-layout,
	.learn-layout--no-course {
		grid-template-columns: minmax(0, 1fr);
		align-content: start;
	}

	.learn-layout__spine,
	.learn-layout__workspace {
		display: none;
	}

	.learn-layout__mobile-spine {
		display: block;
		border-bottom: 1px solid var(--color-rule);
	}

	.learn-layout__mobile-spine .spine {
		position: static;
		height: auto;
		border-right: 0;
	}

	.learn-layout__mobile-summary {
		display: flex;
		gap: 12px;
		align-items: baseline;
		min-height: var(--touch-target);
		padding: 10px 18px;
		cursor: pointer;
		font-size: var(--text-small);
		list-style: none;
	}

	.learn-layout__mobile-summary::-webkit-details-marker {
		display: none;
	}

	.learn-layout__main {
		padding-bottom: 170px;
	}

	.learn-layout__sheet {
		display: flex;
	}
}

@media (max-width: 768px) {
	.learn-layout__main {
		padding: 20px 18px 170px;
	}

	.learn-head__title {
		font-size: 28px;
	}
}
</style>
