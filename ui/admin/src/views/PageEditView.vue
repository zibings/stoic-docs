<template>
	<div class="page">
		<div class="page__head">
			<div>
				<p class="label"><RouterLink to="/pages">Pages</RouterLink> · {{ page?.mode }}</p>
				<h1 class="page__title">{{ page?.title ?? "…" }}</h1>
				<p v-if="page" class="page__subtitle mono">{{ page.slug }} · {{ catalog.versionLabel(page.introducedVersionId) }}<template v-if="page.removedVersionId"> → {{ catalog.versionLabel(page.removedVersionId) }}</template></p>
			</div>
			<div class="row">
				<a v-if="siteLink" :href="siteLink" target="_blank" rel="noopener">View on site ↗</a>
				<Button label="Save body" icon="pi pi-save" :loading="saving" :disabled="!dirty" @click="saveBody" />
			</div>
		</div>

		<Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

		<section v-if="page" class="card stack">
			<MarkdownEditor v-model="body" :context="renderContext" />
		</section>

		<section class="card stack">
			<div class="resource-table__head">
				<div>
					<h2 class="resource-table__title">Linked symbols</h2>
					<p class="muted">The <em>subject</em> is what a reference page documents; <em>mentions</em> drive the contract pane's scroll-follow and "also covered in".</p>
				</div>
				<Button label="Save links" size="small" :loading="savingLinks" :disabled="!linksDirty" @click="saveLinks" />
			</div>
			<div v-for="(link, i) in links" :key="i" class="row">
				<div style="flex: 1 1 auto; min-width: 240px">
					<SymbolPicker :model-value="link.symbolId" @update:model-value="setLinkSymbol(i, $event)" />
				</div>
				<Select v-model="link.role" :options="['subject', 'mentions']" size="small" @update:model-value="linksDirty = true" />
				<Button icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Remove link" @click="removeLink(i)" />
			</div>
			<Button label="Add symbol" icon="pi pi-plus" text size="small" @click="addLink" />
		</section>

		<ResourceTable title="Code samples" subtitle="Embedded in the body with <<sample:key>>. One row per language and package-manager variant; leave the variant empty when a sample applies to all." :rows="samples.rows.value" :columns="sampleColumns" :loading="samples.loading.value" create-label="New sample" sort-field="sampleKey" :searchable="false" @create="samples.startCreate({ pageId, language: defaultLanguage })" @edit="samples.startEdit($event)" @delete="samples.remove($event, (s) => `${s.sampleKey} (${s.language}${s.variant ? ' · ' + s.variant : ''})`)" />

		<RecordDialog v-model:visible="samples.dialogOpen.value" :header="samples.isNew.value ? 'New sample' : `Edit ${samples.editing.value.sampleKey}`" :saving="samples.saving.value" :errors="samples.errors.value" width="820px" @save="samples.save()">
			<FormField v-slot="{ id }" label="Key" :span="4" required hint="Used by <<sample:key>>" :error="samples.errors.value.sampleKey">
				<InputText :id="id" v-model="(samples.editing.value.sampleKey as string)" class="mono" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Title" :span="4" hint="Shown in the card header, e.g. user.ts">
				<InputText :id="id" :model-value="(samples.editing.value.title as string | null) ?? ''" class="mono" fluid @update:model-value="samples.editing.value.title = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="Language" :span="2" required :error="samples.errors.value.language">
				<Select :id="id" v-model="(samples.editing.value.language as string)" :options="catalog.languages" option-label="label" option-value="optionKey" editable fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Variant" :span="2" hint="Package manager">
				<Select :id="id" :model-value="(samples.editing.value.variant as string | null)" :options="catalog.packageManagers" option-label="label" option-value="optionKey" show-clear placeholder="all" fluid @update:model-value="samples.editing.value.variant = $event ?? null" />
			</FormField>
			<FormField v-slot="{ id }" label="Code" required :error="samples.errors.value.code">
				<Textarea :id="id" v-model="(samples.editing.value.code as string)" rows="10" auto-resize fluid class="mono" spellcheck="false" />
			</FormField>
			<div class="field field--6 field--inline">
				<Checkbox v-model="(samples.editing.value.isTested as boolean)" binary input-id="tested" />
				<label for="tested">This sample is a test</label>
			</div>
			<FormField v-slot="{ id }" label="Last passed on" :span="6">
				<VersionSelect :model-value="(samples.editing.value.lastTestPassVersionId as number | null)" :input-id="id" clearable placeholder="not recorded" @update:model-value="samples.editing.value.lastTestPassVersionId = $event" />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";
import FormField from "components/FormField.vue";
import MarkdownEditor from "components/MarkdownEditor.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import SymbolPicker from "components/SymbolPicker.vue";
import VersionSelect from "components/VersionSelect.vue";
import { useResource } from "composables/useResource";
import type { RenderContext, SymbolRefInfo } from "markdown/render";
import { useCatalogStore } from "stores/catalog";
import type { AdminPage } from "types/admin";
import type { CodeSample } from "types/docs";

