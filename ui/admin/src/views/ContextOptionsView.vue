<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Context options</h1>
				<p class="page__subtitle">The languages and package managers offered in the site's context bar. Code samples reference these keys.</p>
			</div>
		</div>

		<ResourceTable title="Options" :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New option" sort-field="kind" @create="res.startCreate()" @edit="res.startEdit($event)" @delete="res.remove($event, (o) => o.label)" />

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New option' : `Edit ${res.editing.value.label}`" :saving="res.saving.value" :errors="res.errors.value" @save="res.save()">
			<FormField v-slot="{ id }" label="Kind" :span="4" required>
				<Select :id="id" v-model="(res.editing.value.kind as string)" :options="[{ label: 'Language', value: 'language' }, { label: 'Package manager', value: 'packageManager' }]" option-label="label" option-value="value" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Key" :span="4" required hint="Used by samples and remembered preferences, e.g. ts" :error="res.errors.value.optionKey">
				<InputText :id="id" v-model="(res.editing.value.optionKey as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Label" :span="4" required :error="res.errors.value.label">
				<InputText :id="id" v-model="(res.editing.value.label as string)" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Sort order" :span="6">
				<InputNumber :id="id" v-model="(res.editing.value.sortOrder as number)" :use-grouping="false" fluid />
			</FormField>
			<div class="field field--6 field--inline" style="align-self: end">
				<Checkbox v-model="(res.editing.value.isDefault as boolean)" binary input-id="isDefault" />
				<label for="isDefault">Default for its kind</label>
			</div>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import Checkbox from "primevue/checkbox";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { onMounted } from "vue";

import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import { useResource } from "composables/useResource";
import { useCatalogStore } from "stores/catalog";

const catalog = useCatalogStore();
const res = useResource("contextOptions", () => ({}), () => catalog.reloadContextOptions());

const columns: ColumnDef[] = [
	{ field: "kind", header: "Kind", kind: "tag", width: "18%" },
	{ field: "optionKey", header: "Key", kind: "mono", width: "16%" },
	{ field: "label", header: "Label" },
	{ field: "sortOrder", header: "Order", kind: "mono", width: "8%" },
	{ field: "isDefault", header: "Default", kind: "bool", width: "8%" },
];

onMounted(res.load);
</script>
