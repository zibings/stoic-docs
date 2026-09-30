<template>
	<div class="page">
		<div class="page__head">
			<div>
				<p class="label"><RouterLink to="/changes">Changes</RouterLink> · {{ change ? catalog.versionLabel(change.versionId) : "" }}</p>
				<h1 class="page__title">{{ change?.title ?? "…" }}</h1>
			</div>
			<Button label="Save" icon="pi pi-save" :loading="saving" :disabled="!change" @click="save" />
		</div>

		<Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

		<form v-if="change" class="card form" @submit.prevent="save">
			<FormField v-slot="{ id }" label="Version" :span="3" required>
				<VersionSelect :model-value="change.versionId" :input-id="id" @update:model-value="change.versionId = $event ?? change.versionId" />
			</FormField>
			<FormField v-slot="{ id }" label="Kind" :span="3" required>
				<Select :id="id" v-model="change.kind" :options="CHANGE_KINDS" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Title" :span="6" required>
				<InputText :id="id" v-model="change.title" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Why" hint="The reasoning shown under the title">
				<Textarea :id="id" v-model="change.why" rows="3" auto-resize fluid />
			</FormField>
			<FormField v-slot="{ id }" label="RFC / discussion URL" :span="6">
				<InputText :id="id" :model-value="change.rfcUrl ?? ''" fluid @update:model-value="change.rfcUrl = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="Codemod command" :span="6" hint="Shown in the 'Migrate automatically' card">
				<InputText :id="id" :model-value="change.codemodCmd ?? ''" class="mono" fluid @update:model-value="change.codemodCmd = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="Before" :span="6" hint="The same call at the older version">
				<Textarea :id="id" :model-value="change.beforeCode ?? ''" rows="6" auto-resize fluid class="mono" spellcheck="false" @update:model-value="change.beforeCode = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="After" :span="6" hint="The same call now">
				<Textarea :id="id" :model-value="change.afterCode ?? ''" rows="6" auto-resize fluid class="mono" spellcheck="false" @update:model-value="change.afterCode = $event || null" />
			</FormField>
		</form>

		<section class="card stack">
			<div class="resource-table__head">
				<div>
					<h2 class="resource-table__title">Affected symbols</h2>
					<p class="muted">Contract diffs on the upgrade view are computed for these symbols.</p>
				</div>
				<Button label="Save symbols" size="small" :loading="savingLinks" :disabled="!linksDirty" @click="saveLinks" />
			</div>
			<div v-for="(id, i) in symbolIds" :key="i" class="row">
				<div style="flex: 1 1 auto; min-width: 240px">
					<SymbolPicker :model-value="id" @update:model-value="setSymbol(i, $event)" />
				</div>
				<Button icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Remove" @click="removeSymbol(i)" />
			</div>
			<Button label="Add symbol" icon="pi pi-plus" text size="small" @click="symbolIds.push(null); linksDirty = true" />
		</section>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";
import FormField from "components/FormField.vue";
import SymbolPicker from "components/SymbolPicker.vue";
import VersionSelect from "components/VersionSelect.vue";
import { CHANGE_KINDS, toPayload } from "lib/forms";
import { useCatalogStore } from "stores/catalog";
import type { AdminChange } from "types/admin";

const props = defineProps<{ id: string }>();

const catalog = useCatalogStore();
const toast = useToast();
const changeId = computed(() => Number(props.id));
const change = ref<AdminChange | null>(null);
const error = ref<string | null>(null);
const saving = ref(false);
const symbolIds = ref<(number | null)[]>([]);
const linksDirty = ref(false);
const savingLinks = ref(false);

async function load(): Promise<void> {
	try {
		change.value = await adminApi.get("changes", changeId.value);
		symbolIds.value = (await adminApi.changeSymbols(changeId.value)).map((l) => l.symbolId);
	} catch (err) {
		error.value = errorMessage(err);
	}
}

async function save(): Promise<void> {
	if (!change.value) {
		return;
	}

	saving.value = true;

	try {
		change.value = await adminApi.update("changes", changeId.value, toPayload(change.value as unknown as Record<string, unknown>) as Partial<AdminChange>);
		toast.add({ severity: "success", summary: "Saved", life: 2500 });
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not save", detail: errorMessage(err), life: 6000 });
	} finally {
		saving.value = false;
	}
}

function setSymbol(index: number, id: number | null): void {
	symbolIds.value[index] = id;
	linksDirty.value = true;
}

function removeSymbol(index: number): void {
	symbolIds.value.splice(index, 1);
	linksDirty.value = true;
}

async function saveLinks(): Promise<void> {
	savingLinks.value = true;

	try {
		symbolIds.value = (await adminApi.setChangeSymbols(changeId.value, symbolIds.value.filter((id): id is number => id !== null))).map((l) => l.symbolId);
		linksDirty.value = false;
		toast.add({ severity: "success", summary: "Symbols saved", life: 2500 });
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not save symbols", detail: errorMessage(err), life: 6000 });
	} finally {
		savingLinks.value = false;
	}
}

onMounted(load);
</script>
