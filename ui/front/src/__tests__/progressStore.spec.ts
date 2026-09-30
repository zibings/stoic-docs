import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, it } from "vitest";

import { PROGRESS_PREFIX, readProgress, useProgressStore } from "stores/progress";

describe("progress store", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		window.localStorage.clear();
	});

	it("marks steps, completes the lesson when all steps are done, and persists per version and course", () => {
		const progress = useProgressStore();

		progress.load("v4.2", "tessel-in-an-afternoon");
		progress.markStep("keep-data-fresh", 1, 3);
		progress.markStep("keep-data-fresh", 1, 3);
		progress.markStep("keep-data-fresh", 3, 3);

		expect(progress.doneSteps("keep-data-fresh")).toEqual([1, 3]);
		expect(progress.isLessonDone("keep-data-fresh")).toBe(false);

		progress.markStep("keep-data-fresh", 2, 3);

		expect(progress.isLessonDone("keep-data-fresh")).toBe(true);
		expect(readProgress("v4.2", "tessel-in-an-afternoon")["keep-data-fresh"].done).toBe(true);
		expect(readProgress("v4.1", "tessel-in-an-afternoon")).toEqual({});
		expect(window.localStorage.getItem(`${PROGRESS_PREFIX}v4.2:tessel-in-an-afternoon`)).not.toBeNull();
	});

	it("undoes a step and reopens the lesson", () => {
		const progress = useProgressStore();

		progress.load("v4.2", "c");
		progress.markStep("l", 1, 1);
		expect(progress.isLessonDone("l")).toBe(true);

		progress.unmarkStep("l", 1);
		expect(progress.doneSteps("l")).toEqual([]);
		expect(progress.isLessonDone("l")).toBe(false);
	});

	it("marks stepless lessons done and swaps state when another course loads", () => {
		const progress = useProgressStore();

		progress.load("v4.2", "a");
		progress.markLessonDone("intro");
		expect(progress.isLessonDone("intro")).toBe(true);

		progress.load("v4.2", "b");
		expect(progress.isLessonDone("intro")).toBe(false);

		progress.load("v4.2", "a");
		expect(progress.isLessonDone("intro")).toBe(true);
	});

	it("survives corrupt storage", () => {
		window.localStorage.setItem(`${PROGRESS_PREFIX}v4.2:a`, "not json");

		const progress = useProgressStore();

		progress.load("v4.2", "a");
		expect(progress.lessons).toEqual({});
	});
});
