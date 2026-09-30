<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Courses</h1>
				<p class="page__subtitle">A course is an ordered set of Learn pages; each lesson can carry "Try it" steps.</p>
			</div>
		</div>

		<ResourceTable title="Courses" :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New course" sort-field="sortOrder" @create="res.startCreate()" @edit="res.startEdit($event)" @delete="res.remove($event, (c) => c.title)">
			<template #cell-title="{ row }">
				<RouterLink :to="{ name: 'course', params: { id: row.id } }">{{ row.title }}</RouterLink>
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New course' : `Edit ${res.editing.value.title}`" :saving="res.saving.value" :errors="res.errors.value" @save="res.save()">
			<FormField v-slot="{ id }" label="Title" :span="8" required :error="res.errors.value.title">
				<InputText :id="id" v-model="(res.editing.value.title as string)" fluid @update:model-value="res.isNew.value && (res.editing.value.slug = slugify($event ?? ''))" />
			</FormField>
			<FormField v-slot="{ id }" label="Sort order" :span="4">
				<InputNumber :id="id" v-model="(res.editing.value.sortOrder as number)" :use-grouping="false" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Slug" required :error="res.errors.value.slug">
				<InputText :id="id" v-model="(res.editing.value.slug as string)" class="mono" fluid />
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
import { slugify } from "lib/forms";

const res = useResource("courses");

const columns: ColumnDef[] = [
	{ field: "title", header: "Title", width: "30%" },
	{ field: "slug", header: "Slug", kind: "mono", width: "25%" },
	{ field: "summary", header: "Summary" },
	{ field: "sortOrder", header: "Order", kind: "mono", width: "8%" },
];

onMounted(res.load);
</script>
