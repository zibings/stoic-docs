import MiniSearch from "minisearch";
import type { AsPlainObject, Options } from "minisearch";

import type { DocVersion, Mode } from "types/docs";

// Search runs over the manifest's search records (symbols, pages, changes) for one version. Phase 7 emits the same
// records as a static file per version; everything here stays the same.

export type RecordType = "symbol" | "page" | "change";

export interface SearchRecord {
	type: RecordType;
	route: string | null;
	[key: string]: unknown;
}

export interface SearchDoc {
	id: string;
	type: RecordType;
	title: string;
	/** Last segment of a dotted symbol name, or the title; drives the starts-with boost. */
	shortName: string;
	text: string;
	kind: string;
	mode: Mode | "";
	module: string;
	route: string | null;
	record: SearchRecord;
}

export type SearchChip = "all" | "reference" | "do" | "explain" | "errors" | "changelog";

export const CHIPS: { key: SearchChip; label: string }[] = [
	{ key: "all", label: "All" },
	{ key: "reference", label: "Reference" },
	{ key: "do", label: "Do" },
	{ key: "explain", label: "Explain" },
	{ key: "errors", label: "Errors" },
	{ key: "changelog", label: "Changelog" },
];

export type GroupKey = "symbols" | "tasks" | "concepts" | "lessons";

export interface ResultGroup {
	key: GroupKey;
	label: string;
	items: SearchDoc[];
}

const GROUP_LABELS: Record<GroupKey, string> = {
	symbols: "Symbols",
	tasks: "Tasks",
	concepts: "Concepts & changes",
	lessons: "Lessons",
};

const GROUP_LIMITS: Record<GroupKey, number> = { symbols: 6, tasks: 4, concepts: 4, lessons: 3 };

function str(value: unknown): string {
	return typeof value === "string" ? value : "";
}

export function toDocs(records: SearchRecord[]): SearchDoc[] {
	return records.map((record) => {
		if (record.type === "symbol") {
			const name = str(record.name);

			return {
				id: `symbol:${str(record.ref)}`,
				type: "symbol",
				title: name,
				shortName: name.includes(".") ? name.slice(name.lastIndexOf(".") + 1) : name,
				text: [str(record.summary), str(record.signature), str(record.module)].join(" "),
				kind: str(record.kind),
				mode: "reference",
				module: str(record.module),
				route: record.route,
				record,
			};
		}

		if (record.type === "page") {
			return {
				id: `page:${str(record.mode)}/${str(record.slug)}`,
				type: "page",
				title: str(record.title),
				shortName: str(record.title),
				text: str(record.summary),
				kind: "page",
				mode: str(record.mode) as Mode,
				module: "",
				route: record.route,
				record,
			};
		}

		return {
			id: `change:${String(record.id ?? record.title)}`,
			type: "change",
			title: str(record.title),
			shortName: str(record.title),
			text: [str(record.why), str(record.kind), str(record.versionLabel)].join(" "),
			kind: str(record.kind),
			mode: "",
			module: "",
			route: record.route,
			record,
		};
	});
}

