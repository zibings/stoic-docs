<template>
	<aside class="spine" aria-label="Course">
		<div class="spine__head">
			<div class="label">Course</div>
			<div class="spine__title">{{ course.title }}</div>
			<div class="spine__meta">{{ doneCount }} of {{ course.lessonCount }} done · about {{ remaining }} min left</div>
		</div>
		<div class="spine__bar" role="progressbar" :aria-valuenow="doneCount" :aria-valuemin="0" :aria-valuemax="course.lessonCount" :aria-label="`${doneCount} of ${course.lessonCount} lessons done`">
			<div class="spine__bar-fill" :style="{ width: `${course.lessonCount ? Math.round((doneCount / course.lessonCount) * 100) : 0}%` }" />
		</div>
		<nav class="spine__lessons" aria-label="Lessons">
			<RouterLink v-for="lesson in course.lessons" :key="lesson.slug" :to="lesson.route" class="spine__lesson" :class="{ 'is-current': lesson.current, 'is-done': isDone(lesson.slug) }" :aria-current="lesson.current ? 'page' : undefined">
				<span class="spine__marker" aria-hidden="true">
					<svg v-if="isDone(lesson.slug) && !lesson.current" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10" /></svg>
					<span v-else class="spine__number" :class="{ 'is-current': lesson.current }">{{ lesson.ordinal }}</span>
				</span>
				<span class="spine__lesson-text">
					<span class="spine__lesson-title">{{ lesson.title }}</span>
					<span v-if="lesson.minutes" class="spine__lesson-minutes">{{ lesson.minutes }} min</span>
				</span>
			</RouterLink>
		</nav>
		<p class="spine__note">Progress is kept in this browser. Nothing to sign up for.</p>
	</aside>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink } from "vue-router";

import { doneLessonCount, remainingMinutes } from "learn/steps";
import type { CourseSpine } from "types/docs";

const props = defineProps<{ course: CourseSpine; isDone: (slug: string) => boolean }>();

const doneCount = computed(() => doneLessonCount(props.course.lessons, props.isDone));
const remaining = computed(() => remainingMinutes(props.course.lessons, props.isDone));
</script>

<style>
.spine {
	display: flex;
	flex-direction: column;
	gap: 18px;
	padding: 28px 22px;
	border-right: 1px solid var(--color-rule);
	position: sticky;
	top: var(--header-height);
	height: calc(100vh - var(--header-height));
	overflow-y: auto;
}

.spine__head {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.spine__title {
	font-family: var(--font-serif);
	font-size: 21px;
	line-height: 1.25;
}

.spine__meta {
	font-size: var(--text-meta);
	color: var(--color-muted);
}

.spine__bar {
	height: 4px;
	border-radius: 2px;
	background: var(--color-rule);
	overflow: hidden;
}

.spine__bar-fill {
	height: 100%;
	background: var(--color-accent);
	border-radius: 2px;
	transition: width 160ms ease;
}

.spine__lessons {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.spine__lesson {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	min-height: var(--touch-target);
	padding: 8px 10px;
	margin: 0 -10px;
	border-radius: var(--radius-control);
	color: var(--color-ink3);
	text-decoration: none;
}

.spine__lesson:hover {
	background: var(--color-surface-sunk);
	text-decoration: none;
}

.spine__lesson.is-current {
	background: var(--color-chip);
	color: var(--color-ink);
}

.spine__marker {
	width: 18px;
	padding-top: 3px;
	display: flex;
	justify-content: center;
	color: var(--color-accent);
	flex-shrink: 0;
}

.spine__number {
	width: 16px;
	height: 16px;
	border-radius: 9px;
	border: 1px solid var(--color-faint);
	color: var(--color-muted);
	font-family: var(--font-mono);
	font-size: 10px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.spine__number.is-current {
	width: 18px;
	height: 18px;
	border: 0;
	background: var(--color-ink);
	color: var(--color-surface);
	font-size: 11px;
}

.spine__lesson-text {
	display: flex;
	flex-direction: column;
}

.spine__lesson-title {
	font-size: var(--text-small);
	line-height: 1.4;
}

.spine__lesson.is-current .spine__lesson-title {
	font-weight: 600;
}

.spine__lesson-minutes {
	font-size: var(--text-label);
	color: var(--color-muted);
}

.spine__note {
	margin-top: auto;
	font-size: var(--text-meta);
	color: var(--color-muted);
	line-height: 1.5;
}
</style>
