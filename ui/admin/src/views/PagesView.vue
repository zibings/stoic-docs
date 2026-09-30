<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Pages</h1>
				<p class="page__subtitle">Prose in the four reader modes. Reference pages are the prose for a symbol; link that symbol with the <em>subject</em> role.</p>
			</div>
		</div>

		<ResourceTable title="Pages" :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New page" sort-field="title" @create="res.startCreate()" @edit="res.startEdit($event)" @delete="res.remove($event, (p) => p.title)">
			<template #toolbar>
				<Select v-model="mode" :options="modeOptions" option-label="label" option-value="value" size="small" />
			</template>
			<template #cell-title="{ row }">
				<RouterLink :to="{ name: 'page', params: { id: row.id } }">{{ row.title }}</RouterLink>
			</template>
			<template #actions="{ row }">
				<Button icon="pi pi-file-edit" text rounded size="small" aria-label="Edit content" @click="router.push({ name: 'page', params: { id: row.id } })" />
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New page' : `Edit ${res.editing.value.title}`" :saving="res.saving.value" :errors="res.errors.value" @save="onSave">
			<FormField v-slot="{ id }" label="Mode" :span="4" required :error="res.errors.value.mode">
				<Select :id="id" v-model="(res.editing.value.mode as string)" :options="PAGE_MODES" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Title" :span="8" required :error="res.errors.value.title">
				<InputText :id="id" v-model="(res.editing.value.title as string)" fluid @update:model-value="autoSlug" />
			</FormField>
			<FormField v-slot="{ id }" label="Slug" :span="8" required hint="Reference pages use the module path + symbol, e.g. tessel/query/createQuery" :error="res.errors.value.slug">
				<InputText :id="id" v-model="(res.editing.value.slug as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Minutes" :span="4" hint="Optional reading / doing time">
				<InputNumber :id="id" :model-value="(res.editing.value.minutes as number | null)" :use-grouping="false" fluid @update:model-value="res.editing.value.minutes = $event ?? null" />
			</FormField>
			<FormField v-slot="{ id }" label="Introduced in" :span="6" required :error="res.errors.value.introducedVersionId">
				<VersionSelect :model-value="(res.editing.value.introducedVersionId as number | null)" :input-id="id" @update:model-value="res.editing.value.introducedVersionId = $event" />
			</FormField>
			<FormField v-slot="{ id }" label="Removed / superseded in" :span="6">
				<VersionSelect :model-value="(res.editing.value.removedVersionId as number | null)" :input-id="id" clearable placeholder="still current" @update:model-value="res.editing.value.removedVersionId = $event" />
			</FormField>
			<FormField v-slot="{ id }" label="Summary">
				<InputText :id="id" v-model="(res.editing.value.summary as string)" fluid />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { onMounted, ref, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";

import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import VersionSelect from "components/VersionSelect.vue";
import { useResource } from "composables/useResource";
import { PAGE_MODES, slugify } from "lib/forms";

const router = useRouter();
const mode = ref<string | null>(null);
const modeOptions = [{ label: "All modes", value: null }, ...PAGE_MODES.map((m) => ({ label: m, value: m }))];
const res = useResource("pages", () => ({ mode: mode.value }));

const columns: ColumnDef[] = [
	{ field: "mode", header: "Mode", kind: "tag", width: "10%" },
	{ field: "title", header: "Title", width: "30%" },
	{ field: "slug", header: "Slug", kind: "mono" },
	{ field: "introducedVersionId", header: "From", kind: "version", width: "9%" },
	{ field: "removedVersionId", header: "Until", kind: "version", width: "9%" },
	{ field: "minutes", header: "Min", kind: "mono", width: "6%" },
];

function autoSlug(title: string | undefined): void {
	if (res.isNew.value && res.editing.value.mode !== "reference") {
		res.editing.value.slug = slugify(title ?? "");
	}
}

async function onSave(): Promise<void> {
	const saved = await res.save();

	if (saved && res.isNew.value === false) {
		return;
	}

	if (saved) {
		router.push({ name: "page", params: { id: saved.id } });
	}
}

watch(mode, () => res.load());
onMounted(res.load);
</script>
