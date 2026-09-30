<template>
	<PaletteDialog :visible="ui.browseOpen" label="Browse all" size="browse" @update:visible="onVisible" @keydown="onKeydown">
		<div class="browse__head">
			<div class="browse__tabs" role="tablist" aria-label="Modes">
				<button
					v-for="mode in site.modes"
					:key="mode"
					type="button"
					role="tab"
					class="browse__tab"
					:class="{ 'is-active': mode === activeMode }"
					:aria-selected="mode === activeMode"
					@click="setMode(mode)"
				>
					{{ modeLabels[mode] }}
				</button>
			</div>
			<span class="palette__scope">{{ versionLabel }}<template v-if="context.languageLabel"> · {{ context.languageLabel }}</template></span>
		</div>

		<div class="browse__body">
			<div class="browse__left">
				<div class="browse__filter">
					<label for="palette-browse-filter" class="browse__filter-label mono">filter</label>
					<input id="palette-browse-filter" ref="filterInput" v-model="filter" class="browse__filter-input" type="text" autocomplete="off" spellcheck="false" placeholder="type to narrow the tree" />
					<label v-if="activeMode === 'reference'" class="browse__removed">
						<input v-model="includeRemoved" type="checkbox" />
						removed
					</label>
				</div>

				<div ref="treeHost" class="browse__tree-host" @focusin="onTreeFocus">
					<p v-if="loading" class="browse__status">Loading {{ versionLabel }}…</p>
					<p v-else-if="error" class="browse__status">Could not load: {{ error }}</p>
					<p v-else-if="nodes.length === 0" class="browse__status">Nothing matches “{{ filter }}”.</p>
					<Tree
						v-else
						:value="nodes"
						v-model:expanded-keys="expandedKeys"
						:selection-keys="selectionKeys"
						selection-mode="single"
						:meta-key-selection="false"
						:pt="treePt"
						:unstyled="true"
						@node-select="onNodeSelect"
					>
						<template #default="{ node }">
							<span class="browse__row" :data-node-key="node.key" :class="{ 'is-current': node.data.current, 'is-removed': node.data.state === 'removed' }">
								<span v-if="node.data.kind === 'symbol'" class="browse__kind mono">{{ node.data.symbolKind }}</span>
								<span class="browse__label" :class="{ mono: node.data.kind !== 'page', removed: node.data.state === 'removed' }">{{ node.label }}</span>
								<span v-if="node.data.current" class="browse__tag">you are here</span>
								<span v-else-if="node.data.state === 'removed'" class="browse__tag is-warn">removed {{ node.data.removedLabel }}</span>
								<span v-else-if="node.data.kind === 'symbol' && isRecent(node.data.sinceLabel)" class="browse__tag is-accent">new in {{ node.data.sinceLabel }}</span>
								<span v-else-if="node.data.count !== undefined && node.data.kind !== 'symbol'" class="browse__count">{{ node.data.count }}</span>
								<span v-else-if="node.data.minutes" class="browse__count">{{ node.data.minutes }} min</span>
							</span>
						</template>
					</Tree>
				</div>
			</div>

			<div class="browse__preview" aria-live="polite">
				<template v-if="focused && focused.data.kind === 'symbol' && focused.data.symbol">
					<div class="browse__crumb mono">{{ focused.data.modulePath?.split("/").join(" / ") }} / {{ focused.data.symbol.name.split(".").join(" / ") }}</div>
					<div class="browse__preview-title mono" :class="{ removed: focused.data.state === 'removed' }">{{ focused.data.symbol.shortName }}</div>
					<p v-if="focused.data.symbol.contract.summary" class="browse__preview-summary">{{ focused.data.symbol.contract.summary }}</p>
					<pre class="browse__signature pane"><code class="hljs" v-html="highlight(focused.data.symbol.contract.signature, context.effectiveLanguage)" /></pre>
					<div v-if="focusedSymbolPages.length > 0" class="browse__also">
						<div class="label">Also covered in</div>
						<RouterLink v-for="page in focusedSymbolPages" :key="page.route" :to="page.route" @click="close">{{ modeLabels[page.mode] }} · {{ page.title }}</RouterLink>
					</div>
				</template>
				<template v-else-if="focused && focused.data.kind === 'page'">
					<div class="browse__crumb mono">{{ modeLabels[activeMode] }}</div>
					<div class="browse__preview-title">{{ focused.label }}</div>
					<p v-if="focused.data.summary" class="browse__preview-summary">{{ focused.data.summary }}</p>
					<p v-if="focused.data.minutes" class="browse__crumb mono">{{ focused.data.minutes }} min</p>
				</template>
				<template v-else-if="focused">
					<div class="browse__crumb mono">{{ focused.data.kind }}</div>
					<div class="browse__preview-title mono">{{ focused.label }}</div>
					<p v-if="focused.data.summary" class="browse__preview-summary">{{ focused.data.summary }}</p>
					<p class="browse__crumb mono">{{ focused.data.count }} items</p>
				</template>
				<p v-else class="browse__status">Move through the tree to preview an item.</p>
			</div>
		</div>

		<div class="palette__footer">
			<span>↑↓ move</span>
			<span>→ ← expand / collapse</span>
			<span>↵ open</span>
			<span>⌘↵ open in contract pane</span>
			<span class="is-right">⇥ next mode</span>
		</div>
	</PaletteDialog>
