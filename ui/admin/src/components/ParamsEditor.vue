<template>
	<div class="params">
		<DataTable :value="rows" data-key="_key" size="small" edit-mode="cell" @cell-edit-complete="onEdit">
			<template #empty><span class="muted">No parameters.</span></template>
			<Column field="name" header="Name" style="width: 22%">
				<template #body="{ data }"><span class="mono">{{ data.name }}</span></template>
				<template #editor="{ data, field }"><InputText v-model="data[field]" fluid autofocus /></template>
			</Column>
			<Column field="type" header="Type" style="width: 26%">
				<template #body="{ data }"><span class="mono">{{ data.type }}</span></template>
				<template #editor="{ data, field }"><InputText v-model="data[field]" fluid /></template>
			</Column>
			<Column field="required" header="Required" style="width: 10%">
				<template #body="{ data }"><Checkbox v-model="data.required" binary @update:model-value="push" /></template>
			</Column>
			<Column field="default" header="Default" style="width: 14%">
				<template #body="{ data }"><span class="mono">{{ data.default ?? "" }}</span></template>
				<template #editor="{ data, field }"><InputText v-model="data[field]" fluid /></template>
			</Column>
			<Column field="description" header="Description">
				<template #body="{ data }">{{ data.description }}</template>
				<template #editor="{ data, field }"><InputText v-model="data[field]" fluid /></template>
			</Column>
			<Column style="width: 1%">
				<template #body="{ index }">
					<div class="row" style="flex-wrap: nowrap; gap: 0">
						<Button icon="pi pi-arrow-up" text rounded size="small" aria-label="Move up" :disabled="index === 0" @click="move(index, -1)" />
						<Button icon="pi pi-arrow-down" text rounded size="small" aria-label="Move down" :disabled="index === rows.length - 1" @click="move(index, 1)" />
						<Button icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Remove" @click="removeAt(index)" />
					</div>
				</template>
			</Column>
		</DataTable>
		<Button label="Add parameter" icon="pi pi-plus" text size="small" @click="add" />
		<p class="field__hint">Click a cell to edit it. Use the same field names the prose references (for options types, one row per field).</p>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import { ref, watch } from "vue";

import type { AdminParam } from "types/admin";

type Row = AdminParam & { _key: number };

const props = defineProps<{ modelValue: AdminParam[] }>();
const emit = defineEmits<{ (e: "update:modelValue", value: AdminParam[]): void }>();

let nextKey = 1;
const rows = ref<Row[]>([]);

watch(
	() => props.modelValue,
	(value) => {
		rows.value = (value ?? []).map((p) => ({ ...p, default: p.default ?? null, _key: nextKey++ }));
	},
	{ immediate: true },
);

function push(): void {
	emit(
		"update:modelValue",
		rows.value.map(({ _key, ...param }) => ({ ...param, default: param.default === "" ? null : param.default })),
	);
}

function onEdit(event: { data: Row; newValue: unknown; field: string }): void {
	(event.data as unknown as Record<string, unknown>)[event.field] = typeof event.newValue === "string" ? event.newValue : (event.newValue ?? "");
	push();
}

function add(): void {
	rows.value.push({ name: "", type: "", required: false, default: null, description: "", _key: nextKey++ });
	push();
}

function removeAt(index: number): void {
	rows.value.splice(index, 1);
	push();
}

function move(index: number, delta: number): void {
	const target = index + delta;

	if (target < 0 || target >= rows.value.length) {
		return;
	}

	const [row] = rows.value.splice(index, 1);

	rows.value.splice(target, 0, row);
	push();
}
</script>

<style>
.params {
	display: flex;
	flex-direction: column;
	gap: 8px;
}
</style>
