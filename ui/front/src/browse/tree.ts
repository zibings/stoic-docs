import type { TreeNode } from "primevue/treenode";

import type { SearchRecord } from "search/index";
import type { Mode, SymbolNode, TreeResponse } from "types/docs";

export type BrowseKind = "module" | "symbol" | "course" | "page";

export interface BrowseData {
	kind: BrowseKind;
	route: string | null;
	ref?: string;
	symbol?: SymbolNode;
	symbolKind?: string;
	state?: "current" | "removed";
	sinceLabel?: string | null;
	removedLabel?: string | null;
	count?: number;
	current?: boolean;
	summary?: string;
	minutes?: number | null;
	modulePath?: string;
}

export interface BrowseNode extends TreeNode {
	key: string;
	label: string;
	data: BrowseData;
	children?: BrowseNode[];
	leaf?: boolean;
	selectable?: boolean;
}

export interface ReferenceTreeOptions {
	includeRemoved: boolean;
	filter: string;
	currentRoute: string | null;
	versionLabel: string;
}

function matches(filter: string, ...texts: (string | null | undefined)[]): boolean {
	const needle = filter.trim().toLowerCase();

	if (needle === "") {
		return true;
	}

	return texts.some((t) => (t ?? "").toLowerCase().includes(needle));
}

function symbolNode(node: SymbolNode, modulePath: string, opts: ReferenceTreeOptions): BrowseNode | null {
	if (!opts.includeRemoved && node.state === "removed") {
		return null;
	}

	const children = node.children.map((c) => symbolNode(c, modulePath, opts)).filter((c): c is BrowseNode => c !== null);
	const selfMatches = matches(opts.filter, node.name, node.shortName, node.contract.summary);

	if (!selfMatches && children.length === 0) {
		return null;
	}

	return {
		key: node.ref,
		label: node.shortName,
		leaf: children.length === 0,
		selectable: true,
		children: children.length > 0 ? children : undefined,
		data: {
			kind: "symbol",
			route: node.route,
			ref: node.ref,
			symbol: node,
			symbolKind: node.kind,
			state: node.state,
			sinceLabel: node.sinceLabel,
			removedLabel: node.removedLabel,
			current: opts.currentRoute === node.route,
			summary: node.contract.summary,
			modulePath,
		},
	};
}

/** Modules → symbols (→ nested symbols) for the Reference tab. */
export function buildReferenceTree(tree: TreeResponse, opts: ReferenceTreeOptions): BrowseNode[] {
	const modules: BrowseNode[] = [];

	for (const module of tree.modules) {
		const symbols = module.symbols.map((s) => symbolNode(s, module.path, opts)).filter((s): s is BrowseNode => s !== null);

		if (symbols.length === 0 && !matches(opts.filter, module.path)) {
			continue;
		}

		modules.push({
			key: `module:${module.path}`,
			label: module.path,
			selectable: false,
			children: symbols,
			leaf: symbols.length === 0,
			data: { kind: "module", route: null, count: symbols.length, summary: module.summary, modulePath: module.path },
		});
	}

	return modules;
}

/** Pages of one mode from the manifest records. Learn pages group by course (taken from the route). */
export function buildPagesTree(records: SearchRecord[], mode: Mode, filter: string, currentRoute: string | null): BrowseNode[] {
	const pages = records.filter((r) => r.type === "page" && r.mode === mode);
	const leaf = (r: SearchRecord): BrowseNode | null => {
		const title = String(r.title ?? "");
		const summary = String(r.summary ?? "");

		if (!matches(filter, title, summary)) {
			return null;
		}

		return {
			key: `page:${r.route}`,
			label: title,
			leaf: true,
			selectable: true,
			data: {
				kind: "page",
				route: r.route,
				summary,
				minutes: typeof r.minutes === "number" ? r.minutes : null,
				current: currentRoute === r.route,
			},
		};
	};

	if (mode !== "learn") {
		return pages.map(leaf).filter((n): n is BrowseNode => n !== null);
	}

	const courses = new Map<string, BrowseNode>();

	for (const page of pages) {
		const route = String(page.route ?? "");
		const segments = route.split("/");
		const courseSlug = segments.length >= 5 ? segments[3] : "course";
		const node = leaf(page);

		if (!node) {
			continue;
		}

		let course = courses.get(courseSlug);

		if (!course) {
			course = {
				key: `course:${courseSlug}`,
				label: courseSlug.replace(/-/g, " "),
				selectable: false,
				children: [],
				data: { kind: "course", route: null, count: 0 },
			};

			courses.set(courseSlug, course);
		}

		course.children!.push(node);
		course.data.count = course.children!.length;
	}

	return [...courses.values()];
}

/** Every node in document order, for keyboard-focus lookups. */
export function flattenNodes(nodes: BrowseNode[], into: BrowseNode[] = []): BrowseNode[] {
	for (const node of nodes) {
		into.push(node);

		if (node.children) {
			flattenNodes(node.children, into);
		}
	}

	return into;
}

/** Keys that should start expanded: every branch when filtering, otherwise the branch containing the current node. */
export function defaultExpandedKeys(nodes: BrowseNode[], filtering: boolean): Record<string, boolean> {
	const expanded: Record<string, boolean> = {};

	const visit = (node: BrowseNode, ancestors: BrowseNode[]): void => {
		if (filtering && node.children && node.children.length > 0) {
			expanded[node.key] = true;
		}

		if (node.data.current) {
			for (const a of ancestors) {
				expanded[a.key] = true;
			}
		}

		for (const child of node.children ?? []) {
			visit(child, [...ancestors, node]);
		}
	};

	for (const node of nodes) {
		visit(node, []);
	}

	if (!filtering && Object.keys(expanded).length === 0 && nodes.length > 0 && nodes[0].children) {
		expanded[nodes[0].key] = true;
	}

	return expanded;
}
