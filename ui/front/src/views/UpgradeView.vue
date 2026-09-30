<template>
	<div v-if="status === 400" class="shell-content"><p class="muted">The starting version must be older than the target version.</p></div>
	<div v-else-if="notFound" class="shell-content"><p class="muted">One of those versions does not exist.</p></div>
	<div v-else-if="error" class="shell-content"><p class="muted">Could not load the changes: {{ error }}</p></div>
	<div v-else-if="!data" class="shell-content"><p class="muted">Loading…</p></div>

	<div v-else class="upgrade-layout">
		<details class="upgrade-layout__mobile-list" :open="mobileListOpen" @toggle="mobileListOpen = ($event.target as HTMLDetailsElement).open">
			<summary class="upgrade-layout__mobile-summary">
				<span class="mono">{{ from }} → {{ to }}</span>
				<span class="muted">{{ selected ? selected.title : `${data.total} changes` }}</span>
			</summary>
			<ChangeList v-bind="listProps" @update:chip="chip = $event" @update:imports-only="importsOnly = $event" @select="onSelectMobile" />
		</details>

		<ChangeList class="upgrade-layout__list" v-bind="listProps" @update:chip="chip = $event" @update:imports-only="importsOnly = $event" @select="select" />

		<main class="upgrade-layout__detail">
			<ChangeDetail v-if="selected" :key="selected.id" :change="selected" :from="from" :to="to" :language="context.effectiveLanguage" :is-reviewed="review.isReviewed(selected.id)" :report="scan.report" @review-next="reviewAndNext" />
			<div v-else class="shell-content"><p class="muted">Nothing to show for these filters.</p></div>
		</main>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import { docsApi } from "api/docs";
import ChangeDetail from "components/upgrade/ChangeDetail.vue";
import ChangeList from "components/upgrade/ChangeList.vue";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import { useContextStore } from "stores/context";
import { useReviewStore } from "stores/review";
import { useScanStore } from "stores/scan";
import { useUiStore } from "stores/ui";
import { allChanges, changeIdFromHash, defaultSelection, hashForChange, kindCounts, stepChange, visibleChanges } from "upgrade/diff";
import type { DiffChip } from "upgrade/diff";

const props = defineProps<{ from: string; to: string }>();

const route = useRoute();
const router = useRouter();
const context = useContextStore();
const review = useReviewStore();
const scan = useScanStore();
const ui = useUiStore();

const key = computed(() => `diff:${props.from}:${props.to}`);
const { data, error, notFound, status } = useDocsData(key, () => docsApi.diff(props.from, props.to));

usePageMeta(
	computed(() => `Upgrade ${props.from} → ${props.to}`),
	computed(() => (data.value ? `${data.value.total} changes between ${props.from} and ${props.to}` : null)),
);

const chip = ref<DiffChip>("all");
const importsOnly = ref(false);
const mobileListOpen = ref(false);
/** Set by clicks and keys; the hash and the default selection are folded in by `selectedId` below. */
const manualSelection = ref<number | null>(null);

watch(
	() => [props.from, props.to] as const,
	([from, to]) => {
		review.load(from, to);
		chip.value = "all";
	},
	{ immediate: true },
);

watch(
	() => scan.report,
	(report) => {
		if (!report) {
			importsOnly.value = false;
		}
	},
);

const kinds = computed(() => (data.value ? kindCounts(data.value) : []));
const visible = computed(() => (data.value ? visibleChanges(data.value, chip.value, importsOnly.value, scan.report) : []));
const hiddenByImports = computed(() => (data.value && importsOnly.value ? visibleChanges(data.value, chip.value, false, null).length - visible.value.length : 0));

// Derived rather than watched so it also resolves during pre-rendering, where watchers do not flush. Precedence:
// the URL hash, then the reader's last click or key press, then the first unreviewed visible change.
const selectedId = computed<number | null>(() => {
	const list = visible.value;
	const fromHash = changeIdFromHash(route.hash);

	if (fromHash !== null && list.some((c) => c.id === fromHash)) {
		return fromHash;
	}

	if (manualSelection.value !== null && list.some((c) => c.id === manualSelection.value)) {
		return manualSelection.value;
	}

	return defaultSelection(list, (id) => review.isReviewed(id));
});

const selected = computed(() => visible.value.find((c) => c.id === selectedId.value) ?? null);

const listProps = computed(() => ({
	from: props.from,
	to: props.to,
	total: data.value?.total ?? 0,
	kinds: kinds.value,
	chip: chip.value,
	importsOnly: importsOnly.value,
	hiddenByImports: hiddenByImports.value,
	hasReport: scan.report !== null,
	visible: visible.value,
	selectedId: selectedId.value,
	isReviewed: (id: number) => review.isReviewed(id),
	reviewedCount: (data.value ? allChanges(data.value).filter((c) => review.isReviewed(c.id)).length : 0),
}));

function select(id: number): void {
	manualSelection.value = id;
	router.replace({ hash: hashForChange(id) });
}

function onSelectMobile(id: number): void {
	select(id);
	mobileListOpen.value = false;
}

function move(delta: number): void {
	const next = stepChange(visible.value, selectedId.value, delta);

	if (next !== null) {
		select(next);
		document.getElementById(`change-row-${next}`)?.scrollIntoView?.({ block: "nearest" });
	}
}

function reviewAndNext(): void {
	if (selectedId.value === null) {
		return;
	}

	review.mark(selectedId.value);
	move(1);
}

function onKeydown(event: KeyboardEvent): void {
	const target = event.target as HTMLElement | null;

	if (event.metaKey || event.ctrlKey || event.altKey || ui.searchOpen || ui.browseOpen) {
		return;
	}

	if (target && (target.tagName === "INPUT" || target.tagName === "TEXTAREA" || target.tagName === "SELECT" || target.isContentEditable)) {
		return;
	}

	if (event.key === "j" || event.key === "J") {
		event.preventDefault();
		move(1);
	} else if (event.key === "k" || event.key === "K") {
		event.preventDefault();
		move(-1);
	} else if ((event.key === "r" || event.key === "R") && selectedId.value !== null) {
		event.preventDefault();
		review.toggle(selectedId.value);
	}
}

onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onKeydown));
</script>

<style>
.upgrade-layout {
	flex: 1 1 auto;
	display: grid;
	grid-template-columns: 460px minmax(0, 1fr);
	min-height: 0;
}

.upgrade-layout__mobile-list {
	display: none;
}

.upgrade-layout__detail {
	min-width: 0;
	display: flex;
	flex-direction: column;
}

.muted {
	color: var(--color-muted);
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 1180px) {
	.upgrade-layout {
		grid-template-columns: minmax(0, 1fr);
		align-content: start;
	}

	.upgrade-layout__list {
		display: none;
	}

	.upgrade-layout__mobile-list {
		display: block;
		border-bottom: 1px solid var(--color-rule);
	}

	.upgrade-layout__mobile-list .change-list {
		position: static;
		height: auto;
		border-right: 0;
	}

	.upgrade-layout__mobile-summary {
		display: flex;
		gap: 12px;
		align-items: baseline;
		min-height: var(--touch-target);
		padding: 10px 18px;
		cursor: pointer;
		font-size: var(--text-small);
		list-style: none;
	}

	.upgrade-layout__mobile-summary::-webkit-details-marker {
		display: none;
	}
}
</style>
