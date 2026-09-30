<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Import / export</h1>
				<p class="page__subtitle">Bundles are the JSON interchange format: what scanners emit, what fixtures are, and what moves content between installs. Importing upserts by natural key inside one transaction.</p>
			</div>
		</div>

		<div class="ie">
			<section class="card stack">
				<h2 class="resource-table__title">Export</h2>
				<p class="muted">Every version, module, symbol, contract, page, sample, change, and course as one bundle. Use it as a backup or to seed another install.</p>
				<div class="row">
					<Button label="Download bundle" icon="pi pi-download" :loading="exporting" @click="exportBundle" />
					<span v-if="exported" class="muted">{{ exported }}</span>
				</div>
			</section>

			<section class="card stack">
				<h2 class="resource-table__title">Import</h2>
				<p class="muted">Pick a bundle file. Existing records with the same natural key are updated, new ones created; nothing is deleted.</p>
				<div class="row">
					<label class="p-button p-component p-button-outlined ie__file">
						<input type="file" accept="application/json,.json" style="display: none" @change="onFile" />
						<i class="pi pi-upload" /> <span>Choose bundle…</span>
					</label>
					<span v-if="fileName" class="mono muted">{{ fileName }}</span>
				</div>
				<Textarea v-model="raw" rows="10" auto-resize fluid class="mono" placeholder='{ "versions": [...], "modules": [...] }' spellcheck="false" />
				<div class="row">
					<Button label="Import" icon="pi pi-check" :disabled="!raw.trim()" :loading="importing" @click="importBundle" />
					<span v-if="parseError" class="field__error">{{ parseError }}</span>
				</div>
				<Message v-if="result" severity="success" :closable="false">
					Imported: <span v-for="(count, type) in result" :key="type" class="mono">{{ type }} {{ count }} · </span>
				</Message>
				<Message v-if="importError" severity="error" :closable="false">{{ importError }}</Message>
			</section>
		</div>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import Message from "primevue/message";
import Textarea from "primevue/textarea";
import { ref } from "vue";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";
import { useCatalogStore } from "stores/catalog";

const catalog = useCatalogStore();
const exporting = ref(false);
const exported = ref<string | null>(null);
const raw = ref("");
const fileName = ref<string | null>(null);
const parseError = ref<string | null>(null);
const importing = ref(false);
const result = ref<Record<string, number> | null>(null);
const importError = ref<string | null>(null);

async function exportBundle(): Promise<void> {
	exporting.value = true;

	try {
		const bundle = await adminApi.exportBundle();
		const blob = new Blob([JSON.stringify(bundle, null, "\t")], { type: "application/json" });
		const url = URL.createObjectURL(blob);
		const a = document.createElement("a");

		a.href = url;
		a.download = `${catalog.libraryName.toLowerCase().replace(/[^a-z0-9]+/g, "-")}-docs-bundle.json`;
		a.click();
		URL.revokeObjectURL(url);
		exported.value = `Exported at ${new Date().toLocaleTimeString()}`;
	} catch (err) {
		exported.value = errorMessage(err);
	} finally {
		exporting.value = false;
	}
}

async function onFile(event: Event): Promise<void> {
	const input = event.target as HTMLInputElement;
	const file = input.files?.[0];

	if (file) {
		raw.value = await file.text();
		fileName.value = file.name;
		result.value = null;
		importError.value = null;
	}

	input.value = "";
}

async function importBundle(): Promise<void> {
	parseError.value = null;
	importError.value = null;
	result.value = null;

	let bundle: unknown;

	try {
		bundle = JSON.parse(raw.value);
	} catch {
		parseError.value = "That is not valid JSON.";

		return;
	}

	importing.value = true;

	try {
		result.value = await adminApi.importBundle(bundle);
		await catalog.load(true);
	} catch (err) {
		importError.value = errorMessage(err);
	} finally {
		importing.value = false;
	}
}
</script>

<style>
.ie {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 16px;
}

.ie__file {
	cursor: pointer;
	gap: 8px;
}

@media (max-width: 1000px) {
	.ie {
		grid-template-columns: minmax(0, 1fr);
	}
}
</style>
