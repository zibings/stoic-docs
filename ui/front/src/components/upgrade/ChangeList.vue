<template>
	<aside class="change-list" aria-label="Changes">
		<div class="change-list__head">
			<div class="change-list__range mono">{{ from }} → {{ to }}</div>
			<div class="change-list__summary">{{ summary }}</div>
		</div>

		<div class="change-list__chips" role="group" aria-label="Filter by kind">
			<button type="button" class="chip" :class="{ 'is-active': chip === 'all' }" :aria-pressed="chip === 'all'" @click="emit('update:chip', 'all')">All {{ total }}</button>
			<button v-for="k in kinds" :key="k.kind" type="button" class="chip" :class="{ 'is-active': chip === k.kind }" :aria-pressed="chip === k.kind" @click="emit('update:chip', k.kind)">
				{{ kindLabel(k.kind) }} {{ k.count }}
			</button>
		</div>

		<ScanReportLoader :imports-only="importsOnly" :hidden="hiddenByImports" :total="total" @update:imports-only="emit('update:importsOnly', $event)" />

		<nav class="change-list__groups" aria-label="Change list">
			<p v-if="visible.length === 0" class="change-list__empty">No changes match these filters.</p>
			<section v-for="group in grouped" :key="group.kind" class="change-list__group">
				<div class="label change-list__group-label">{{ kindLabel(group.kind) }}</div>
				<a
					v-for="change in group.changes"
					:id="`change-row-${change.id}`"
					:key="change.id"
					:href="hashForChange(change.id)"
					class="change-row"
					:class="{ 'is-selected': change.id === selectedId }"
					:aria-current="change.id === selectedId ? 'true' : undefined"
					@click.prevent="emit('select', change.id)"
				>
					<span class="change-row__check" aria-hidden="true">
						<svg v-if="isReviewed(change.id)" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10" /></svg>
					</span>
					<span class="visually-hidden">{{ isReviewed(change.id) ? "Reviewed: " : "" }}</span>
					<span class="change-row__title">{{ change.title }}</span>
					<span v-if="change.hasCodemod" class="pill pill--accent change-row__tag">codemod</span>
					<span class="change-row__version mono">{{ change.versionLabel }}</span>
				</a>
			</section>
		</nav>

		<div class="change-list__progress">
			<div class="change-list__bar" role="progressbar" :aria-valuenow="reviewedCount" :aria-valuemin="0" :aria-valuemax="total" :aria-label="`${reviewedCount} of ${total} reviewed`">
				<div class="change-list__bar-fill" :style="{ width: `${total > 0 ? Math.round((reviewedCount / total) * 100) : 0}%` }" />
			</div>
			<span>{{ reviewedCount }} of {{ total }} reviewed</span>
		</div>
	</aside>
</template>

<script setup lang="ts">
import { computed } from "vue";

import ScanReportLoader from "components/upgrade/ScanReportLoader.vue";
import type { DiffChip } from "upgrade/diff";
import { hashForChange } from "upgrade/diff";
import type { DiffChange } from "types/docs";

const props = defineProps<{
	from: string;
	to: string;
	total: number;
	kinds: { kind: string; count: number }[];
	chip: DiffChip;
	importsOnly: boolean;
	hiddenByImports: number;
	hasReport: boolean;
	visible: DiffChange[];
	selectedId: number | null;
	isReviewed: (id: number) => boolean;
	reviewedCount: number;
}>();

const emit = defineEmits<{
	(e: "update:chip", value: DiffChip): void;
	(e: "update:importsOnly", value: boolean): void;
	(e: "select", id: number): void;
}>();

const kindLabels: Record<string, string> = { breaking: "Breaking", behavior: "Behavior", deprecated: "Deprecated", added: "Added", removed: "Removed" };

function kindLabel(kind: string): string {
	return kindLabels[kind] ?? kind;
}

const breaking = computed(() => props.kinds.find((k) => k.kind === "breaking")?.count ?? 0);

const summary = computed(() => {
	const touch = props.hasReport ? `${props.visible.length} change${props.visible.length === 1 ? "" : "s"} touch code you import` : `${props.total} change${props.total === 1 ? "" : "s"}`;

	return breaking.value > 0 ? `${touch} · ${breaking.value} breaking` : touch;
});

const grouped = computed(() => {
	const order = props.kinds.map((k) => k.kind);
	const buckets = new Map<string, DiffChange[]>();

	for (const change of props.visible) {
		const list = buckets.get(change.kind) ?? [];

		list.push(change);
		buckets.set(change.kind, list);
	}

	return order.filter((k) => buckets.has(k)).map((kind) => ({ kind, changes: buckets.get(kind)! }));
});
</script>

<style>
.change-list {
	display: flex;
	flex-direction: column;
	gap: 14px;
	padding: 28px;
	border-right: 1px solid var(--color-rule);
	position: sticky;
	top: var(--header-height);
	height: calc(100vh - var(--header-height));
	overflow-y: auto;
}

.change-list__head {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.change-list__range {
	font-size: 26px;
	font-weight: 600;
	letter-spacing: -0.02em;
}

.change-list__summary {
	font-size: var(--text-small);
	color: var(--color-muted);
}

.change-list__chips {
	display: flex;
	gap: 6px;
	flex-wrap: wrap;
}

.chip {
	height: 30px;
	padding: 0 11px;
	border-radius: var(--radius-pill);
	border: 1px solid var(--color-control-border);
	background: var(--color-surface);
	color: var(--color-ink3);
	font-size: var(--text-meta);
	white-space: nowrap;
}

@media (max-width: 768px) {
	.chip {
		height: var(--touch-target);
		padding: 0 14px;
	}
}

.chip.is-active {
	border-color: var(--color-ink);
	background: var(--color-ink);
	color: var(--color-surface);
}

.change-list__groups {
	display: flex;
	flex-direction: column;
}

.change-list__empty {
	color: var(--color-muted);
	font-size: var(--text-small);
	padding: 12px 0;
}

.change-list__group {
	display: flex;
	flex-direction: column;
}

.change-list__group-label {
	padding: 12px 0 4px;
}

.change-row {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 9px 12px;
	margin: 0 -12px;
	border-radius: var(--radius-control);
	color: var(--color-ink);
	font-size: var(--text-small);
	line-height: 1.4;
	text-decoration: none;
}

.change-row:hover {
	background: var(--color-surface-sunk);
	text-decoration: none;
}

.change-row.is-selected {
	background: var(--color-chip);
}

.change-row.is-selected .change-row__title {
	font-weight: 600;
}

.change-row__check {
	width: 14px;
	flex-shrink: 0;
	color: var(--color-accent);
	display: inline-flex;
}

.change-row__title {
	flex: 1 1 auto;
	min-width: 0;
}

.change-row__tag {
	font-size: 11px;
	padding: 1px 6px;
	border-radius: 3px;
}

.change-row__version {
	font-size: var(--text-label);
	color: var(--color-muted);
}

.change-list__progress {
	margin-top: auto;
	display: flex;
	align-items: center;
	gap: 12px;
	font-size: var(--text-meta);
	color: var(--color-muted);
	padding-top: 12px;
}

.change-list__bar {
	flex: 1 1 auto;
	height: 4px;
	border-radius: 2px;
	background: var(--color-rule);
	overflow: hidden;
}

.change-list__bar-fill {
	height: 100%;
	background: var(--color-accent);
	border-radius: 2px;
	transition: width 160ms ease;
}

.mono {
	font-family: var(--font-mono);
}
</style>
