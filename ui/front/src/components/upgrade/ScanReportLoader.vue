<template>
	<div class="scan-loader">
		<label class="scan-loader__toggle" :class="{ 'is-disabled': !scan.report }">
			<input type="checkbox" :checked="importsOnly" :disabled="!scan.report" @change="emit('update:importsOnly', ($event.target as HTMLInputElement).checked)" />
			<span>
				Only symbols my code imports
				<span v-if="scan.report" class="scan-loader__hint">(from <span class="mono">{{ scan.tool ?? scan.fileName ?? "scan report" }}</span><template v-if="hidden > 0">, {{ hidden }} of {{ total }} hidden</template>)</span>
				<span v-else class="scan-loader__hint">(load a scan report to enable)</span>
			</span>
		</label>
		<div class="scan-loader__actions">
			<label class="scan-loader__file">
				<input type="file" accept="application/json,.json" class="visually-hidden" @change="onFile" />
				<span class="scan-loader__link">{{ scan.report ? "Load another report" : "Load scan report" }}</span>
			</label>
			<button v-if="scan.report" type="button" class="scan-loader__link" @click="scan.clear()">Clear</button>
		</div>
		<p v-if="scan.error" class="scan-loader__error">{{ scan.error }}</p>
		<p v-else-if="suggestedFrom" class="scan-loader__hint">
			Your project has {{ scan.report?.package ?? "the library" }} {{ scan.report?.fromVersion }} installed.
			<RouterLink :to="{ name: 'upgrade', params: { from: suggestedFrom.label, to: label } }">Compare from {{ suggestedFrom.label }} instead</RouterLink>
		</p>
		<p class="scan-loader__note">Reports stay in this browser. Expected shape: <span class="mono">{ "imports": [{ "ref": "module/path#Name", "callSites": 23, "files": 9 }] }</span></p>
	</div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink } from "vue-router";

import { useCurrentVersion } from "composables/useCurrentVersion";
import { useSiteStore } from "stores/site";

import { useScanStore } from "stores/scan";

defineProps<{ importsOnly: boolean; hidden: number; total: number }>();

const emit = defineEmits<{ (e: "update:importsOnly", value: boolean): void }>();

const store = useScanStore();
const scan = computed(() => ({ report: store.report, fileName: store.fileName, error: store.error, tool: store.report?.tool ?? null, clear: () => store.clear() }));

const site = useSiteStore();
const { label, fromLabel } = useCurrentVersion();

// The scanner records the installed version; when it names a version we know and it is not the current "from", offer it.
const suggestedFrom = computed(() => {
	const wanted = store.report?.fromVersion;

	if (!wanted || !label.value) {
		return null;
	}

	const bare = wanted.replace(/^v/i, "");
	const match = site.versions.find((v) => v.tag === wanted || v.tag === bare || v.label === wanted || v.label === `v${bare}`);

	return match && match.label !== fromLabel.value && match.label !== label.value ? match : null;
});

async function onFile(event: Event): Promise<void> {
	const input = event.target as HTMLInputElement;
	const file = input.files?.[0];

	if (!file) {
		return;
	}

	store.loadText(await file.text(), file.name);
	emit("update:importsOnly", store.report !== null);
	input.value = "";
}
</script>

<style>
.scan-loader {
	display: flex;
	flex-direction: column;
	gap: 6px;
	font-size: var(--text-meta);
	color: var(--color-ink3);
}

.scan-loader__toggle {
	display: flex;
	align-items: flex-start;
	gap: 8px;
	line-height: 1.45;
}

.scan-loader__toggle input {
	margin-top: 3px;
}

.scan-loader__toggle.is-disabled {
	color: var(--color-muted);
}

.scan-loader__hint {
	color: var(--color-muted);
}

.scan-loader__actions {
	display: flex;
	gap: 14px;
	padding-left: 22px;
}

.scan-loader__link {
	color: var(--color-accent);
	cursor: pointer;
	font-size: var(--text-label);
	min-height: 24px;
}

.scan-loader__link:hover {
	text-decoration: underline;
}

.scan-loader__error {
	padding-left: 22px;
	color: var(--color-warn-text);
	font-size: var(--text-label);
}

.scan-loader__note {
	padding-left: 22px;
	color: var(--color-faint);
	font-size: 11px;
	line-height: 1.5;
}

.mono {
	font-family: var(--font-mono);
}
</style>