</template>

<script setup lang="ts">
import Tree from "primevue/tree";
import type { TreeNode } from "primevue/treenode";
import { computed, nextTick, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";

import { docsApi } from "api/docs";
import { buildPagesTree, buildReferenceTree, defaultExpandedKeys, flattenNodes } from "browse/tree";
import type { BrowseNode } from "browse/tree";
import PaletteDialog from "components/palette/PaletteDialog.vue";
import { useCurrentVersion } from "composables/useCurrentVersion";
import { highlight } from "markdown/highlight";
import type { SearchRecord } from "search/index";
import { useCacheStore } from "stores/cache";
import { useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";
import { useUiStore } from "stores/ui";
import type { Manifest, Mode, PageMode, PageSummary, SymbolResponse, TreeResponse } from "types/docs";

const ui = useUiStore();
const site = useSiteStore();
const context = useContextStore();
const cache = useCacheStore();
const route = useRoute();
const router = useRouter();
const { label } = useCurrentVersion();

const modeLabels: Record<PageMode, string> = { learn: "Learn", do: "Do", reference: "Reference", explain: "Explain", home: "Home" };

const versionLabel = computed(() => label.value ?? site.latest ?? "latest");
const activeMode = ref<Mode>("reference");
const filter = ref("");
const includeRemoved = ref(true);
const loading = ref(false);
const error = ref<string | null>(null);
const filterInput = ref<HTMLInputElement | null>(null);
const treeHost = ref<HTMLElement | null>(null);
const focusedKey = ref<string | null>(null);
const expandedKeys = ref<Record<string, boolean>>({});

const treeData = computed(() => cache.entries[`tree:${versionLabel.value}`] as TreeResponse | undefined);
const manifestData = computed(() => cache.entries[`manifest:${versionLabel.value}`] as Manifest | undefined);

const nodes = computed<BrowseNode[]>(() => {
	if (activeMode.value === "reference") {
		return treeData.value
			? buildReferenceTree(treeData.value, { includeRemoved: includeRemoved.value, filter: filter.value, currentRoute: route.path, versionLabel: versionLabel.value })
			: [];
	}

	return manifestData.value ? buildPagesTree(manifestData.value.searchRecords as SearchRecord[], activeMode.value, filter.value, route.path) : [];
});

const flat = computed(() => flattenNodes(nodes.value));
const focused = computed<BrowseNode | null>(() => flat.value.find((n) => n.key === focusedKey.value) ?? null);
const selectionKeys = computed<Record<string, boolean>>(() => (focusedKey.value ? { [focusedKey.value]: true } : {}));

// "new in vX" when the symbol arrived in the viewed version or the one before it.
const recentLabels = computed(() => {
	const sorted = [...site.versions].sort((a, b) => a.sortKey - b.sortKey);
	const idx = sorted.findIndex((v) => v.label === versionLabel.value);

	return new Set(sorted.slice(Math.max(0, idx - 1), idx + 1).map((v) => v.label));
});

function isRecent(sinceLabel: string | null | undefined): boolean {
	return Boolean(sinceLabel && recentLabels.value.has(sinceLabel));
}

const treePt = {
	root: { class: "browse-tree" },
	wrapper: { class: "browse-tree__wrapper" },
	rootChildren: { class: "browse-tree__list" },
	node: ({ context: ctx }: { context: { selected?: boolean; expanded?: boolean; leaf?: boolean } }) => ({
		class: ["browse-tree__node", { "is-selected": ctx.selected, "is-expanded": ctx.expanded, "is-leaf": ctx.leaf }],
	}),
	nodeContent: { class: "browse-tree__content" },
	nodeToggleButton: { class: "browse-tree__toggle" },
	nodeToggleIcon: { class: "browse-tree__toggle-icon" },
	nodeLabel: { class: "browse-tree__label" },
	nodeChildren: { class: "browse-tree__children" },
};

watch(
	() => ui.browseOpen,
	async (open) => {
		if (!open) {
			return;
		}

		filter.value = "";
		focusedKey.value = null;
		activeMode.value = ui.browseMode ?? route.meta.mode ?? "reference";
		ui.browseMode = null;

		await load();
		expandedKeys.value = defaultExpandedKeys(nodes.value, false);
		focusedKey.value = flat.value.find((n) => n.data.current)?.key ?? flat.value.find((n) => n.data.kind !== "module" && n.data.kind !== "course")?.key ?? null;

		await nextTick();
		filterInput.value?.focus();
	},
);

watch(filter, (value) => {
	expandedKeys.value = value.trim() ? defaultExpandedKeys(nodes.value, true) : defaultExpandedKeys(nodes.value, false);
});

watch(activeMode, async () => {
	filter.value = "";
	focusedKey.value = null;

	await load();
	expandedKeys.value = defaultExpandedKeys(nodes.value, false);
});

async function load(): Promise<void> {
	loading.value = true;
	error.value = null;

	try {
		if (activeMode.value === "reference") {
			if (!treeData.value) {
				cache.set(`tree:${versionLabel.value}`, await docsApi.tree(versionLabel.value, true));
			}
		} else if (!manifestData.value) {
			cache.set(`manifest:${versionLabel.value}`, await docsApi.manifest(versionLabel.value));
		}
	} catch (err) {
		error.value = err instanceof Error ? err.message : String(err);
	} finally {
		loading.value = false;
	}
}

// "Also covered in" for the focused symbol, fetched lazily and cached under the same key the symbol view uses.
const focusedSymbolPages = ref<PageSummary[]>([]);
let previewTimer: number | null = null;

watch(focused, (node) => {
	focusedSymbolPages.value = [];

	if (previewTimer !== null) {
		window.clearTimeout(previewTimer);
		previewTimer = null;
	}

	if (!node || node.data.kind !== "symbol" || !node.data.ref || !node.data.modulePath) {
		return;
	}

	const modulePath = node.data.modulePath;
	const name = node.data.ref.slice(node.data.ref.indexOf("#") + 1);
	const key = `symbol:${versionLabel.value}:${modulePath}#${name}`;
	const cached = cache.entries[key] as SymbolResponse | undefined;

	if (cached) {
		focusedSymbolPages.value = cached.alsoCoveredIn;

		return;
	}

	previewTimer = window.setTimeout(async () => {
		try {
			const response = await docsApi.symbol(versionLabel.value, modulePath, name);

			cache.set(key, response);

			if (focused.value?.key === node.key) {
				focusedSymbolPages.value = response.alsoCoveredIn;
			}
		} catch {
			// Preview extras are optional.
		}
	}, 150);
});

function setMode(mode: Mode): void {
	activeMode.value = mode;
}

function cycleMode(direction: 1 | -1): void {
	const modes = site.modes;
	const idx = modes.indexOf(activeMode.value);

	activeMode.value = modes[(idx + direction + modes.length) % modes.length];
}

function onTreeFocus(event: FocusEvent): void {
	const item = (event.target as HTMLElement).closest<HTMLElement>('[role="treeitem"]');
	const key = item?.querySelector<HTMLElement>("[data-node-key]")?.dataset.nodeKey;

	if (key) {
		focusedKey.value = key;
	}
}

function onNodeSelect(node: TreeNode): void {
	const browseNode = node as BrowseNode;

	focusedKey.value = browseNode.key;

	if (browseNode.data.route) {
		open(browseNode);
	} else {
		expandedKeys.value = { ...expandedKeys.value, [browseNode.key]: !expandedKeys.value[browseNode.key] };
	}
}

function close(): void {
	ui.closePalettes();
}

function onVisible(visible: boolean): void {
	if (!visible) {
		close();
	}
}

function open(node: BrowseNode, inPane = false): void {
	if (!node.data.route) {
		return;
	}

	if (inPane && node.data.kind === "symbol" && node.data.ref && route.name === "reference-symbol") {
		ui.openInPane(node.data.ref);
		close();

		return;
	}

	close();
	router.push(node.data.route);
}

function onKeydown(event: KeyboardEvent): void {
	const target = event.target as HTMLElement;
	const inFilter = target === filterInput.value;
	const inTree = Boolean(treeHost.value?.contains(target));

	if (event.key === "Tab" && (inFilter || inTree)) {
		event.preventDefault();
		event.stopPropagation();
		cycleMode(event.shiftKey ? -1 : 1);

		return;
	}

	if (event.key === "Enter" && (event.metaKey || event.ctrlKey) && focused.value) {
		event.preventDefault();
		event.stopPropagation();
		open(focused.value, true);

		return;
	}

	if (event.key === "ArrowDown" && inFilter) {
		event.preventDefault();
		treeHost.value?.querySelector<HTMLElement>('[role="treeitem"]')?.focus();

		return;
	}

	if (event.key === "Enter" && inFilter && focused.value) {
		event.preventDefault();
		open(focused.value);

		return;
	}

	if (event.key === "." && (event.metaKey || event.ctrlKey)) {
		event.preventDefault();
		ui.requestContextFocus();
	}
}
</script>

<style>
.browse__head {
	display: flex;
	align-items: center;
	gap: 16px;
	padding: 12px 20px 0;
	border-bottom: 1px solid var(--color-rule-soft);
}

.browse__tabs {
	display: flex;
	gap: 4px;
}

.browse__tab {
	height: 32px;
	padding: 0 12px;
	border-bottom: 2px solid transparent;
	color: var(--color-muted);
	font-size: var(--text-small);
}

.browse__tab.is-active {
	border-bottom-color: var(--color-ink);
	color: var(--color-ink);
	font-weight: 600;
}

.browse__head .palette__scope {
	margin-left: auto;
	padding-bottom: 8px;
}

.browse__body {
	flex: 1 1 auto;
	display: flex;
	min-height: 0;
}

.browse__left {
	width: 520px;
	flex-shrink: 0;
	display: flex;
	flex-direction: column;
	border-right: 1px solid var(--color-rule-soft);
	min-height: 0;
}

.browse__filter {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px 20px;
	border-bottom: 1px solid var(--color-rule-soft);
}

.browse__filter-label {
	font-size: var(--text-label);
	color: var(--color-muted);
}

.browse__filter-input {
	flex: 1 1 auto;
	min-width: 0;
	height: 32px;
	padding: 0 10px;
	border: 1px solid var(--color-control-border);
	border-radius: var(--radius-control);
	background: var(--color-surface-sunk);
	font-family: var(--font-mono);
	font-size: var(--text-meta);
	color: var(--color-ink);
}

.browse__removed {
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: var(--text-label);
	color: var(--color-ink3);
	white-space: nowrap;
}

.browse__tree-host {
	flex: 1 1 auto;
	overflow-y: auto;
	padding: 8px 0;
}

.browse__status {
	padding: 14px 20px;
	color: var(--color-muted);
	font-size: var(--text-small);
}

/* PrimeVue Tree, unstyled */
.browse-tree__list,
.browse-tree__children {
	list-style: none;
	margin: 0;
	padding: 0;
}

.browse-tree__node {
	outline: none;
}

.browse-tree__content {
	display: flex;
	align-items: center;
	gap: 8px;
	min-height: 34px;
	padding: 0 20px;
	cursor: pointer;
}

.browse-tree__children .browse-tree__content {
	padding-left: 42px;
	min-height: 32px;
}

.browse-tree__children .browse-tree__children .browse-tree__content {
	padding-left: 64px;
}

.browse-tree__node:focus > .browse-tree__content,
.browse-tree__node:focus-visible > .browse-tree__content {
	background: var(--color-accent-tint);
	box-shadow: inset 2px 0 0 var(--color-accent);
}

.browse-tree__node.is-selected > .browse-tree__content {
	background: var(--color-chip);
}

.browse-tree__toggle {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 20px;
	height: 20px;
	color: var(--color-ink);
	visibility: visible;
}

.browse-tree__node.is-leaf > .browse-tree__content > .browse-tree__toggle {
	visibility: hidden;
}

.browse-tree__toggle-icon {
	width: 14px;
	height: 14px;
}

.browse-tree__label {
	flex: 1 1 auto;
	min-width: 0;
}

.browse__row {
	display: flex;
	align-items: center;
	gap: 10px;
	min-width: 0;
	font-size: var(--text-small);
	color: var(--color-ink);
}

.browse__row.is-removed {
	color: var(--color-faint);
}

.browse__kind {
	width: 34px;
	flex-shrink: 0;
	font-size: 11px;
	color: var(--color-muted);
}

.browse__label {
	flex: 1 1 auto;
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.browse__label.mono {
	font-size: var(--text-meta);
}

.browse-tree__list > .browse-tree__node > .browse-tree__content .browse__label {
	font-weight: 600;
	font-size: var(--text-small);
}

.browse__row.is-current .browse__label {
	font-weight: 600;
}

.browse__tag,
.browse__count {
	font-size: var(--text-label);
	color: var(--color-muted);
	white-space: nowrap;
}

.browse__tag.is-warn {
	color: var(--color-warn-text);
}

.browse__tag.is-accent {
	color: var(--color-accent-strong);
}

.browse__preview {
	flex: 1 1 auto;
	min-width: 0;
	padding: 24px 28px;
	display: flex;
	flex-direction: column;
	gap: 16px;
	background: var(--color-surface-sunk);
	overflow-y: auto;
}

.browse__crumb {
	font-size: var(--text-label);
	color: var(--color-muted);
}

.browse__preview-title {
	font-size: 26px;
	font-weight: 600;
	letter-spacing: -0.02em;
	overflow-wrap: anywhere;
}

.browse__preview-summary {
	font-family: var(--font-serif);
	font-size: 19px;
	line-height: 1.4;
	color: var(--color-ink2);
}

.browse__signature {
	padding: 14px 16px;
	border-radius: var(--radius-card);
	background: var(--pane-bg);
	color: var(--pane-text);
	font-size: var(--text-meta);
	line-height: 1.7;
	overflow-x: auto;
}

.browse__also {
	display: flex;
	flex-direction: column;
	gap: 6px;
	font-size: var(--text-small);
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 900px) {
	.browse__head .palette__scope {
		display: none;
	}
}

@media (max-width: 768px) {
	.browse__left {
		width: 100%;
		border-right: 0;
	}

	.browse__preview {
		display: none;
	}

	.browse__head {
		overflow-x: auto;
	}
}
</style>
