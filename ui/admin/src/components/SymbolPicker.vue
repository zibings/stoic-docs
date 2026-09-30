<template>
	<AutoComplete
		:model-value="selected"
		:suggestions="suggestions"
		option-label="ref"
		:placeholder="placeholder"
		:input-id="inputId"
		dropdown
		force-selection
		fluid
		@complete="onComplete"
		@update:model-value="onSelect"
	>
		<template #option="{ option }">
			<span class="mono">{{ option.ref }}</span>
			<span class="muted" style="margin-left: 8px">{{ option.kind }}</span>
		</template>
	</AutoComplete>
</template>

<script setup lang="ts">
import AutoComplete from "primevue/autocomplete";
import { computed, ref } from "vue";

import { useCatalogStore } from "stores/catalog";
import type { AdminSymbol } from "types/admin";

const props = withDefaults(defineProps<{ modelValue: number | null; placeholder?: string; inputId?: string }>(), { placeholder: "module/path#Name" });
const emit = defineEmits<{ (e: "update:modelValue", value: number | null): void }>();

const catalog = useCatalogStore();
const suggestions = ref<AdminSymbol[]>([]);
const selected = computed(() => catalog.symbols.find((s) => s.id === props.modelValue) ?? null);

function onComplete(event: { query: string }): void {
	const needle = event.query.trim().toLowerCase();

	suggestions.value = catalog.symbols.filter((s) => !needle || s.ref.toLowerCase().includes(needle)).slice(0, 30);
}

function onSelect(value: unknown): void {
	emit("update:modelValue", value && typeof value === "object" && "id" in value ? (value as AdminSymbol).id : null);
}
</script>
