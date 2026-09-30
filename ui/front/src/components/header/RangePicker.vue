<template>
	<div class="range-picker">
		<button ref="trigger" type="button" class="control range-picker__button" aria-label="Upgrade range" :aria-expanded="open" aria-haspopup="dialog" @click="toggle">
			<span>{{ display }}</span>
			<ChevronDownIcon />
		</button>
		<Popover ref="popover" :pt="pt" :unstyled="true" append-to="body" @show="open = true" @hide="open = false">
			<div class="range-picker__field">
				<span class="label">From</span>
				<ContextPicker :model-value="from" :options="fromOptions" label="Upgrade from version" @update:model-value="onFrom" />
			</div>
			<div class="range-picker__field">
				<span class="label">To</span>
				<ContextPicker :model-value="to" :options="toOptions" label="Upgrade to version" @update:model-value="onTo" />
			</div>
		</Popover>
	</div>
</template>

<script setup lang="ts">
import Popover from "primevue/popover";
import { computed, ref } from "vue";
import { useRouter } from "vue-router";

import ContextPicker from "components/header/ContextPicker.vue";
import ChevronDownIcon from "components/icons/ChevronDownIcon.vue";
import type { PickerOption } from "composables/useContextControls";
import { useSiteStore } from "stores/site";

const props = defineProps<{ from: string; to: string }>();

const router = useRouter();
const site = useSiteStore();
const popover = ref<InstanceType<typeof Popover> | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const open = ref(false);

const display = computed(() => `${props.from} → ${props.to}`);

const toVersion = computed(() => site.versionByLabel(props.to));
const fromVersion = computed(() => site.versionByLabel(props.from));

// From must be older than To; offer only valid combinations.
const fromOptions = computed<PickerOption[]>(() =>
	site.newestFirst.filter((v) => !toVersion.value || v.sortKey < toVersion.value.sortKey).map((v) => ({ key: v.label, label: v.label })),
);
const toOptions = computed<PickerOption[]>(() =>
	site.newestFirst.filter((v) => !fromVersion.value || v.sortKey > fromVersion.value.sortKey).map((v) => ({ key: v.label, label: v.isLatest ? `${v.label} · latest` : v.label })),
);

function toggle(event: Event): void {
	popover.value?.toggle(event);
}

function onFrom(label: string | null): void {
	if (label && label !== props.from) {
		router.push({ name: "upgrade", params: { from: label, to: props.to } });
	}
}

function onTo(label: string | null): void {
	if (label && label !== props.to) {
		router.push({ name: "upgrade", params: { from: props.from, to: label } });
	}
}

const pt = {
	root: { class: "ctx-popover" },
	content: { class: "ctx-popover__content" },
};

defineExpose({ focus: () => trigger.value?.focus() });
</script>

<style>
.range-picker__button {
	height: 34px;
	gap: 8px;
}

.range-picker__field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.range-picker__field .ctx-select {
	width: 100%;
}
</style>
