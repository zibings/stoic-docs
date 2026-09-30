import type { CodeSample, CourseLesson, CourseStep } from "types/docs";

// Pure helpers behind Learn mode: step state, course progress, workspace tabs, and the synthetic network log.

export function stepsDone(steps: CourseStep[], doneOrdinals: number[]): number {
	return steps.filter((s) => doneOrdinals.includes(s.ordinal)).length;
}

export function allStepsDone(steps: CourseStep[], doneOrdinals: number[]): boolean {
	return steps.every((s) => doneOrdinals.includes(s.ordinal));
}

/** The step the reader is on: the first not yet done, or the last one when everything is done. */
export function currentStep(steps: CourseStep[], doneOrdinals: number[]): CourseStep | null {
	if (steps.length === 0) {
		return null;
	}

	return steps.find((s) => !doneOrdinals.includes(s.ordinal)) ?? steps[steps.length - 1];
}

export function doneLessonCount(lessons: CourseLesson[], isDone: (slug: string) => boolean): number {
	return lessons.filter((l) => isDone(l.slug)).length;
}

export function remainingMinutes(lessons: CourseLesson[], isDone: (slug: string) => boolean): number {
	return lessons.filter((l) => !isDone(l.slug)).reduce((sum, l) => sum + (l.minutes ?? 0), 0);
}

export function neighbors(lessons: CourseLesson[], currentOrdinal: number): { previous: CourseLesson | null; next: CourseLesson | null } {
	const sorted = [...lessons].sort((a, b) => a.ordinal - b.ordinal);
	const index = sorted.findIndex((l) => l.ordinal === currentOrdinal);

	return {
		previous: index > 0 ? sorted[index - 1] : null,
		next: index >= 0 && index < sorted.length - 1 ? sorted[index + 1] : null,
	};
}

/** Titled samples for the reader's language (falling back to any language), one tab per title. */
export function workspaceTabs(samples: CodeSample[], language: string | null): CodeSample[] {
	const titled = samples.filter((s) => s.title);
	const preferred = language ? titled.filter((s) => s.language === language) : titled;
	const pool = preferred.length > 0 ? preferred : titled;
	const seen = new Set<string>();

	return pool.filter((s) => {
		if (seen.has(s.title!)) {
			return false;
		}

		seen.add(s.title!);

		return true;
	});
}

export interface LogLine {
	time: string;
	kind: "NETWORK" | "CACHE" | "OUTPUT";
	text: string;
}

/** Turns a step's expected output into network-log rows. Lines starting with an HTTP method, an HTTP status, or
 *  `HTTP/` are NETWORK, lines starting with CACHE are cache hits, everything else is plain output. The status has to
 *  lead the line so that ordinary output containing a three-digit number (`int(100)`, `LIMIT 500`) stays output.
 *  Times are synthetic and increasing. */
export function logLines(expectedOutput: string | null | undefined): LogLine[] {
	if (!expectedOutput) {
		return [];
	}

	return expectedOutput
		.split("\n")
		.map((line) => line.trim())
		.filter(Boolean)
		.map((line, i) => {
			const time = `${(i * 2.9).toFixed(1)}s`;

			if (/^CACHE\b/i.test(line)) {
				return { time, kind: "CACHE", text: line.replace(/^CACHE\s*/i, "") } as LogLine;
			}

			if (/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\b|^HTTP\/|^[1-5]\d\d\b/.test(line)) {
				return { time, kind: "NETWORK", text: line } as LogLine;
			}

			return { time, kind: "OUTPUT", text: line } as LogLine;
		});
}