const props = defineProps<{ id: string }>();

const catalog = useCatalogStore();
const toast = useToast();
const pageId = computed(() => Number(props.id));
const page = ref<AdminPage | null>(null);
const body = ref("");
const saving = ref(false);
const error = ref<string | null>(null);
const dirty = computed(() => page.value !== null && body.value !== page.value.body);

const samples = useResource("samples", () => ({ pageId: pageId.value }));
const sampleColumns: ColumnDef[] = [
	{ field: "sampleKey", header: "Key", kind: "mono", width: "18%" },
	{ field: "title", header: "Title", kind: "mono", width: "18%" },
	{ field: "language", header: "Language", kind: "tag", width: "12%" },
	{ field: "variant", header: "Variant", kind: "tag", width: "12%" },
	{ field: "isTested", header: "Tested", kind: "bool", width: "8%" },
	{ field: "lastTestPassVersionId", header: "Passed on", kind: "version", width: "12%" },
];

const defaultLanguage = computed(() => catalog.languages.find((l) => l.isDefault)?.optionKey ?? catalog.languages[0]?.optionKey ?? "");

interface LinkRow {
	symbolId: number | null;
	role: "subject" | "mentions";
}

const links = ref<LinkRow[]>([]);
const linksDirty = ref(false);
const savingLinks = ref(false);

// Preview context: every known symbol resolves (the site only resolves linked ones, so unresolved refs show as
// warnings here too), samples come from this page, language from the catalog default.
const renderContext = computed<RenderContext>(() => {
	const latest = catalog.versions.find((v) => v.isLatest) ?? catalog.versionsNewestFirst[0];
	const versionLabel = latest?.label ?? "latest";
	const symbols = new Map<string, SymbolRefInfo>();

	for (const s of catalog.symbols) {
		const modulePath = s.ref.slice(0, s.ref.indexOf("#"));
		const name = s.ref.slice(s.ref.indexOf("#") + 1);

		symbols.set(s.ref, { ref: s.ref, name, shortName: s.name, route: `/${versionLabel}/reference/${modulePath}/${name}`, removed: false });
	}

	const language = defaultLanguage.value || null;

	return {
		symbols,
		samples: samples.rows.value.map<CodeSample>((s) => ({ key: s.sampleKey, title: s.title, language: s.language, variant: s.variant, code: s.code, isTested: s.isTested, lastTestPassLabel: catalog.versionLabel(s.lastTestPassVersionId) })),
		language,
		languageLabel: catalog.languages.find((l) => l.optionKey === language)?.label ?? "",
		packageManager: catalog.packageManagers.find((p) => p.isDefault)?.optionKey ?? catalog.packageManagers[0]?.optionKey ?? null,
		versionLabel,
		showUpgradeNote: () => true,
	};
});

const siteLink = computed(() => {
	if (!page.value || !catalog.siteUrl) {
		return null;
	}

	const version = catalog.versionLabel(page.value.introducedVersionId);

	return `${catalog.siteUrl}/${version}/${page.value.mode}/${page.value.slug}`;
});

async function load(): Promise<void> {
	try {
		page.value = await adminApi.get("pages", pageId.value);
		body.value = page.value.body;
		links.value = (await adminApi.pageSymbols(pageId.value)).map((l) => ({ symbolId: l.symbolId, role: (l.role ?? "mentions") as LinkRow["role"] }));
		linksDirty.value = false;
		await samples.load();
	} catch (err) {
		error.value = errorMessage(err);
	}
}

async function saveBody(): Promise<void> {
	if (!page.value) {
		return;
	}

	saving.value = true;

	try {
		page.value = await adminApi.update("pages", pageId.value, { body: body.value });
		toast.add({ severity: "success", summary: "Body saved", life: 2500 });
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not save", detail: errorMessage(err), life: 6000 });
	} finally {
		saving.value = false;
	}
}

function addLink(): void {
	links.value.push({ symbolId: null, role: "mentions" });
	linksDirty.value = true;
}

function removeLink(index: number): void {
	links.value.splice(index, 1);
	linksDirty.value = true;
}

function setLinkSymbol(index: number, symbolId: number | null): void {
	links.value[index].symbolId = symbolId;
	linksDirty.value = true;
}

async function saveLinks(): Promise<void> {
	savingLinks.value = true;

	try {
		const payload = links.value.filter((l) => l.symbolId !== null).map((l) => ({ symbolId: l.symbolId!, role: l.role }));

		links.value = (await adminApi.setPageSymbols(pageId.value, payload)).map((l) => ({ symbolId: l.symbolId, role: (l.role ?? "mentions") as LinkRow["role"] }));
		linksDirty.value = false;
		toast.add({ severity: "success", summary: "Links saved", life: 2500 });
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not save links", detail: errorMessage(err), life: 6000 });
	} finally {
		savingLinks.value = false;
	}
}

watch(pageId, load);
onMounted(load);
</script>
