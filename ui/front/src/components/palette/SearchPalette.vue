<template>
	<PaletteDialog :visible="ui.searchOpen" label="Search" size="search" @update:visible="onVisible" @keydown="onKeydown">
		<div class="search__head">
			<SearchIcon :size="18" class="search__icon" />
			<label for="palette-search-input" class="visually-hidden">Search</label>
			<input
				id="palette-search-input"
				ref="input"
				v-model="query"
				class="search__input"
				type="text"
				autocomplete="off"
				spellcheck="false"
				placeholder="Search symbols, tasks, errors…"
				role="combobox"
				aria-autocomplete="list"
				aria-controls="palette-search-results"
				:aria-activedescendant="activeId"
				:aria-expanded="flat.length > 0"
			/>
			<span class="palette__scope">scoped to {{ versionLabel }}<template v-if="context.languageLabel"> · {{ context.languageLabel }}</template></span>
		</div>

		<div class="search__chips" role="group" aria-label="Filter results">
			<button
				v-for="chip in CHIPS"
				:key="chip.key"
				type="button"
				class="search__chip"
				:class="{ 'is-active': chip.key === activeChip }"
				:aria-pressed="chip.key === activeChip"
				@click="setChip(chip.key)"
			>
				{{ chip.label }}
			</button>
		</div>

		<div id="palette-search-results" class="search__results" role="listbox" aria-label="Results">
			<p v-if="loading" class="search__status">Building the {{ versionLabel }} index…</p>
			<p v-else-if="error" class="search__status">Search is unavailable: {{ error }}</p>
			<p v-else-if="flat.length === 0" class="search__status">No matches for “{{ query }}” at {{ versionLabel }}.</p>

			<template v-for="group in groups" :key="group.key">
				<div class="search__group-label">{{ group.label }}</div>
				<component
					:is="item.route ? 'a' : 'div'"
					v-for="item in group.items"
					:id="idFor(item)"
					:key="item.id"
					:href="item.route ?? undefined"
					class="search__item"
					:class="{ 'is-active': item.id === activeDoc?.id, 'is-removed': item.record.state === 'removed' }"
					role="option"
					:aria-selected="item.id === activeDoc?.id"
					@click.prevent="open(item)"
					@mousemove="activeIndex = indexOf(item)"
				>
					<template v-if="item.type === 'symbol'">
						<span class="search__item-title mono" :class="{ removed: item.record.state === 'removed' }">{{ item.title }}</span>
						<span v-if="removedFor(item)" class="search__item-meta">
							{{ removedFor(item)!.label }}
							<template v-if="removedFor(item)!.route"> · <RouterLink :to="removedFor(item)!.route!" @click.stop="close">what replaced it</RouterLink></template>
						</span>
						<span v-else class="search__item-meta mono">{{ describeSymbol(item) }}</span>
					</template>
					<template v-else-if="item.type === 'page'">
						<span class="search__item-title">{{ item.title }}</span>
						<span class="search__item-meta">{{ modeLabel(item.mode) }}<template v-if="item.record.minutes"> · {{ item.record.minutes }} min</template></span>
					</template>
					<template v-else>
						<span class="search__item-title">{{ item.record.versionLabel }} — {{ item.title }}</span>
						<span class="search__item-meta" :class="{ 'is-warn': affectsFor(item) }">{{ affectsFor(item) ?? item.kind }}</span>
					</template>
					<span v-if="item.id === activeDoc?.id" class="search__enter mono" aria-hidden="true">↵</span>
				</component>
			</template>
		</div>

		<div class="palette__footer">
			<span>↵ open</span>
			<span>⇥ next mode</span>
			<span>⌘↵ open in contract pane</span>
			<span class="is-right">⌘. change context</span>
		</div>
	</PaletteDialog>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, shallowRef, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";

