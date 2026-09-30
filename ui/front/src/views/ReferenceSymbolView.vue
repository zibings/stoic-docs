<template>
	<div v-if="notFound" class="shell-content">
		<p class="muted">
			<span class="mono">{{ modulePath }}#{{ symbol }}</span> does not exist at {{ version }}.
			<RouterLink :to="`/${version}/reference`">Browse the reference</RouterLink>
		</p>
	</div>
	<div v-else-if="error" class="shell-content"><p class="muted">Could not load this symbol: {{ error }}</p></div>
	<div v-else-if="!data" class="shell-content"><p class="muted">Loading…</p></div>

	<div v-else class="symbol-layout">
		<SymbolRail
			class="symbol-layout__rail"
			:trail="trailEntries"
			:current="data.symbol.shortName"
			:module-path="data.module.path"
			:siblings="data.siblings"
			:current-ref="rootRef"
			@browse="ui.openBrowse()"
		/>

		<article class="symbol-layout__main">
			<SymbolHeader :node="data.symbol" :breadcrumb="data.breadcrumb" />

			<ProseBody v-if="data.page" ref="prose" :body="data.page.body" :context="renderContext" />
			<p v-else class="muted">No reference prose for this symbol at {{ version }} yet.</p>

			<section v-if="data.alsoCoveredIn.length > 0" class="also-covered">
				<div class="label">Also covered in</div>
				<ul class="also-covered__list">
					<li v-for="page in data.alsoCoveredIn" :key="page.route">
						<RouterLink :to="page.route">{{ modeLabel(page.mode) }} · {{ page.title }}</RouterLink>
					</li>
				</ul>
			</section>
		</article>

		<aside class="symbol-layout__pane" aria-label="Contract">
			<div v-if="paneOverride" class="pane-override">
				<span class="pane-override__text">showing <span class="mono">{{ paneOverride.symbol.shortName }}</span></span>
				<button type="button" class="pane-override__back" @click="ui.clearPaneOverride()">back to {{ data.symbol.shortName }}</button>
			</div>
			<ContractPane
				:node="paneNode"
				:related-types="paneRelatedTypes"
				:active-ref="paneOverride ? null : activeRef"
				:version-label="version"
				:language="context.effectiveLanguage"
				:tested-samples="paneOverride ? null : data.testedSamples"
			/>
		</aside>

		<ClientOnly>
			<ContractSheet
				class="symbol-layout__sheet"
				:node="paneNode"
				:related-types="paneRelatedTypes"
				:active-ref="paneOverride ? null : activeRef"
				:version-label="version"
				:language="context.effectiveLanguage"
				:tested-samples="paneOverride ? null : data.testedSamples"
			/>
		</ClientOnly>
	</div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { RouterLink } from "vue-router";

