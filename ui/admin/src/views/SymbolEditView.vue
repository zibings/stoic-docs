<template>
	<div class="page">
		<div class="page__head">
			<div>
				<p class="label"><RouterLink to="/modules">Modules</RouterLink> / <RouterLink v-if="symbol" :to="{ name: 'module', params: { id: symbol.moduleId } }">{{ catalog.modulePath(symbol.moduleId) }}</RouterLink></p>
				<h1 class="page__title mono">{{ symbol?.ref.slice(symbol.ref.indexOf("#") + 1) ?? "…" }}</h1>
				<p v-if="symbol" class="page__subtitle"><Tag :value="symbol.kind" severity="secondary" /> <span class="mono">{{ symbol.ref }}</span></p>
			</div>
			<a v-if="siteLink" :href="siteLink" target="_blank" rel="noopener">View on site ↗</a>
		</div>

		<ResourceTable title="Contracts" subtitle="One row per version range. A contract is valid from its introduced version until (not including) its removed version." :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New contract" sort-field="introducedVersionId" :searchable="false" @create="res.startCreate({ symbolId })" @edit="res.startEdit($event)" @delete="res.remove($event, (c) => `the contract from ${catalog.versionLabel(c.introducedVersionId)}`)">
			<template #cell-signature="{ row }">
				<code class="mono">{{ row.signature.split("\n")[0] }}</code>
			</template>
			<template #cell-params="{ row }">
				<span class="mono">{{ row.params.length }}</span>
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New contract' : 'Edit contract'" :saving="res.saving.value" :errors="res.errors.value" width="980px" @save="res.save()">
			<FormField v-slot="{ id }" label="Introduced in" :span="4" required :error="res.errors.value.introducedVersionId">
				<VersionSelect :model-value="(res.editing.value.introducedVersionId as number | null)" :input-id="id" @update:model-value="res.editing.value.introducedVersionId = $event" />
			</FormField>
			<FormField v-slot="{ id }" label="Removed / replaced in" :span="4" hint="Empty while current" :error="res.errors.value.removedVersionId">
				<VersionSelect :model-value="(res.editing.value.removedVersionId as number | null)" :input-id="id" clearable placeholder="still current" @update:model-value="res.editing.value.removedVersionId = $event" />
			</FormField>
			<FormField v-slot="{ id }" label="Status" :span="4">
				<Select :id="id" v-model="(res.editing.value.status as string)" :options="STATUSES" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Signature" required hint="Shown in the contract pane; multi-line is fine" :error="res.errors.value.signature">
				<Textarea :id="id" v-model="(res.editing.value.signature as string)" rows="4" auto-resize fluid class="mono" spellcheck="false" />
			</FormField>
			<FormField v-slot="{ id }" label="Summary" hint="One line under the symbol name">
				<InputText :id="id" v-model="(res.editing.value.summary as string)" fluid />
			</FormField>
			<div class="field">
				<label>Parameters / fields</label>
				<ParamsEditor :model-value="(res.editing.value.params as AdminParam[])" @update:model-value="res.editing.value.params = $event" />
			</div>
			<FormField v-slot="{ id }" label="Returns type" :span="4">
				<InputText :id="id" :model-value="returns.type ?? ''" class="mono" fluid @update:model-value="setReturns('type', $event)" />
			</FormField>
			<FormField v-slot="{ id }" label="Returns description" :span="8">
				<InputText :id="id" :model-value="returns.description ?? ''" fluid @update:model-value="setReturns('description', $event)" />
			</FormField>
			<FormField v-slot="{ id }" label="Throws" hint="One per line: Type — description">
				<Textarea :id="id" :model-value="throwsText" rows="3" auto-resize fluid class="mono" @update:model-value="setThrows($event)" />
			</FormField>
			<FormField v-slot="{ id }" label="Source path" :span="8" hint="Relative to the repository, e.g. src/query/create.ts">
				<InputText :id="id" :model-value="(res.editing.value.sourcePath as string | null) ?? ''" class="mono" fluid @update:model-value="res.editing.value.sourcePath = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="Source line" :span="4">
				<InputNumber :id="id" :model-value="(res.editing.value.sourceLine as number | null)" :use-grouping="false" fluid @update:model-value="res.editing.value.sourceLine = $event ?? null" />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Tag from "primevue/tag";
import Textarea from "primevue/textarea";
import { computed, onMounted } from "vue";
import { RouterLink } from "vue-router";

import FormField from "components/FormField.vue";
import ParamsEditor from "components/ParamsEditor.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import VersionSelect from "components/VersionSelect.vue";
import { useResource } from "composables/useResource";
import { STATUSES } from "lib/forms";
import { useCatalogStore } from "stores/catalog";
import type { AdminParam } from "types/admin";

const props = defineProps<{ id: string }>();

const catalog = useCatalogStore();
const symbolId = computed(() => Number(props.id));
const symbol = computed(() => catalog.symbols.find((s) => s.id === symbolId.value) ?? null);
const res = useResource("contracts", () => ({ symbolId: symbolId.value }));

const columns: ColumnDef[] = [
	{ field: "introducedVersionId", header: "Introduced", kind: "version", width: "12%" },
	{ field: "removedVersionId", header: "Removed", kind: "version", width: "12%" },
	{ field: "status", header: "Status", kind: "tag", width: "12%" },
	{ field: "signature", header: "Signature" },
	{ field: "params", header: "Params", width: "8%", sortable: false },
];

const latest = computed(() => catalog.versions.find((v) => v.isLatest) ?? catalog.versionsNewestFirst[0]);
const siteLink = computed(() => {
	if (!symbol.value || !latest.value || !catalog.siteUrl) {
		return null;
	}

	const ref = symbol.value.ref;

	return `${catalog.siteUrl}/${latest.value.label}/reference/${ref.slice(0, ref.indexOf("#"))}/${ref.slice(ref.indexOf("#") + 1)}`;
});

const returns = computed(() => (res.editing.value.returns as { type?: string; description?: string }) ?? {});

function setReturns(key: "type" | "description", value: string | undefined): void {
	res.editing.value.returns = { ...returns.value, [key]: value ?? "" };
}

const throwsText = computed(() => ((res.editing.value.throws as { type?: string; description?: string }[]) ?? []).map((t) => (t.description ? `${t.type} — ${t.description}` : t.type ?? "")).join("\n"));

function setThrows(text: string | undefined): void {
	res.editing.value.throws = (text ?? "")
		.split("\n")
		.map((line) => line.trim())
		.filter(Boolean)
		.map((line) => {
			const [type, ...rest] = line.split(/\s+[—-]\s+/);

			return { type: type.trim(), description: rest.join(" — ").trim() };
		});
}

onMounted(res.load);
</script>
