import type { Resource } from "types/admin";

// Form helpers shared by the editors: blank records, required-field validation, and payload shaping.

export type Errors = Record<string, string>;

const REQUIRED: Record<Resource, string[]> = {
	versions: ["tag", "label", "sortKey"],
	modules: ["path"],
	symbols: ["moduleId", "name", "kind"],
	contracts: ["symbolId", "introducedVersionId", "signature"],
	pages: ["mode", "slug", "title", "introducedVersionId"],
	samples: ["pageId", "sampleKey", "language", "code"],
	changes: ["versionId", "kind", "title"],
	courses: ["slug", "title"],
	lessons: ["courseId", "pageId", "ordinal"],
	steps: ["lessonId", "ordinal", "prompt"],
	contextOptions: ["kind", "optionKey", "label"],
};

const LABELS: Record<string, string> = {
	tag: "Tag",
	label: "Label",
	sortKey: "Sort key",
	path: "Path",
	moduleId: "Module",
	name: "Name",
	kind: "Kind",
	symbolId: "Symbol",
	introducedVersionId: "Introduced version",
	signature: "Signature",
	mode: "Mode",
	slug: "Slug",
	title: "Title",
	pageId: "Page",
	sampleKey: "Sample key",
	language: "Language",
	code: "Code",
	versionId: "Version",
	courseId: "Course",
	ordinal: "Ordinal",
	lessonId: "Lesson",
	prompt: "Prompt",
	optionKey: "Key",
};

export function fieldLabel(field: string): string {
	return LABELS[field] ?? field;
}

function isBlank(value: unknown): boolean {
	return value === null || value === undefined || (typeof value === "string" && value.trim() === "");
}

/** Missing required fields, keyed by field with a readable message. */
export function validate(resource: Resource, record: Record<string, unknown>): Errors {
	const errors: Errors = {};

	for (const field of REQUIRED[resource]) {
		if (isBlank(record[field])) {
			errors[field] = `${fieldLabel(field)} is required.`;
		}
	}

	if (resource === "versions" && !isBlank(record.sortKey) && !Number.isInteger(Number(record.sortKey))) {
		errors.sortKey = "Sort key must be a whole number.";
	}

	if (resource === "contracts" && record.removedVersionId !== null && record.removedVersionId !== undefined && record.removedVersionId === record.introducedVersionId) {
		errors.removedVersionId = "Removed version must differ from the introduced version.";
	}

	return errors;
}

/** Strips server-assigned fields before sending. */
export function toPayload<T extends Record<string, unknown>>(record: T): Partial<T> {
	const copy = { ...record } as Record<string, unknown>;

	delete copy.id;
	delete copy.ref;
	delete copy.created;
	delete copy.updated;

	return copy as Partial<T>;
}

export function blank(resource: Resource): Record<string, unknown> {
	switch (resource) {
		case "versions":
			return { tag: "", label: "", sortKey: 0, releasedAt: null, isLatest: false, isSupported: true };
		case "modules":
			return { path: "", summary: "", sortOrder: 0 };
		case "symbols":
			return { moduleId: null, parentSymbolId: null, name: "", kind: "fn", sortOrder: 0 };
		case "contracts":
			return { symbolId: null, introducedVersionId: null, removedVersionId: null, signature: "", summary: "", status: "stable", params: [], returns: {}, throws: [], sourcePath: null, sourceLine: null };
		case "pages":
			return { mode: "do", slug: "", title: "", summary: "", body: "", introducedVersionId: null, removedVersionId: null, minutes: null };
		case "samples":
			return { pageId: null, sampleKey: "", title: null, language: "", variant: null, code: "", isTested: false, lastTestPassVersionId: null };
		case "changes":
			return { versionId: null, kind: "breaking", title: "", why: "", rfcUrl: null, beforeCode: null, afterCode: null, codemodCmd: null, sortOrder: 0 };
		case "courses":
			return { slug: "", title: "", summary: "", sortOrder: 0 };
		case "lessons":
			return { courseId: null, pageId: null, ordinal: 1 };
		case "steps":
			return { lessonId: null, ordinal: 1, prompt: "", hint: null, answer: null, expectedOutput: null };
		case "contextOptions":
			return { kind: "language", optionKey: "", label: "", sortOrder: 0, isDefault: false };
	}
}

/** Slug from a title: lowercase, hyphens, ascii. */
export function slugify(text: string): string {
	return text
		.toLowerCase()
		.normalize("NFKD")
		.replace(/[̀-ͯ]/g, "")
		.replace(/[^a-z0-9]+/g, "-")
		.replace(/^-+|-+$/g, "");
}

export const SYMBOL_KINDS = ["fn", "class", "method", "type", "option", "const", "error"];
export const CHANGE_KINDS = ["breaking", "behavior", "deprecated", "added", "removed"];
/** The four reader modes plus `home`, whose single page (slug `index`) is the landing page hero. */
export const PAGE_MODES = ["learn", "do", "reference", "explain", "home"];
export const STATUSES = ["stable", "experimental", "deprecated"];