import { docsApi } from "api/docs";
import ClientOnly from "components/ClientOnly.vue";
import ContractPane from "components/contract/ContractPane.vue";
import ContractSheet from "components/contract/ContractSheet.vue";
import ProseBody from "components/prose/ProseBody.vue";
import SymbolHeader from "components/reference/SymbolHeader.vue";
import SymbolRail from "components/reference/SymbolRail.vue";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import { useProseContext } from "composables/useProseContext";
import { useSymbolFollow } from "composables/useSymbolFollow";
import { useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";
import { useTrailStore } from "stores/trail";
import { useUiStore } from "stores/ui";
import type { PageMode, SymbolNode, SymbolResponse } from "types/docs";

const props = defineProps<{ version: string; module: string[] | string; symbol: string }>();

const site = useSiteStore();
const context = useContextStore();
const trail = useTrailStore();
const ui = useUiStore();

const modulePath = computed(() => (Array.isArray(props.module) ? props.module.join("/") : props.module));
const key = computed(() => `symbol:${props.version}:${modulePath.value}#${props.symbol}`);
const { data, error, notFound } = useDocsData(key, () => docsApi.symbol(props.version, modulePath.value, props.symbol));

// The rail lists module-level siblings; for a nested symbol the "current" sibling is its top-level ancestor.
const rootRef = computed(() => {
	const ref = data.value?.symbol.ref ?? "";
	const hash = ref.indexOf("#");
	const dot = ref.indexOf(".", hash);

	return dot > 0 ? ref.slice(0, dot) : ref;
});

const versionLabel = computed(() => props.version);
const knownSymbols = computed<Iterable<SymbolNode | null | undefined>>(() => {
	if (!data.value) {
		return [];
	}

	return [data.value.symbol, ...Object.values(data.value.relatedTypes), ...data.value.mentions, ...data.value.siblings];
});
const samples = computed(() => data.value?.samples ?? []);
const renderContext = useProseContext(versionLabel, knownSymbols, samples);

const prose = ref<InstanceType<typeof ProseBody> | null>(null);
const proseRoot = computed(() => prose.value?.root ?? null);
const proseHtml = computed(() => prose.value?.html ?? "");
const { activeRef } = useSymbolFollow(proseRoot, proseHtml);

const trailEntries = computed(() => trail.recent(data.value?.symbol.route ?? null, 3));

usePageMeta(
	computed(() => (data.value ? `${data.value.symbol.shortName} · ${data.value.module.path} · ${props.version}` : notFound.value ? `Not found · ${props.version}` : null)),
	computed(() => data.value?.symbol.contract.summary || null),
);

// ⌘↵ from a palette shows another symbol's contract in the pane while this page stays put.
const paneOverride = ref<SymbolResponse | null>(null);

watch(
	() => ui.paneOverrideRef,
	async (overrideRef) => {
		paneOverride.value = null;

		if (!overrideRef || !overrideRef.includes("#")) {
			return;
		}

		const modulePathOf = overrideRef.slice(0, overrideRef.indexOf("#"));
		const nameOf = overrideRef.slice(overrideRef.indexOf("#") + 1);

		if (data.value && overrideRef === data.value.symbol.ref) {
			ui.clearPaneOverride();

			return;
		}

		try {
			const response = await docsApi.symbol(props.version, modulePathOf, nameOf);

			if (ui.paneOverrideRef === overrideRef) {
				paneOverride.value = response;
			}
		} catch {
			ui.clearPaneOverride();
		}
	},
	{ immediate: true },
);

watch(key, () => ui.clearPaneOverride());

const paneNode = computed<SymbolNode>(() => paneOverride.value?.symbol ?? data.value!.symbol);
const paneRelatedTypes = computed(() => paneOverride.value?.relatedTypes ?? data.value!.relatedTypes);

watch(
	data,
	(value) => {
		if (!value || typeof window === "undefined") {
			return;
		}

		trail.record({
			route: value.symbol.route,
			title: value.symbol.shortName,
			mode: "reference",
			versionLabel: value.version.label,
			versionSortKey: value.version.sortKey,
		});
	},
	{ immediate: true },
);

const modeLabels: Record<PageMode, string> = { learn: "Learn", do: "Do", reference: "Reference", explain: "Explain", home: "Home" };

function modeLabel(mode: PageMode): string {
	return modeLabels[mode] ?? mode;
}

void site;
</script>

<style>
.pane-override {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	padding: 10px 28px;
	background: var(--pane-code);
	color: var(--pane-text-soft);
	font-family: var(--font-mono);
	font-size: var(--text-label);
}

.pane-override__back {
	color: var(--pane-link);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	min-height: 28px;
}

.symbol-layout {
	flex: 1 1 auto;
	display: grid;
	grid-template-columns: var(--rail-width) minmax(0, 1fr) var(--pane-width);
	min-height: 0;
}

.symbol-layout__main {
	min-width: 0;
	padding: 36px var(--page-pad-x) 48px;
	display: flex;
	flex-direction: column;
	gap: 28px;
}

.symbol-layout__pane {
	position: sticky;
	top: var(--header-height);
	height: calc(100vh - var(--header-height));
	overflow-y: auto;
}

.symbol-layout__sheet {
	display: none;
}

.also-covered {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding-top: 18px;
	border-top: 1px solid var(--color-rule);
}

.also-covered__list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 4px;
	font-size: var(--text-small);
}

.muted {
	color: var(--color-muted);
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 1180px) {
	.symbol-layout {
		grid-template-columns: minmax(0, 1fr);
	}

	.symbol-layout__rail,
	.symbol-layout__pane {
		display: none;
	}

	.symbol-layout__main {
		padding-bottom: 170px;
	}

	.symbol-layout__sheet {
		display: flex;
	}
}

@media (max-width: 768px) {
	.symbol-layout__main {
		padding: 20px 18px 170px;
		gap: 18px;
	}
}
</style>
