import type { DiffChange, DiffResponse, DocParam } from "types/docs";

// Pure helpers behind the upgrade view: which changes are visible, how to move between them, how a contract diff
// renders as lines, and how a locally loaded scan report narrows the list to symbols the reader's code imports.

export type DiffChip = "all" | string;

export interface ScanImport {
	ref: string;
	callSites: number;
	files: number;
}

export interface ScanReport {
	tool: string | null;
	/** Package the report was made for, when the scanner recorded it. */
	package: string | null;
	/** Installed library version the scanner found (a tag or label), so the view can offer it as the "from" version. */
	fromVersion: string | null;
	imports: Map<string, ScanImport>;
}

export interface DiffLine {
	kind: "-" | "+" | " ";
	text: string;
}

export interface ContractDiffBlockData {
	ref: string;
	name: string;
	lines: DiffLine[];
}

const KIND_ORDER = ["breaking", "behavior", "deprecated", "added", "removed"];

/** Every change in the response, in display order (kind order, then the API's order within a kind). */
export function allChanges(diff: DiffResponse): DiffChange[] {
	return [...diff.groups].sort((a, b) => KIND_ORDER.indexOf(a.kind) - KIND_ORDER.indexOf(b.kind)).flatMap((g) => g.changes);
}

/** Kinds present in the response with their counts, in display order. */
export function kindCounts(diff: DiffResponse): { kind: string; count: number }[] {
	return [...diff.groups]
		.filter((g) => g.count > 0)
		.sort((a, b) => KIND_ORDER.indexOf(a.kind) - KIND_ORDER.indexOf(b.kind))
		.map((g) => ({ kind: g.kind, count: g.count }));
}

export function touchesImports(change: DiffChange, report: ScanReport | null): boolean {
	if (!report) {
		return true;
	}

	return change.symbols.some((s) => report.imports.has(s.ref));
}

/** Changes that pass the kind chip and, when a report is loaded and the toggle is on, the imports filter. */
export function visibleChanges(diff: DiffResponse, chip: DiffChip, importsOnly: boolean, report: ScanReport | null): DiffChange[] {
	return allChanges(diff).filter((c) => (chip === "all" || c.kind === chip) && (!importsOnly || touchesImports(c, report)));
}

/** Id of the change `delta` steps from the current one in the visible list, clamped at the ends. */
export function stepChange(visible: DiffChange[], currentId: number | null, delta: number): number | null {
	if (visible.length === 0) {
		return null;
	}

	const index = visible.findIndex((c) => c.id === currentId);

	if (index < 0) {
		return visible[0].id;
	}

	return visible[Math.min(visible.length - 1, Math.max(0, index + delta))].id;
}

/** The first visible change that is not yet reviewed, else the first visible one. */
export function defaultSelection(visible: DiffChange[], isReviewed: (id: number) => boolean): number | null {
	return (visible.find((c) => !isReviewed(c.id)) ?? visible[0])?.id ?? null;
}

export function changeIdFromHash(hash: string): number | null {
	const match = /^#change-(\d+)$/.exec(hash);

	return match ? Number(match[1]) : null;
}

export function hashForChange(id: number): string {
	return `#change-${id}`;
}

function paramLine(param: DocParam): string {
	const optional = param.required ? "" : "?";
	const def = param.default !== null && param.default !== undefined && param.default !== "" ? `        // default ${param.default}` : "";

	return `${param.name}${optional}: ${param.type}${def}`;
}

/**
 * Renders one symbol's contract diff as unified lines: removed and changed parameters as "-", added and changed as
 * "+", unchanged ones plain. A symbol whose signature changed without parameter changes shows the two signatures.
 */
