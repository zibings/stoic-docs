<template>
	<Select
		ref="select"
		:model-value="modelValue"
		:options="options"
		option-label="label"
		option-value="key"
		:aria-label="label"
		:placeholder="placeholder"
		:pt="pt"
		:unstyled="true"
		scroll-height="320px"
		@update:model-value="onChange"
	>
		<template #value="{ value }">
			<span class="ctx-select__value">{{ displayFor(value) }}</span>
		</template>
		<template #dropdownicon>
			<ChevronDownIcon class="ctx-select__chevron" />
		</template>
	</Select>
</template>

<script setup lang="ts">
import Select from "primevue/select";
import { ref } from "vue";

import ChevronDownIcon from "components/icons/ChevronDownIcon.vue";
import type { PickerOption } from "composables/useContextControls";

const props = withDefaults(
	defineProps<{
		modelValue: string | null;
		options: PickerOption[];
		/** Accessible name of the picker, forwarded as aria-label. */
		label: string;
		placeholder?: string;
		/** Overrides the rendered value text (used for the version range on the upgrade view). */
		display?: string | null;
	}>(),
	{ placeholder: "—", display: null },
);

const emit = defineEmits<{ (e: "update:modelValue", value: string | null): void }>();
const select = ref<InstanceType<typeof Select> | null>(null);

/** Moves keyboard focus to the picker (used by the ⌘. shortcut). */
function focus(): void {
	const el = (select.value as unknown as { $el?: HTMLElement } | null)?.$el ?? null;

	el?.focus();
}

defineExpose({ focus });

function onChange(value: unknown): void {
	emit("update:modelValue", typeof value === "string" ? value : null);
}

function displayFor(value: unknown): string {
	if (props.display) {
		return props.display;
	}

	return props.options.find((o) => o.key === value)?.label ?? props.placeholder;
}

// Pass-through classes: PrimeVue supplies the behavior, these classes supply every visual.
const pt = {
	root: { class: "ctx-select control" },
	label: { class: "ctx-select__label" },
	dropdown: { class: "ctx-select__dropdown" },
	overlay: { class: "ctx-select__overlay" },
	listContainer: { class: "ctx-select__list-container" },
	list: { class: "ctx-select__list" },
	option: ({ context }: { context: { selected?: boolean; focused?: boolean } }) => ({
		class: ["ctx-select__option", { "is-selected": context.selected, "is-focused": context.focused }],
	}),
	optionLabel: { class: "ctx-select__option-label" },
	emptyMessage: { class: "ctx-select__empty" },
};
</script>

<style>
/* Unscoped on purpose: the overlay is portaled to <body>. */
.ctx-select {
	position: relative;
	height: 34px;
	padding: 0 8px 0 10px;
	gap: 6px;
	cursor: pointer;
	user-select: none;
}

.ctx-select__label {
	display: flex;
	align-items: center;
	min-width: 0;
	white-space: nowrap;
}

.ctx-select__dropdown {
	display: flex;
	align-items: center;
	color: var(--color-muted);
}

.ctx-select__overlay {
	min-width: 180px;
	margin-top: 4px;
	background: var(--color-surface);
	border: 1px solid var(--color-dialog-border);
	border-radius: var(--radius-card);
	box-shadow: var(--shadow-popover);
	overflow: hidden;
	z-index: 50;
}

.ctx-select__list-container {
	overflow: auto;
}

.ctx-select__list {
	list-style: none;
	margin: 0;
	padding: 6px;
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.ctx-select__option {
	display: flex;
	align-items: center;
	min-height: 36px;
	padding: 0 10px;
	border-radius: var(--radius-control);
	font-family: var(--font-mono);
	font-size: var(--text-meta);
	color: var(--color-ink);
	cursor: pointer;
}

.ctx-select__option.is-focused {
	background: var(--color-chip);
}

.ctx-select__option.is-selected {
	background: var(--color-accent-tint);
	color: var(--color-accent-strong);
	font-weight: 600;
}

.ctx-select__empty {
	padding: 10px 12px;
	font-size: var(--text-small);
	color: var(--color-muted);
}
</style>
