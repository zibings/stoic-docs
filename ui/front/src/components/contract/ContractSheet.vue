<template>
	<div class="sheet pane" :class="{ 'sheet--peek': !open }">
		<button type="button" class="sheet__handle" :aria-label="open ? 'Collapse contract' : 'Expand contract'" :aria-expanded="open" @click="open = true">
			<span class="sheet__grip" />
		</button>
		<div v-if="!open" class="sheet__peek" @click="open = true">
			<div class="sheet__peek-head">
				<span class="contract__label">Contract</span>
				<span class="contract__label">{{ peekRow ? "following" : "" }}</span>
			</div>
			<div v-if="peekRow" class="sheet__peek-row">
				<span class="sheet__peek-name">{{ peekRow.label }}</span>
				<span class="sheet__peek-type">{{ peekRow.required ? `${peekRow.type} · required` : peekRow.default ? `${peekRow.type} = ${peekRow.default}` : peekRow.type }}</span>
			</div>
			<div v-if="peekRow?.description" class="sheet__peek-desc">{{ peekRow.description }}</div>
			<div v-else-if="!peekRow" class="sheet__peek-desc">{{ node.shortName }} · tap to open the contract</div>
		</div>

		<Drawer v-model:visible="open" position="bottom" :modal="true" :block-scroll="true" :show-close-icon="false" :pt="pt" :unstyled="true" append-to="body">
			<template #container="{ closeCallback }">
				<div class="sheet__panel pane" role="dialog" aria-label="Contract">
					<span class="sheet__grip sheet__grip--panel" aria-hidden="true" />
					<div class="sheet__panel-head">
						<span class="contract__label">Contract</span>
						<button type="button" class="icon-button sheet__close" aria-label="Close contract" @click="closeCallback">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
						</button>
					</div>
					<div class="sheet__panel-body">
						<ContractPane :node="node" :related-types="relatedTypes" :active-ref="activeRef" :version-label="versionLabel" :language="language" :tested-samples="testedSamples" compact />
					</div>
				</div>
			</template>
		</Drawer>
	</div>
</template>

<script setup lang="ts">
import Drawer from "primevue/drawer";
import { computed, ref } from "vue";

import ContractPane from "components/contract/ContractPane.vue";
import { flattenContract } from "contract/flatten";
import type { SymbolNode } from "types/docs";

const props = withDefaults(
	defineProps<{
		node: SymbolNode;
		relatedTypes: Record<string, SymbolNode>;
		activeRef: string | null;
		versionLabel: string;
		language: string | null;
		testedSamples?: { total: number; passingAtVersion: number } | null;
	}>(),
	{ testedSamples: null },
);

const open = ref(false);
const rows = computed(() => flattenContract(props.node, props.relatedTypes));
const peekRow = computed(() => rows.value.find((r) => r.ref === props.activeRef) ?? rows.value[0] ?? null);

const pt = {
	mask: { class: "sheet__mask" },
	root: { class: "sheet__drawer" },
};
</script>