export function contractDiffLines(diff: DiffChange["contractDiffs"][number]): DiffLine[] {
	const lines: DiffLine[] = [];
	const changedNames = new Set(diff.paramsChanged.map((c) => c.name));
	const addedNames = new Set(diff.paramsAdded.map((p) => p.name));
	const removed = new Map(diff.paramsRemoved.map((p) => [p.name, p]));
	const before = new Map<string, DocParam>((diff.before?.params ?? []).map((p) => [p.name, p]));

	// Walk the "after" contract in order so unchanged context stays where the reader expects it.
	for (const param of diff.after?.params ?? []) {
		if (changedNames.has(param.name)) {
			const prev = before.get(param.name);

			if (prev) {
				lines.push({ kind: "-", text: paramLine(prev) });
			}

			lines.push({ kind: "+", text: paramLine(param) });
		} else if (addedNames.has(param.name)) {
			lines.push({ kind: "+", text: paramLine(param) });
		} else {
			lines.push({ kind: " ", text: paramLine(param) });
		}
	}

	for (const param of removed.values()) {
		lines.push({ kind: "-", text: paramLine(param) });
	}

	// No parameter-level changes: show the signatures themselves (only the sides that exist).
	if (lines.length === 0 && (diff.signatureChanged || diff.before === null || diff.after === null)) {
		for (const line of diff.before ? diff.before.signature.split("\n") : []) {
			lines.push({ kind: "-", text: line });
		}

		for (const line of diff.after ? diff.after.signature.split("\n") : []) {
			lines.push({ kind: "+", text: line });
		}
	}

	return lines;
}

export function contractDiffBlocks(change: DiffChange): ContractDiffBlockData[] {
	return change.contractDiffs
		.map((d) => ({ ref: d.ref, name: d.name, lines: contractDiffLines(d) }))
		.filter((b) => b.lines.some((l) => l.kind !== " "));
}

/**
 * Parses a scan report produced by a code scanner (phase 12). Format:
 *   { "tool": "tessel-migrate scan", "imports": [{ "ref": "tessel/query#createQuery", "callSites": 23, "files": 9 }] }
 * Throws with a readable message on anything else.
 */
export function parseScanReport(text: string): ScanReport {
	let parsed: unknown;

	try {
		parsed = JSON.parse(text);
	} catch {
		throw new Error("The scan report is not valid JSON.");
	}

	if (typeof parsed !== "object" || parsed === null || !Array.isArray((parsed as { imports?: unknown }).imports)) {
		throw new Error('The scan report needs an "imports" array.');
	}

	const imports = new Map<string, ScanImport>();

	for (const entry of (parsed as { imports: unknown[] }).imports) {
		if (typeof entry !== "object" || entry === null) {
			continue;
		}

		const record = entry as { ref?: unknown; callSites?: unknown; files?: unknown };

		if (typeof record.ref !== "string" || !record.ref.includes("#")) {
			throw new Error(`Each import needs a "ref" like "module/path#Name" (got ${JSON.stringify(record.ref)}).`);
		}

		imports.set(record.ref, {
			ref: record.ref,
			callSites: typeof record.callSites === "number" ? record.callSites : 0,
			files: typeof record.files === "number" ? record.files : 0,
		});
	}

	const tool = (parsed as { tool?: unknown }).tool;

	const { package: pkg, fromVersion } = parsed as { package?: unknown; fromVersion?: unknown };

	return {
		tool: typeof tool === "string" ? tool : null,
		package: typeof pkg === "string" ? pkg : null,
		fromVersion: typeof fromVersion === "string" && fromVersion !== "" ? fromVersion : null,
		imports,
	};
}

/** "Affects 23 call sites in 9 files" for a change, summed over its symbols present in the report; null without one. */
export function affectedSummary(change: DiffChange, report: ScanReport | null): { callSites: number; files: number } | null {
	if (!report) {
		return null;
	}

	let callSites = 0;
	let files = 0;
	let any = false;

	for (const symbol of change.symbols) {
		const hit = report.imports.get(symbol.ref);

		if (hit) {
			any = true;
			callSites += hit.callSites;
			files += hit.files;
		}
	}

	return any ? { callSites, files } : null;
}
