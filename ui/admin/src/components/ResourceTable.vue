<template>
	<section class="card resource-table">
		<div class="resource-table__head">
			<div>
				<h2 class="resource-table__title">{{ title }}</h2>
				<p v-if="subtitle" class="muted">{{ subtitle }}</p>
			</div>
			<div class="row">
				<slot name="toolbar" />
				<IconField v-if="searchable">
					<InputIcon class="pi pi-search" />
					<InputText v-model="search" placeholder="Filter" size="small" />
				</IconField>
				<Button v-if="createLabel" :label="createLabel" icon="pi pi-plus" size="small" @click="emit('create')" />
			</div>
		</div>

		<DataTable :value="filtered" :loading="loading" data-key="id" size="small" striped-rows :paginator="filtered.length > pageSize" :rows="pageSize" :rows-per-page-options="[10, 25, 50, 100]" :sort-field="sortField" :sort-order="1" scrollable>
			<template #empty><span class="muted">Nothing here yet.</span></template>
			<Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable !== false" :style="col.width ? { width: col.width } : undefined">
				<template #body="{ data }">
					<slot :name="`cell-${col.field}`" :row="data" :value="data[col.field]">
						<template v-if="col.kind === 'bool'">
							<i :class="data[col.field] ? 'pi pi-check-circle' : 'pi pi-minus'" :style="{ color: data[col.field] ? 'var(--color-accent)' : 'var(--color-faint)' }" :aria-label="data[col.field] ? 'yes' : 'no'" />
						</template>
						<span v-else-if="col.kind === 'mono'" class="mono">{{ data[col.field] }}</span>
						<Tag v-else-if="col.kind === 'tag'" :value="String(data[col.field] ?? '')" severity="secondary" />
						<span v-else-if="col.kind === 'version'" class="mono">{{ catalog.versionLabel(data[col.field]) }}</span>
						<span v-else class="resource-table__text">{{ data[col.field] }}</span>
					</slot>
				</template>
			</Column>
			<Column v-if="hasActions" header="" :style="{ width: '1%' }" body-class="resource-table__actions">
				<template #body="{ data }">
					<div class="row resource-table__actions-row">
						<slot name="actions" :row="data" />
						<Button v-if="editable" icon="pi pi-pencil" text rounded size="small" aria-label="Edit" @click="emit('edit', data)" />
						<Button v-if="deletable" icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Delete" @click="emit('delete', data)" />
					</div>
				</template>
			</Column>
		</DataTable>
	</section>
</template>

<script setup lang="ts" generic="Row extends { id: number }">
import Button from "primevue/button";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Tag from "primevue/tag";
import { computed, ref } from "vue";

import { useCatalogStore } from "stores/catalog";

export interface ColumnDef {
	field: string;
	header: string;
	kind?: "text" | "bool" | "mono" | "tag" | "version";
	width?: string;
	sortable?: boolean;
}

const props = withDefaults(
	defineProps<{
		title: string;
		subtitle?: string;
		rows: Row[];
		columns: ColumnDef[];
		loading?: boolean;
		createLabel?: string;
		editable?: boolean;
		deletable?: boolean;
		searchable?: boolean;
		sortField?: string;
		pageSize?: number;
	}>(),
	{ loading: false, editable: true, deletable: true, searchable: true, pageSize: 25 },
);

const emit = defineEmits<{ (e: "create"): void; (e: "edit", row: Row): void; (e: "delete", row: Row): void }>();

const catalog = useCatalogStore();
const search = ref("");
const hasActions = computed(() => props.editable || props.deletable);

const filtered = computed(() => {
	const needle = search.value.trim().toLowerCase();

	if (!needle) {
		return props.rows;
	}

	return props.rows.filter((row) => Object.values(row as Record<string, unknown>).some((v) => String(v ?? "").toLowerCase().includes(needle)));
});
</script>

<style>
.resource-table {
	display: flex;
	flex-direction: column;
	gap: 14px;
}

.resource-table__head {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: 16px;
	flex-wrap: wrap;
}

.resource-table__title {
	font-size: 17px;
	font-weight: 600;
}

.resource-table__text {
	display: inline-block;
	max-width: 420px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	vertical-align: bottom;
}

.resource-table__actions-row {
	justify-content: flex-end;
	flex-wrap: nowrap;
	gap: 2px;
}
</style>
