<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Changes</h1>
				<p class="page__subtitle">Changelog entries per version; the upgrade view groups them by kind and computes contract diffs from the linked symbols.</p>
			</div>
		</div>

		<ResourceTable title="Changes" :rows="res.rows.value" :columns="columns" :loading="res.loading.value" create-label="New change" sort-field="versionId" @create="res.startCreate({ versionId: latestId })" @edit="res.startEdit($event)" @delete="res.remove($event, (c) => c.title)">
			<template #toolbar>
				<VersionSelect :model-value="versionFilter" clearable placeholder="All versions" @update:model-value="versionFilter = $event" />
			</template>
			<template #cell-title="{ row }">
				<RouterLink :to="{ name: 'change', params: { id: row.id } }">{{ row.title }}</RouterLink>
			</template>
			<template #cell-codemodCmd="{ row }">
				<i v-if="row.codemodCmd" class="pi pi-bolt" aria-label="codemod available" />
			</template>
		</ResourceTable>

		<RecordDialog v-model:visible="res.dialogOpen.value" :header="res.isNew.value ? 'New change' : `Edit ${res.editing.value.title}`" :saving="res.saving.value" :errors="res.errors.value" @save="onSave">
			<FormField v-slot="{ id }" label="Version" :span="4" required :error="res.errors.value.versionId">
				<VersionSelect :model-value="(res.editing.value.versionId as number | null)" :input-id="id" @update:model-value="res.editing.value.versionId = $event" />
			</FormField>
			<FormField v-slot="{ id }" label="Kind" :span="4" required :error="res.errors.value.kind">
				<Select :id="id" v-model="(res.editing.value.kind as string)" :options="CHANGE_KINDS" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Sort order" :span="4">
				<InputNumber :id="id" v-model="(res.editing.value.sortOrder as number)" :use-grouping="false" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Title" required :error="res.errors.value.title">
				<InputText :id="id" v-model="(res.editing.value.title as string)" fluid />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";

import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import VersionSelect from "components/VersionSelect.vue";
import { useResource } from "composables/useResource";
import { CHANGE_KINDS } from "lib/forms";
import { useCatalogStore } from "stores/catalog";

const router = useRouter();
const catalog = useCatalogStore();
const versionFilter = ref<number | null>(null);
const res = useResource("changes", () => ({ versionId: versionFilter.value }));
const latestId = computed(() => catalog.versions.find((v) => v.isLatest)?.id ?? null);

const columns: ColumnDef[] = [
	{ field: "versionId", header: "Version", kind: "version", width: "10%" },
	{ field: "kind", header: "Kind", kind: "tag", width: "12%" },
	{ field: "title", header: "Title" },
	{ field: "codemodCmd", header: "Codemod", width: "8%", sortable: false },
	{ field: "sortOrder", header: "Order", kind: "mono", width: "7%" },
];

async function onSave(): Promise<void> {
	const wasNew = res.isNew.value;
	const saved = await res.save();

	if (saved && wasNew) {
		router.push({ name: "change", params: { id: saved.id } });
	}
}

watch(versionFilter, () => res.load());
onMounted(res.load);
</script>
