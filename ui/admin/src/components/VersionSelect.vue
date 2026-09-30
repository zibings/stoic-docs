<template>
	<Select :model-value="modelValue" :options="options" option-label="label" option-value="id" :placeholder="placeholder" :show-clear="clearable" :input-id="inputId" fluid @update:model-value="emit('update:modelValue', $event ?? null)" />
</template>

<script setup lang="ts">
import Select from "primevue/select";
import { computed } from "vue";

import { useCatalogStore } from "stores/catalog";

withDefaults(defineProps<{ modelValue: number | null; placeholder?: string; clearable?: boolean; inputId?: string }>(), { placeholder: "Pick a version", clearable: false });

const emit = defineEmits<{ (e: "update:modelValue", value: number | null): void }>();

const catalog = useCatalogStore();
const options = computed(() => catalog.versionsNewestFirst.map((v) => ({ id: v.id, label: v.isLatest ? `${v.label} (latest)` : v.label })));
</script>
