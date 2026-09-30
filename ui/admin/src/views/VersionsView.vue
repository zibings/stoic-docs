<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Versions</h1>
				<p class="page__subtitle">Sort key orders versions (higher is newer) without assuming any version scheme. The label is what appears in URLs.</p>
			</div>
		</div>

		<ResourceTable title="Versions" :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New version" sort-field="sortKey" @create="res.startCreate()" @edit="res.startEdit($event)" @delete="res.remove($event, (v) => v.label)">
			<template #actions="{ row }">
				<Button v-if="!row.isLatest" label="Mark latest" text size="small" @click="markLatest(row.id)" />
				<Tag v-else value="latest" />
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New version' : `Edit ${res.editing.value.label}`" :saving="res.saving.value" :errors="res.errors.value" @save="res.save()">
			<FormField v-slot="{ id }" label="Tag" :span="4" required hint="As the library publishes it, e.g. 4.2.0" :error="res.errors.value.tag">
				<InputText :id="id" v-model="(res.editing.value.tag as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Label" :span="4" required hint="Used in URLs, e.g. v4.2" :error="res.errors.value.label">
				<InputText :id="id" v-model="(res.editing.value.label as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Sort key" :span="4" required hint="Higher is newer" :error="res.errors.value.sortKey">
				<InputNumber :id="id" v-model="(res.editing.value.sortKey as number)" :use-grouping="false" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Released" :span="6" hint="YYYY-MM-DD, optional">
				<InputText :id="id" :model-value="(res.editing.value.releasedAt as string | null)?.slice(0, 10) ?? ''" placeholder="2026-01-15" fluid @update:model-value="res.editing.value.releasedAt = $event || null" />
			</FormField>
			<div class="field field--6 field--inline" style="align-self: end">
				<Checkbox v-model="(res.editing.value.isSupported as boolean)" binary input-id="supported" />
				<label for="supported">Still supported</label>
			</div>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Tag from "primevue/tag";
import { useToast } from "primevue/usetoast";
import { onMounted } from "vue";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";
import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import { useResource } from "composables/useResource";
import { useCatalogStore } from "stores/catalog";

const catalog = useCatalogStore();
const toast = useToast();
const res = useResource("versions", () => ({}), () => catalog.reloadVersions());

const columns: ColumnDef[] = [
	{ field: "label", header: "Label", kind: "mono", width: "12%" },
	{ field: "tag", header: "Tag", kind: "mono", width: "12%" },
	{ field: "sortKey", header: "Sort key", kind: "mono", width: "10%" },
	{ field: "releasedAt", header: "Released", width: "16%" },
	{ field: "isSupported", header: "Supported", kind: "bool", width: "10%" },
	{ field: "isLatest", header: "Latest", kind: "bool", width: "8%" },
];

async function markLatest(id: number): Promise<void> {
	try {
		await adminApi.markLatest(id);
		await res.load();
		await catalog.reloadVersions();
		toast.add({ severity: "success", summary: "Marked latest", life: 2500 });
	} catch (error) {
		toast.add({ severity: "error", summary: "Could not mark latest", detail: errorMessage(error), life: 6000 });
	}
}

onMounted(res.load);
</script>