import SearchIcon from "components/icons/SearchIcon.vue";
import PaletteDialog from "components/palette/PaletteDialog.vue";
import { useCurrentVersion } from "composables/useCurrentVersion";
import { CHIPS, affectsReader, describeSymbol, flattenGroups, removedNote, runSearch } from "search/index";
import type { ResultGroup, SearchChip, SearchDoc } from "search/index";
import { getVersionIndex } from "search/loader";
import type { VersionIndex } from "search/loader";
import { useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";
import { useTrailStore } from "stores/trail";
import { useUiStore } from "stores/ui";
import type { Mode } from "types/docs";

const ui = useUiStore();
const site = useSiteStore();
const context = useContextStore();
const trail = useTrailStore();
const route = useRoute();
const router = useRouter();
const { label } = useCurrentVersion();

const versionLabel = computed(() => label.value ?? site.latest ?? "latest");
const input = ref<HTMLInputElement | null>(null);
const query = ref("");
const activeChip = ref<SearchChip>("all");
const activeIndex = ref(0);
const loading = ref(false);
const error = ref<string | null>(null);
const versionIndex = shallowRef<VersionIndex | null>(null);

const groups = computed<ResultGroup[]>(() => (versionIndex.value ? runSearch(versionIndex.value.index, versionIndex.value.docs, query.value, activeChip.value) : []));
const flat = computed(() => flattenGroups(groups.value));
const activeDoc = computed<SearchDoc | null>(() => flat.value[Math.min(activeIndex.value, flat.value.length - 1)] ?? null);
const activeId = computed(() => (activeDoc.value ? idFor(activeDoc.value) : undefined));

watch(
	() => ui.searchOpen,
	async (open) => {
		if (!open) {
			return;
		}

		query.value = "";
		activeChip.value = "all";
		activeIndex.value = 0;
		trail.hydrate();

		await loadIndex();
		await nextTick();
		input.value?.focus();
	},
);

watch([query, activeChip], () => {
	activeIndex.value = 0;
});

async function loadIndex(): Promise<void> {
	loading.value = true;
	error.value = null;

	try {
		versionIndex.value = await getVersionIndex(versionLabel.value);
	} catch (err) {
		error.value = err instanceof Error ? err.message : String(err);
	} finally {
		loading.value = false;
	}
}

function idFor(doc: SearchDoc): string {
	return `palette-result-${doc.id.replace(/[^\w-]/g, "_")}`;
}

function indexOf(doc: SearchDoc): number {
	return flat.value.findIndex((d) => d.id === doc.id);
}

function setChip(chip: SearchChip): void {
	activeChip.value = chip;
	input.value?.focus();
}

function cycleChip(direction: 1 | -1): void {
	const idx = CHIPS.findIndex((c) => c.key === activeChip.value);

	activeChip.value = CHIPS[(idx + direction + CHIPS.length) % CHIPS.length].key;
}

function close(): void {
	ui.closePalettes();
}

function onVisible(visible: boolean): void {
	if (!visible) {
		close();
	}
}

function open(doc: SearchDoc, inPane = false): void {
	if (!doc.route) {
		return;
	}

	if (inPane && doc.type === "symbol" && route.name === "reference-symbol" && typeof doc.record.ref === "string") {
		ui.openInPane(doc.record.ref);
		close();

		return;
	}

	close();
	router.push(doc.route);
}

function onKeydown(event: KeyboardEvent): void {
	const inInput = event.target === input.value;

	if (event.key === "Tab" && inInput) {
		event.preventDefault();
		cycleChip(event.shiftKey ? -1 : 1);

		return;
	}

	if (event.key === "ArrowDown" || event.key === "ArrowUp") {
		event.preventDefault();

		if (flat.value.length === 0) {
			return;
		}

		const delta = event.key === "ArrowDown" ? 1 : -1;

		activeIndex.value = (activeIndex.value + delta + flat.value.length) % flat.value.length;
		scrollActiveIntoView();

		return;
	}

	if (event.key === "Enter" && activeDoc.value) {
		event.preventDefault();
		open(activeDoc.value, event.metaKey || event.ctrlKey);

		return;
	}

	if (event.key === "." && (event.metaKey || event.ctrlKey)) {
		event.preventDefault();
		ui.requestContextFocus();
	}
}

function scrollActiveIntoView(): void {
	nextTick(() => {
		const id = activeId.value;

		if (id) {
			document.getElementById(id)?.scrollIntoView?.({ block: "nearest" });
		}
	});
}

const modeLabels: Record<string, string> = { learn: "Learn", do: "Do", reference: "Reference", explain: "Explain" };

function modeLabel(mode: Mode | ""): string {
	return modeLabels[mode] ?? mode;
}

function removedFor(doc: SearchDoc) {
	return removedNote(doc, site.versions);
}

function affectsFor(doc: SearchDoc): string | null {
	return affectsReader(doc, site.versions, trail.hasVersionOlderThan);
}
</script>

<style>
.search__head {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px 20px;
	border-bottom: 1px solid var(--color-rule-soft);
}

.search__icon {
	color: var(--color-muted);
	flex-shrink: 0;
}

.search__input {
	flex: 1 1 auto;
	min-width: 0;
	border: 0;
	outline: none;
	background: transparent;
	font-family: var(--font-mono);
	font-size: 18px;
	color: var(--color-ink);
}

.search__input::placeholder {
	color: var(--color-faint);
}

.search__chips {
	display: flex;
	gap: 6px;
	padding: 10px 20px;
	border-bottom: 1px solid var(--color-rule-soft);
	overflow-x: auto;
}

.search__chip {
	height: 30px;
	padding: 0 12px;
	border-radius: var(--radius-pill);
	border: 1px solid var(--color-control-border);
	background: var(--color-surface);
	color: var(--color-ink3);
	font-size: var(--text-meta);
	white-space: nowrap;
}

.search__chip.is-active {
	border-color: var(--color-ink);
	background: var(--color-ink);
	color: var(--color-surface);
}

.search__results {
	flex: 1 1 auto;
	overflow-y: auto;
	padding: 8px 0;
	display: flex;
	flex-direction: column;
}

.search__status {
	padding: 14px 20px;
	color: var(--color-muted);
	font-size: var(--text-small);
}

.search__group-label {
	padding: 10px 20px 6px;
	font-size: 11px;
	font-weight: 500;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: var(--color-muted);
}

.search__item {
	display: flex;
	align-items: center;
	gap: 12px;
	min-height: 40px;
	padding: 8px 20px;
	color: var(--color-ink);
	text-decoration: none;
	cursor: pointer;
}

.search__item:hover {
	text-decoration: none;
	background: var(--color-surface-sunk);
}

.search__item.is-active {
	background: var(--color-accent-tint);
}

.search__item.is-removed {
	color: var(--color-faint);
}

.search__item-title {
	flex: 1 1 auto;
	font-size: var(--text-small);
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.search__item.is-active .search__item-title {
	font-weight: 600;
}

.search__item-meta {
	font-size: var(--text-label);
	color: var(--color-muted);
	white-space: nowrap;
}

.search__item-meta.is-warn {
	color: var(--color-warn-text);
}

.search__enter {
	font-size: var(--text-label);
	color: var(--color-accent-strong);
}

.mono {
	font-family: var(--font-mono);
}
</style>
