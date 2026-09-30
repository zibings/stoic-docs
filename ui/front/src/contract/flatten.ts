import type { DocParam, SymbolNode } from "types/docs";

/** One row in the contract pane's parameter table. */
export interface ContractRow {
	/** Ref the prose uses to point at this row (a real symbol ref for flattened fields, synthetic for plain params). */
	ref: string;
	label: string;
	type: string;
	required: boolean;
	default: string | null;
	description: string;
	changedLabel: string | null;
	sinceLabel: string | null;
}

function leadingTypeName(type: string): string | null {
	const match = /^([A-Za-z_][A-Za-z0-9_]*)/.exec(type.trim());

	return match ? match[1] : null;
}

/**
 * Flattens a symbol's parameters for display. A parameter whose type is a related type symbol with children (an
 * options interface, for example) expands into one row per field, keyed by the child's ref so prose references
 * land on the right row.
 */
export function flattenContract(node: SymbolNode, relatedTypes: Record<string, SymbolNode>): ContractRow[] {
	const rows: ContractRow[] = [];

	for (const param of node.contract.params) {
		const typeName = leadingTypeName(param.type);
		const related = typeName ? relatedTypes[typeName] : undefined;

		if (related && related.children.length > 0) {
			const fields = new Map<string, DocParam>(related.contract.params.map((f) => [f.name, f]));

			for (const child of related.children) {
				if (child.state === "removed") {
					continue;
				}

				const field = fields.get(child.shortName);

				rows.push({
					ref: child.ref,
					label: `${param.name}.${child.shortName}`,
					type: field?.type ?? child.contract.signature,
					required: field?.required ?? false,
					default: field?.default ?? null,
					description: child.contract.summary || field?.description || "",
					changedLabel: child.changedLabel,
					sinceLabel: child.sinceLabel,
				});
			}

			continue;
		}

		rows.push({
			ref: `${node.ref}.${param.name}`,
			label: param.name,
			type: param.type,
			required: param.required,
			default: param.default ?? null,
			description: param.description ?? "",
			changedLabel: null,
			sinceLabel: param.since ?? null,
		});
	}

	return rows;
}

/** A referenced element's position relative to the viewport. */
export interface RefPosition {
	ref: string;
	top: number;
	bottom: number;
}

/**
 * Chooses which referenced element the reader is "at": the last one whose top has crossed the reading line (35%
 * down the viewport), or the first visible one when none has. Returns null when nothing is on screen.
 */
export function pickActiveRef(positions: RefPosition[], viewportHeight: number): string | null {
	const visible = positions.filter((p) => p.bottom > 0 && p.top < viewportHeight);

	if (visible.length === 0) {
		return null;
	}

	const line = viewportHeight * 0.35;
	const passed = visible.filter((p) => p.top <= line).sort((a, b) => b.top - a.top);

	if (passed.length > 0) {
		return passed[0].ref;
	}

	return visible.sort((a, b) => a.top - b.top)[0].ref;
}
