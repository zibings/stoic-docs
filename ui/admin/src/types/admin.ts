// Rows as the /Docs/Admin API returns them (camelCase model fields plus `id`).

export type Resource = "versions" | "modules" | "symbols" | "contracts" | "pages" | "samples" | "changes" | "courses" | "lessons" | "steps" | "contextOptions";

export const RESOURCE_PATHS: Record<Resource, string> = {
	versions: "Versions",
	modules: "Modules",
	symbols: "Symbols",
	contracts: "Contracts",
	pages: "Pages",
	samples: "Samples",
	changes: "Changes",
	courses: "Courses",
	lessons: "Lessons",
	steps: "Steps",
	contextOptions: "ContextOptions",
};

export interface AdminVersion {
	id: number;
	tag: string;
	label: string;
	sortKey: number;
	releasedAt: string | null;
	isLatest: boolean;
	isSupported: boolean;
}

export interface AdminModule {
	id: number;
	path: string;
	summary: string;
	sortOrder: number;
}

export interface AdminSymbol {
	id: number;
	moduleId: number;
	parentSymbolId: number | null;
	name: string;
	kind: string;
	sortOrder: number;
	ref: string;
}

export interface AdminParam {
	name: string;
	type: string;
	required: boolean;
	default: string | null;
	description: string;
	since?: string | null;
}

export interface AdminContract {
	id: number;
	symbolId: number;
	introducedVersionId: number;
	removedVersionId: number | null;
	signature: string;
	summary: string;
	status: string;
	params: AdminParam[];
	returns: { type?: string; description?: string };
	throws: { type?: string; description?: string }[];
	sourcePath: string | null;
	sourceLine: number | null;
}

export interface AdminPage {
	id: number;
	mode: "learn" | "do" | "reference" | "explain" | "home";
	slug: string;
	title: string;
	summary: string;
	body: string;
	introducedVersionId: number;
	removedVersionId: number | null;
	minutes: number | null;
	created: string;
	updated: string;
}

export interface AdminSample {
	id: number;
	pageId: number;
	sampleKey: string;
	title: string | null;
	language: string;
	variant: string | null;
	code: string;
	isTested: boolean;
	lastTestPassVersionId: number | null;
}

export interface AdminChange {
	id: number;
	versionId: number;
	kind: string;
	title: string;
	why: string;
	rfcUrl: string | null;
	beforeCode: string | null;
	afterCode: string | null;
	codemodCmd: string | null;
	sortOrder: number;
}

export interface AdminCourse {
	id: number;
	slug: string;
	title: string;
	summary: string;
	sortOrder: number;
}

export interface AdminLesson {
	id: number;
	courseId: number;
	pageId: number;
	ordinal: number;
}

export interface AdminStep {
	id: number;
	lessonId: number;
	ordinal: number;
	prompt: string;
	hint: string | null;
	answer: string | null;
	expectedOutput: string | null;
}

export interface AdminContextOption {
	id: number;
	kind: "language" | "packageManager";
	optionKey: string;
	label: string;
	sortOrder: number;
	isDefault: boolean;
}

export interface SymbolLink {
	symbolId: number;
	ref: string;
	name: string;
	kind: string;
	role?: "subject" | "mentions";
}

export interface RecordTypes {
	versions: AdminVersion;
	modules: AdminModule;
	symbols: AdminSymbol;
	contracts: AdminContract;
	pages: AdminPage;
	samples: AdminSample;
	changes: AdminChange;
	courses: AdminCourse;
	lessons: AdminLesson;
	steps: AdminStep;
	contextOptions: AdminContextOption;
}
