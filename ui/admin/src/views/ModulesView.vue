<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Modules & symbols</h1>
				<p class="page__subtitle">A module is a path such as <span class="mono">tessel/query</span>; open one to manage its symbols.</p>
			</div>
		</div>

		<ResourceTable title="Modules" :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New module" sort-field="sortOrder" @create="res.startCreate()" @edit="res.startEdit($event)" @delete="res.remove($event, (m) => m.path)">
			<template #cell-path="{ row }">
				<RouterLink :to="{ name: 'module', params: { id: row.id } }" class="mono">{{ row.path }}</RouterLink>
			</template>
			<template #cell-symbols="{ row }">
				<span class="mono">{{ symbolCount(row.id) }}</span>
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New module' : `Edit ${res.editing.value.path}`" :saving="res.saving.value" :errors="res.errors.value" @save="res.save()">
			<FormField v-slot="{ id }" label="Path" :span="8" required hint="e.g. tessel/query" :error="res.errors.value.path">
				<InputText :id="id" v-model="(res.editing.value.path as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Sort order" :span="4">
				<InputNumber :id="id" v-model="(res.editing.value.sortOrder as number)" :use-grouping="false" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Summary">
				<InputText :id="id" v-model="(res.editing.value.summary as string)" fluid />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import { onMounted } from "vue";
import { RouterLink } from "vue-router";

import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import { useResource } from "composables/useResource";
import { useCatalogStore } from "stores/catalog";

const catalog = useCatalogStore();
const res = useResource("modules", () => ({}), () => catalog.reloadModules());

const columns: ColumnDef[] = [
	{ field: "path", header: "Path", width: "30%" },
	{ field: "summary", header: "Summary" },
	{ field: "symbols", header: "Symbols", width: "10%", sortable: false },
	{ field: "sortOrder", header: "Order", kind: "mono", width: "8%" },
];

function symbolCount(moduleId: number): number {
	return catalog.symbols.filter((s) => s.moduleId === moduleId).length;
}

onMounted(res.load);
</script>
