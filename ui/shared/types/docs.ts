// Shapes returned by the public /Docs API (api/1.1/Docs.api.php). Kept in one place so views and stores share them.

export type Mode = "learn" | "do" | "reference" | "explain";

/** Every mode a page can be stored in: the reader modes plus the reserved `home` mode of the landing page. */
export type PageMode = Mode | "home";

export interface DocVersion {
	id: number;
	tag: string;
	label: string;
	sortKey: number;
	releasedAt: string | null;
	isLatest: boolean;
	isSupported: boolean;
}

export interface ContextOption {
	key: string;
	label: string;
	isDefault: boolean;
}

export interface SiteInfo {
	libraryName: string;
	repoUrl: string;
	/** Public origin of the docs site, or null until configured. */
	siteUrl: string | null;
	latest: string | null;
	versions: DocVersion[];
	contextOptions: {
		languages: ContextOption[];
		packageManagers: ContextOption[];
	};
	modes: Mode[];
}

export interface DocParam {
	name: string;
	type: string;
	required: boolean;
	default: string | null;
	description: string;
	since?: string | null;
}

export interface Contract {
	id: number;
	symbolId: number;
	introducedVersionId: number;
	removedVersionId: number | null;
	introducedLabel: string | null;
	removedLabel: string | null;
	signature: string;
	summary: string;
	status: string;
	params: DocParam[];
	returns: Record<string, unknown>;
	throws: Record<string, unknown>[];
	sourcePath: string | null;
	sourceLine: number | null;
	sourceUrl: string | null;
}

export interface SymbolNode {
	ref: string;
	name: string;
	shortName: string;
	kind: string;
	state: "current" | "removed";
	sinceLabel: string | null;
	changedLabel: string | null;
	removedLabel: string | null;
	route: string;
	contract: Contract;
	children: SymbolNode[];
	childCount?: number;
	role?: string;
}

export interface TreeModule {
	path: string;
	summary: string;
	count: number;
	symbols: SymbolNode[];
}

export interface TreeResponse {
	version: DocVersion;
	modules: TreeModule[];
}

export interface PageSummary {
	id: number;
	mode: PageMode;
	slug: string;
	title: string;
	summary: string;
	minutes: number | null;
	route: string;
	role?: string;
}

export interface Page extends PageSummary {
	body: string;
	introducedLabel: string | null;
	removedLabel: string | null;
	updated: string;
}

export interface CodeSample {
	key: string;
	title: string | null;
	language: string;
	variant: string | null;
	code: string;
	isTested: boolean;
	lastTestPassLabel: string | null;
}

export interface ChangeSummary {
	id: number;
	kind: string;
	title: string;
	versionLabel: string | null;
	hasCodemod: boolean;
	codemodCmd: string | null;
}

export interface SymbolResponse {
	version: DocVersion;
	module: { path: string; summary: string };
	breadcrumb: string[];
	symbol: SymbolNode;
	/** Sibling type symbols named by parameter types, keyed by type name; the pane flattens their fields. */
	relatedTypes: Record<string, SymbolNode>;
	/** Symbols the reference prose mentions, resolved at the version (may live in other modules). */
	mentions: SymbolNode[];
	page: Page | null;
	samples: CodeSample[];
	testedSamples: { total: number; passingAtVersion: number };
	alsoCoveredIn: PageSummary[];
	changes: ChangeSummary[];
	siblings: SymbolNode[];
}

export interface CourseLesson {
	ordinal: number;
	title: string;
	slug: string;
	minutes: number | null;
	current: boolean;
	route: string;
}

export interface CourseStep {
	ordinal: number;
	prompt: string;
	hint: string | null;
	answer: string | null;
	expectedOutput: string | null;
}

export interface CourseSpine {
	slug: string;
	title: string;
	summary: string;
	totalMinutes: number;
	lessonCount: number;
	currentLesson: number;
	lessons: CourseLesson[];
	steps: CourseStep[];
}

export interface PageResponse {
	version: DocVersion;
	page: Page;
	symbols: SymbolNode[];
	samples: CodeSample[];
	course: CourseSpine | null;
}

export interface DiffChange extends ChangeSummary {
	why: string;
	rfcUrl: string | null;
	beforeCode: string | null;
	afterCode: string | null;
	symbols: { ref: string; name: string; kind: string; route: string }[];
	contractDiffs: {
		ref: string;
		name: string;
		before: Contract | null;
		after: Contract | null;
		signatureChanged: boolean;
		paramsAdded: DocParam[];
		paramsChanged: { name: string; before: DocParam; after: DocParam }[];
		paramsRemoved: DocParam[];
	}[];
}

export interface DiffResponse {
	from: DocVersion;
	to: DocVersion;
	versions: DocVersion[];
	total: number;
	groups: { kind: string; count: number; changes: DiffChange[] }[];
	route: string;
}

export interface CourseListLesson {
	ordinal: number;
	title: string;
	slug: string;
	minutes: number | null;
	route: string;
}

export interface CourseListEntry {
	slug: string;
	title: string;
	summary: string;
	totalMinutes: number;
	lessonCount: number;
	lessons: CourseListLesson[];
}

export interface Manifest {
	version: DocVersion;
	previous: DocVersion | null;
	routes: string[];
	searchRecords: Record<string, unknown>[];
	courses: CourseListEntry[];
}
