<template>
	<div class="page">
		<div class="page__head">
			<div>
				<p class="label"><RouterLink to="/modules">Modules</RouterLink></p>
				<h1 class="page__title mono">{{ module?.path ?? "…" }}</h1>
				<p v-if="module?.summary" class="page__subtitle">{{ module.summary }}</p>
			</div>
		</div>

		<ResourceTable title="Symbols" subtitle="Nested symbols (options of a type, methods of a class) list their parent." :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New symbol" sort-field="sortOrder" @create="res.startCreate({ moduleId })" @edit="res.startEdit($event)" @delete="res.remove($event, (s) => s.ref)">
			<template #cell-name="{ row }">
				<RouterLink :to="{ name: 'symbol', params: { id: row.id } }" class="mono">{{ row.ref.slice(row.ref.indexOf("#") + 1) }}</RouterLink>
			</template>
			<template #cell-parentSymbolId="{ row }">
				<span class="mono muted">{{ row.parentSymbolId ? parentName(row.parentSymbolId) : "" }}</span>
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New symbol' : `Edit ${res.editing.value.name}`" :saving="res.saving.value" :errors="res.errors.value" @save="res.save()">
			<FormField v-slot="{ id }" label="Name" :span="6" required :error="res.errors.value.name">
				<InputText :id="id" v-model="(res.editing.value.name as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Kind" :span="3" required hint="Free text; these have display treatment" :error="res.errors.value.kind">
				<Select :id="id" v-model="(res.editing.value.kind as string)" :options="SYMBOL_KINDS" editable fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Sort order" :span="3">
				<InputNumber :id="id" v-model="(res.editing.value.sortOrder as number)" :use-grouping="false" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Parent symbol" hint="Leave empty for a top-level symbol">
				<Select :id="id" v-model="(res.editing.value.parentSymbolId as number | null)" :options="parentOptions" option-label="label" option-value="id" show-clear placeholder="none" fluid />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { computed, onMounted } from "vue";
import { RouterLink } from "vue-router";

import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import { useResource } from "composables/useResource";
import { SYMBOL_KINDS } from "lib/forms";
import { useCatalogStore } from "stores/catalog";

const props = defineProps<{ id: string }>();

const catalog = useCatalogStore();
const moduleId = computed(() => Number(props.id));
const module = computed(() => catalog.modules.find((m) => m.id === moduleId.value) ?? null);
const res = useResource("symbols", () => ({ moduleId: moduleId.value }), () => catalog.reloadSymbols());

const columns: ColumnDef[] = [
	{ field: "name", header: "Name", width: "28%" },
	{ field: "kind", header: "Kind", kind: "tag", width: "10%" },
	{ field: "parentSymbolId", header: "Parent", width: "22%" },
	{ field: "sortOrder", header: "Order", kind: "mono", width: "8%" },
];

const parentOptions = computed(() =>
	res.rows.value.filter((s) => s.id !== res.editing.value.id).map((s) => ({ id: s.id, label: s.ref.slice(s.ref.indexOf("#") + 1) })),
);

function parentName(id: number): string {
	const parent = catalog.symbols.find((s) => s.id === id);

	return parent ? parent.ref.slice(parent.ref.indexOf("#") + 1) : `#${id}`;
}

onMounted(res.load);
</script>
