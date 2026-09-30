<template>
	<div class="shell-content">
		<div>
			<div class="label">learn · {{ version }}</div>
			<h1 class="page-title">Courses</h1>
		</div>

		<p v-if="error" class="muted">Could not load the course list: {{ error }}</p>
		<p v-else-if="!data" class="muted">Loading…</p>
		<p v-else-if="courses.length === 0" class="muted">No courses yet at {{ version }}.</p>

		<ul v-else class="course-list">
			<li v-for="course in courses" :key="course.slug" class="course-card card">
				<div class="course-card__head">
					<div>
						<h2 class="course-card__title">{{ course.title }}</h2>
						<p class="course-card__summary muted">{{ course.summary }}</p>
					</div>
					<ClientOnly>
						<div class="course-card__progress mono">{{ doneCount(course) }} of {{ course.lessonCount }} done · about {{ remaining(course) }} min left</div>
					</ClientOnly>
				</div>
				<ol class="course-card__lessons">
					<li v-for="lesson in course.lessons" :key="lesson.slug" class="course-card__lesson" :class="{ 'is-done': isDone(course, lesson.slug) }">
						<span class="course-card__ordinal mono">{{ lesson.ordinal }}</span>
						<RouterLink :to="lesson.route">{{ lesson.title }}</RouterLink>
						<span v-if="lesson.minutes" class="muted course-card__minutes">{{ lesson.minutes }} min</span>
					</li>
				</ol>
				<RouterLink :to="startRoute(course)" class="course-card__start">{{ startLabel(course) }}</RouterLink>
			</li>
		</ul>
		<p v-if="courses.length > 0" class="muted course-note">Progress is kept in this browser. Nothing to sign up for.</p>
	</div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, watch } from "vue";
import { RouterLink } from "vue-router";

import { docsApi } from "api/docs";
import ClientOnly from "components/ClientOnly.vue";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import { readProgress, type LessonProgress } from "stores/progress";
import type { CourseListEntry } from "types/docs";

const props = defineProps<{ version: string }>();

usePageMeta(
	computed(() => `Courses · ${props.version}`),
	computed(() => null),
);

const key = computed(() => `manifest:${props.version}`);
const { data, error } = useDocsData(key, () => docsApi.manifest(props.version));
const courses = computed(() => data.value?.courses ?? []);

// Progress for every course on the page, read straight from storage (client only).
const progress = reactive<Record<string, Record<string, LessonProgress>>>({});

function refresh(): void {
	for (const course of courses.value) {
		progress[course.slug] = readProgress(props.version, course.slug);
	}
}

onMounted(refresh);
watch(courses, refresh);

function isDone(course: CourseListEntry, slug: string): boolean {
	return progress[course.slug]?.[slug]?.done ?? false;
}

function doneCount(course: CourseListEntry): number {
	return course.lessons.filter((l) => isDone(course, l.slug)).length;
}

function remaining(course: CourseListEntry): number {
	return course.lessons.filter((l) => !isDone(course, l.slug)).reduce((sum, l) => sum + (l.minutes ?? 0), 0);
}

function nextLesson(course: CourseListEntry) {
	return course.lessons.find((l) => !isDone(course, l.slug)) ?? course.lessons[0];
}

function startRoute(course: CourseListEntry): string {
	return nextLesson(course)?.route ?? `/${props.version}/learn`;
}

function startLabel(course: CourseListEntry): string {
	const done = doneCount(course);

	if (done === 0) {
		return "Start the course →";
	}

	if (done >= course.lessonCount) {
		return "Start over from lesson 1 →";
	}

	return `Continue with lesson ${nextLesson(course)?.ordinal ?? 1} →`;
}
</script>

<style>
.course-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 18px;
	max-width: 720px;
}

.course-card {
	display: flex;
	flex-direction: column;
	gap: 14px;
}

.course-card__head {
	display: flex;
	justify-content: space-between;
	gap: 16px;
	flex-wrap: wrap;
}

.course-card__title {
	font-family: var(--font-serif);
	font-weight: 500;
	font-size: 24px;
	line-height: 1.2;
}

.course-card__summary {
	margin-top: 4px;
	font-size: var(--text-small);
}

.course-card__progress {
	font-size: var(--text-meta);
	color: var(--color-muted);
	white-space: nowrap;
}

.course-card__lessons {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 6px;
	font-size: var(--text-small);
}

.course-card__lesson {
	display: flex;
	gap: 10px;
	align-items: baseline;
	min-height: 32px;
}

@media (max-width: 768px) {
	.course-card__lesson {
		min-height: var(--touch-target);
		align-items: center;
	}
}

.course-card__lesson.is-done .course-card__ordinal {
	background: var(--color-accent);
	border-color: var(--color-accent);
	color: var(--color-surface);
}

.course-card__ordinal {
	width: 18px;
	height: 18px;
	border-radius: 9px;
	border: 1px solid var(--color-faint);
	color: var(--color-muted);
	font-size: 10px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.course-card__minutes {
	font-size: var(--text-meta);
}

.course-card__start {
	align-self: flex-start;
	font-weight: 500;
	font-size: var(--text-small);
}

.course-note {
	font-size: var(--text-meta);
}

.mono {
	font-family: var(--font-mono);
}
</style>
