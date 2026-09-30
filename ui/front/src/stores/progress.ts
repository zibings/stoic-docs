import { defineStore } from "pinia";

// Learn progress: which steps and lessons the reader has completed, per version and course. Client-only
// (localStorage), no accounts, as the design promises.
export const PROGRESS_PREFIX = "docs.progress:";

export interface LessonProgress {
	doneSteps: number[];
	done: boolean;
}

function storageKey(version: string, course: string): string {
	return `${PROGRESS_PREFIX}${version}:${course}`;
}

/** Reads a course's stored progress without touching the store; used by the Learn index for several courses. */
export function readProgress(version: string, course: string): Record<string, LessonProgress> {
	return read(storageKey(version, course));
}

function read(key: string): Record<string, LessonProgress> {
	if (typeof window === "undefined") {
		return {};
	}

	try {
		const raw = window.localStorage.getItem(key);
		const parsed = raw ? (JSON.parse(raw) as unknown) : {};

		return typeof parsed === "object" && parsed !== null ? (parsed as Record<string, LessonProgress>) : {};
	} catch {
		return {};
	}
}

function write(key: string, value: Record<string, LessonProgress>): void {
	if (typeof window === "undefined") {
		return;
	}

	try {
		window.localStorage.setItem(key, JSON.stringify(value));
	} catch {
		// Storage unavailable; progress lasts for this visit only.
	}
}

export const useProgressStore = defineStore("progress", {
	state: () => ({
		key: null as string | null,
		lessons: {} as Record<string, LessonProgress>,
	}),

	getters: {
		isLessonDone: (state) => (slug: string): boolean => state.lessons[slug]?.done ?? false,
		doneSteps: (state) => (slug: string): number[] => state.lessons[slug]?.doneSteps ?? [],
	},

	actions: {
		load(version: string, course: string) {
			const key = storageKey(version, course);

			if (this.key === key) {
				return;
			}

			this.key = key;
			this.lessons = read(key);
		},

		/** Marks a step done; when every step of the lesson is done the lesson is too. */
		markStep(slug: string, ordinal: number, totalSteps: number) {
			const current = this.lessons[slug] ?? { doneSteps: [], done: false };
			const doneSteps = current.doneSteps.includes(ordinal) ? current.doneSteps : [...current.doneSteps, ordinal].sort((a, b) => a - b);

			this.lessons = { ...this.lessons, [slug]: { doneSteps, done: current.done || doneSteps.length >= totalSteps } };
			this.persist();
		},

		unmarkStep(slug: string, ordinal: number) {
			const current = this.lessons[slug];

			if (!current) {
				return;
			}

			this.lessons = { ...this.lessons, [slug]: { doneSteps: current.doneSteps.filter((o) => o !== ordinal), done: false } };
			this.persist();
		},

		/** For lessons without steps: reading it is completing it. */
		markLessonDone(slug: string) {
			const current = this.lessons[slug] ?? { doneSteps: [], done: false };

			this.lessons = { ...this.lessons, [slug]: { ...current, done: true } };
			this.persist();
		},

		reset() {
			this.lessons = {};
			this.persist();
		},

		persist() {
			if (this.key) {
				write(this.key, this.lessons);
			}
		},
	},
});