/** Index options shared by the build-time writer and the browser loader; a serialized index only loads with these. */
export function indexOptions(): Options<SearchDoc> {
	return {
		fields: ["title", "text", "module", "kind"],
		storeFields: ["id", "shortName"],
		searchOptions: {
			boost: { title: 4, module: 1.5 },
			prefix: true,
			fuzzy: 0.2,
			// Names are short; do not let a long title lose to a short one on length alone.
			bm25: { k: 1.2, b: 0.3, d: 0.5 },
			// Typing "stale" should surface things named stale…: favor documents whose own name starts with the term.
			boostDocument: (_id, term, storedFields) => {
				const shortName = String(storedFields?.shortName ?? "").toLowerCase();

				return shortName.startsWith(term.toLowerCase()) ? 2.5 : 1;
			},
		},
		// Split on whitespace and punctuation but keep dotted names searchable both ways: "QueryOptions.staleTime"
		// indexes as itself, "QueryOptions", and "staleTime".
		tokenize: (text) => {
			const base = text.split(/[\s,;:()<>[\]{}"'`=|/\\?!]+/).filter(Boolean);
			const extra: string[] = [];

			for (const token of base) {
				// Dotted path first ("QueryOptions.staleTime" → "QueryOptions", "staleTime"), then camel-case parts of
				// each piece ("staleTime" → "stale", "Time").
				const pieces = token.includes(".") ? token.split(".").filter(Boolean) : [];

				extra.push(...pieces);

				for (const piece of pieces.length > 0 ? pieces : [token]) {
					const camel = piece.split(/(?<=[a-z0-9])(?=[A-Z])/);

					if (camel.length > 1) {
						extra.push(...camel);
					}
				}
			}

			return [...new Set([...base, ...extra].map((t) => t.toLowerCase()))];
		},
		processTerm: (term) => term.toLowerCase(),
	};
}

export function buildIndex(docs: SearchDoc[]): MiniSearch<SearchDoc> {
	const index = new MiniSearch<SearchDoc>(indexOptions());

	index.addAll(docs);

	return index;
}

/** Serializes an index for the static per-version search file. */
export function serializeIndex(index: MiniSearch<SearchDoc>): AsPlainObject {
	return index.toJSON();
}

/** Restores an index written by serializeIndex. */
export function loadIndex(serialized: AsPlainObject): MiniSearch<SearchDoc> {
	return MiniSearch.loadJS<SearchDoc>(serialized, indexOptions());
}

/** Shape of web/search/{version}.json. */
export interface StaticSearchFile {
	version: { label: string; tag: string; sortKey: number };
	generatedAt: string;
	records: SearchRecord[];
	index: AsPlainObject;
}

export function matchesChip(doc: SearchDoc, chip: SearchChip): boolean {
	switch (chip) {
		case "all":
			return true;
		case "reference":
			return doc.type === "symbol";
		case "do":
			return doc.type === "page" && doc.mode === "do";
		case "explain":
			return doc.type === "page" && doc.mode === "explain";
		case "errors":
			return doc.type === "symbol" && doc.kind === "error";
		case "changelog":
			return doc.type === "change";
	}
}

function groupOf(doc: SearchDoc): GroupKey | null {
	if (doc.type === "symbol") {
		return "symbols";
	}

	if (doc.type === "change") {
		return "concepts";
	}

	switch (doc.mode) {
		case "do":
			return "tasks";
		case "explain":
			return "concepts";
		case "learn":
			return "lessons";
		default:
			return null;
	}
}

export function groupResults(docs: SearchDoc[], chip: SearchChip): ResultGroup[] {
	const buckets: Record<GroupKey, SearchDoc[]> = { symbols: [], tasks: [], concepts: [], lessons: [] };

	for (const doc of docs) {
		if (!matchesChip(doc, chip)) {
			continue;
		}

		const group = groupOf(doc);

		if (group && buckets[group].length < GROUP_LIMITS[group]) {
			buckets[group].push(doc);
		}
	}

	return (Object.keys(buckets) as GroupKey[])
		.filter((key) => buckets[key].length > 0)
		.map((key) => ({ key, label: GROUP_LABELS[key], items: buckets[key] }));
}

/** Runs a query; an empty query returns a browse-style sample so the palette never opens empty. */
export function runSearch(index: MiniSearch<SearchDoc>, docs: SearchDoc[], query: string, chip: SearchChip): ResultGroup[] {
	const trimmed = query.trim();

	if (trimmed === "") {
		return groupResults(docs, chip);
	}

	const byId = new Map(docs.map((d) => [d.id, d]));
	const hits = index.search(trimmed).map((hit) => byId.get(String(hit.id))).filter((d): d is SearchDoc => d !== undefined);

	return groupResults(hits, chip);
}

/** Flattens groups into one ordered list for keyboard navigation. */
export function flattenGroups(groups: ResultGroup[]): SearchDoc[] {
	return groups.flatMap((g) => g.items);
}

/** Right-hand detail for a symbol result: the value part of an option signature, otherwise its module. */
export function describeSymbol(doc: SearchDoc): string {
	const signature = str(doc.record.signature);

	if ((doc.kind === "option" || doc.kind === "const") && signature.includes(":")) {
		return signature.slice(signature.indexOf(":") + 1).trim();
	}

	return doc.module;
}

/** For a removed symbol: the removal label and where to read about what replaced it. */
export function removedNote(doc: SearchDoc, versions: DocVersion[]): { label: string; route: string | null; replacedBy: string | null } | null {
	const removedLabel = str(doc.record.removedLabel);

	if (doc.record.state !== "removed" || !removedLabel) {
		return null;
	}

	const sorted = [...versions].sort((a, b) => a.sortKey - b.sortKey);
	const idx = sorted.findIndex((v) => v.label === removedLabel);
	const previous = idx > 0 ? sorted[idx - 1] : null;

	return {
		label: `removed in ${removedLabel}`,
		route: previous ? `/upgrade/${previous.label}/${removedLabel}` : null,
		replacedBy: str(doc.record.replacedBy) || null,
	};
}

/** Whether a change affects a reader whose trail includes a version older than the change's version. */
export function affectsReader(doc: SearchDoc, versions: DocVersion[], hasVersionOlderThan: (sortKey: number) => boolean): string | null {
	const label = str(doc.record.versionLabel);
	const version = versions.find((v) => v.label === label);

	if (doc.type !== "change" || !version || !hasVersionOlderThan(version.sortKey)) {
		return null;
	}

	const older = versions.filter((v) => v.sortKey < version.sortKey).sort((a, b) => a.sortKey - b.sortKey);
	const from = older.length > 0 ? older[older.length - 1] : null;

	return from ? `affects you if upgrading from ${from.label}` : "affects you";
}
