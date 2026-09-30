import { describe, expect, it } from "vitest";

import { allStepsDone, currentStep, logLines, neighbors, remainingMinutes, workspaceTabs } from "learn/steps";
import type { CodeSample, CourseLesson, CourseStep } from "types/docs";

const steps: CourseStep[] = [
	{ ordinal: 1, prompt: "a", hint: null, answer: null, expectedOutput: null },
	{ ordinal: 2, prompt: "b", hint: null, answer: null, expectedOutput: null },
	{ ordinal: 3, prompt: "c", hint: null, answer: null, expectedOutput: null },
];

const lessons: CourseLesson[] = [
	{ ordinal: 1, title: "One", slug: "one", minutes: 8, current: false, route: "/v4.2/learn/c/one" },
	{ ordinal: 2, title: "Two", slug: "two", minutes: 10, current: true, route: "/v4.2/learn/c/two" },
	{ ordinal: 3, title: "Three", slug: "three", minutes: null, current: false, route: "/v4.2/learn/c/three" },
];

function sample(title: string, language: string): CodeSample {
	return { key: title, title, language, variant: null, code: "x", isTested: false, lastTestPassLabel: null };
}

describe("learn step logic", () => {
	it("picks the first unfinished step and the last one when everything is done", () => {
		expect(currentStep(steps, [])?.ordinal).toBe(1);
		expect(currentStep(steps, [1])?.ordinal).toBe(2);
		expect(currentStep(steps, [1, 3])?.ordinal).toBe(2);
		expect(currentStep(steps, [1, 2, 3])?.ordinal).toBe(3);
		expect(currentStep([], [])).toBeNull();
		expect(allStepsDone(steps, [1, 2, 3])).toBe(true);
		expect(allStepsDone(steps, [1, 2])).toBe(false);
	});

	it("computes neighbours and remaining minutes", () => {
		expect(neighbors(lessons, 2)).toEqual({ previous: lessons[0], next: lessons[2] });
		expect(neighbors(lessons, 1).previous).toBeNull();
		expect(neighbors(lessons, 3).next).toBeNull();
		expect(remainingMinutes(lessons, (slug) => slug === "one")).toBe(10);
	});

	it("builds workspace tabs from titled samples in the reader's language", () => {
		const samples = [sample("profile.ts", "ts"), sample("profile.js", "js"), sample("api.ts", "ts"), { ...sample("untitled", "ts"), title: null }];

		expect(workspaceTabs(samples, "ts").map((s) => s.title)).toEqual(["profile.ts", "api.ts"]);
		expect(workspaceTabs(samples, "js").map((s) => s.title)).toEqual(["profile.js"]);
		expect(workspaceTabs(samples, "php").map((s) => s.title)).toEqual(["profile.ts", "profile.js", "api.ts"]);
		expect(workspaceTabs([], "ts")).toEqual([]);
	});

	it("turns expected output into network, cache, and output rows", () => {
		const lines = logLines("GET /me 200 · 41 ms\nCACHE hit profile\nvalue printed");

		expect(lines.map((l) => l.kind)).toEqual(["NETWORK", "CACHE", "OUTPUT"]);
		expect(logLines("int(100)\nSELECT * FROM t LIMIT 500\n200 OK\nHTTP/1.1 404 Not Found").map((l) => l.kind)).toEqual(["OUTPUT", "OUTPUT", "NETWORK", "NETWORK"]);
		expect(lines[1].text).toBe("hit profile");
		expect(lines[0].time).toBe("0.0s");
		expect(logLines(null)).toEqual([]);
	});
});
