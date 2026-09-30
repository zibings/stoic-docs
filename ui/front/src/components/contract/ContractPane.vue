<template>
	<div class="pane contract" :class="{ 'contract--compact': compact }">
		<div class="contract__head">
			<span class="contract__label">Contract</span>
			<span class="contract__following">
				following:
				<span class="contract__following-name">{{ activeRow?.label ?? "—" }}</span>
			</span>
		</div>

		<pre class="contract__signature"><code class="hljs" v-html="signatureHtml" /></pre>

		<section v-if="rows.length > 0" class="contract__section">
			<div class="contract__label">Parameters</div>
			<ul class="contract__rows">
				<li v-for="row in rows" :key="row.ref" class="contract__row" :class="{ 'is-active': row.ref === activeRef }" :data-contract-ref="row.ref">
					<div class="contract__row-main">
						<span class="contract__row-name">{{ row.label }}</span>
						<span class="contract__row-type">{{ typeText(row) }}</span>
					</div>
					<p v-if="row.description" class="contract__row-desc">
						{{ row.description }}
						<span v-if="row.changedLabel" class="contract__row-note">Changed in {{ row.changedLabel }}.</span>
					</p>
				</li>
			</ul>
		</section>

		<section v-if="returnsText" class="contract__section">
			<div class="contract__label">Returns</div>
			<div class="contract__line">
				<span class="contract__type-link">{{ returns.type }}</span>
				<span v-if="returns.description" class="contract__muted"> — {{ returns.description }}</span>
			</div>
		</section>

		<section v-if="throws.length > 0" class="contract__section">
			<div class="contract__label">Throws</div>
			<div v-for="(t, i) in throws" :key="i" class="contract__line">
				<span class="contract__type-link">{{ t.type }}</span>
				<span v-if="t.description" class="contract__muted"> — {{ t.description }}</span>
			</div>
		</section>

		<div class="contract__foot">
			<a v-if="node.contract.sourceUrl" :href="node.contract.sourceUrl" class="contract__source" target="_blank" rel="noopener">
				{{ node.contract.sourcePath }}<template v-if="node.contract.sourceLine">#L{{ node.contract.sourceLine }}</template> ↗
			</a>
			<div v-if="testedSamples && testedSamples.total > 0" class="contract__muted">
				Every example on this page is a test · {{ testedSamples.passingAtVersion }} passing on {{ versionLabel }}
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed } from "vue";

import { flattenContract } from "contract/flatten";
import type { ContractRow } from "contract/flatten";
import { highlight } from "markdown/highlight";
import type { SymbolNode } from "types/docs";

const props = withDefaults(
	defineProps<{
		node: SymbolNode;
		relatedTypes: Record<string, SymbolNode>;
		activeRef: string | null;
		versionLabel: string;
		/** Language key used to highlight the signature. */
		language: string | null;
		testedSamples?: { total: number; passingAtVersion: number } | null;
		compact?: boolean;
	}>(),
	{ testedSamples: null, compact: false },
);

const rows = computed(() => flattenContract(props.node, props.relatedTypes));
const activeRow = computed(() => rows.value.find((r) => r.ref === props.activeRef) ?? null);
const signatureHtml = computed(() => highlight(props.node.contract.signature, props.language));

const returns = computed(() => props.node.contract.returns as { type?: string; description?: string });
const returnsText = computed(() => Boolean(returns.value?.type));
const throws = computed(() => props.node.contract.throws as { type?: string; description?: string }[]);

function typeText(row: ContractRow): string {
	if (row.required) {
		return `${row.type} · required`;
	}

	return row.default !== null && row.default !== "" ? `${row.type} = ${row.default}` : row.type;
}

defineExpose({ rows, activeRow });
</script>

<style>
.contract {
	display: flex;
	flex-direction: column;
	gap: 22px;
	padding: 28px;
	background: var(--pane-bg);
	color: var(--pane-text);
	font-family: var(--font-mono);
	min-height: 100%;
}

.contract__head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
}

.contract__label {
	font-size: var(--text-label);
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: var(--pane-muted);
}

.contract__following {
	font-size: var(--text-label);
	color: var(--pane-muted);
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.contract__following-name {
	color: var(--pane-link);
}

.contract__signature {
	padding: 14px 16px;
	border-radius: var(--radius-card);
	background: var(--pane-code);
	line-height: 1.7;
	overflow-x: auto;
}

.contract__section {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.contract__rows {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
}

.contract__row {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 8px 0;
	border-top: 1px solid var(--pane-rule);
	font-size: var(--text-meta);
	transition: background-color 120ms ease;
}

.contract__row-main {
	display: flex;
	justify-content: space-between;
	gap: 12px;
}

.contract__row-type {
	color: var(--pane-muted);
	text-align: right;
}

.contract__row-desc {
	display: none;
	font-family: var(--font-sans);
	font-size: var(--text-meta);
	color: var(--pane-text-soft);
}

.contract__row-note {
	color: var(--pane-warn);
}

.contract__row.is-active {
	padding: 10px 12px;
	margin: 0 -12px;
	border-top-color: transparent;
	border-radius: var(--radius-control);
	background: var(--pane-focus-row);
}

.contract__row.is-active + .contract__row {
	border-top-color: transparent;
}

.contract__row.is-active .contract__row-name {
	color: #ffffff;
	font-weight: 600;
}

.contract__row.is-active .contract__row-type {
	color: var(--pane-value);
}

.contract__row.is-active .contract__row-desc {
	display: block;
}

.contract__line {
	font-size: var(--text-meta);
}

.contract__type-link {
	color: var(--pane-link);
}

.contract__muted {
	color: var(--pane-muted);
}

.contract__foot {
	margin-top: auto;
	display: flex;
	flex-direction: column;
	gap: 10px;
	padding-top: 16px;
	border-top: 1px solid var(--pane-rule);
	font-size: var(--text-meta);
}

.contract__source {
	color: var(--pane-link);
}

.contract--compact {
	padding: 8px 18px 20px;
	gap: 16px;
	min-height: 0;
}
</style>
