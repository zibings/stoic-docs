<template>
	<div class="mobile-context">
		<button
			ref="trigger"
			type="button"
			class="control mobile-context__chip"
			aria-label="Change context"
			:aria-expanded="open"
			aria-haspopup="dialog"
			@click="toggle"
		>
			<span class="mobile-context__summary">{{ controls.summary.value }}</span>
			<ChevronDownIcon />
		</button>
		<Popover ref="popover" :pt="pt" :unstyled="true" append-to="body" @show="open = true" @hide="open = false">
			<template v-if="controls.isUpgrade.value && fromLabel && label">
				<div class="mobile-context__field">
					<span class="label">Upgrading from</span>
					<ContextPicker :model-value="fromLabel" :options="fromOptions" label="Upgrade from version" @update:model-value="onFrom" />
				</div>
				<div class="mobile-context__field">
					<span class="label">To</span>
					<ContextPicker :model-value="label" :options="controls.versionOptions.value" label="Upgrade to version" @update:model-value="controls.selectVersion" />
				</div>
			</template>
			<div v-else class="mobile-context__field">
				<span class="label">Version</span>
				<ContextPicker :model-value="controls.versionValue.value" :options="controls.versionOptions.value" label="Library version" @update:model-value="controls.selectVersion" />
			</div>
			<div v-if="controls.languageOptions.value.length > 0" class="mobile-context__field">
				<span class="label">Language</span>
				<ContextPicker :model-value="controls.languageValue.value" :options="controls.languageOptions.value" label="Language" @update:model-value="controls.selectLanguage" />
			</div>
			<div v-if="controls.packageManagerOptions.value.length > 0" class="mobile-context__field">
				<span class="label">Package manager</span>
				<ContextPicker
					:model-value="controls.packageManagerValue.value"
					:options="controls.packageManagerOptions.value"
					label="Package manager"
					@update:model-value="controls.selectPackageManager"
				/>
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
import { useContextControls } from "composables/useContextControls";
import type { PickerOption } from "composables/useContextControls";
import { useCurrentVersion } from "composables/useCurrentVersion";
import { useSiteStore } from "stores/site";

const controls = useContextControls();
const router = useRouter();
const site = useSiteStore();
const { label, fromLabel } = useCurrentVersion();

const fromOptions = computed<PickerOption[]>(() => {
	const to = label.value ? site.versionByLabel(label.value) : undefined;

	return site.newestFirst.filter((v) => !to || v.sortKey < to.sortKey).map((v) => ({ key: v.label, label: v.label }));
});

function onFrom(next: string | null): void {
	if (next && label.value && next !== fromLabel.value) {
		router.push({ name: "upgrade", params: { from: next, to: label.value } });
	}
}
const popover = ref<InstanceType<typeof Popover> | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const open = ref(false);

function toggle(event: Event): void {
	popover.value?.toggle(event);
}

/** Opens the popover anchored to the chip (used by the ⌘. shortcut on small screens). */
function openPopover(): void {
	if (trigger.value && !open.value) {
		popover.value?.show({ currentTarget: trigger.value } as unknown as Event, trigger.value);
	}
}

defineExpose({ open: openPopover });

const pt = {
	root: { class: "ctx-popover" },
	content: { class: "ctx-popover__content" },
};
</script>

<style>
.mobile-context__chip {
	width: 100%;
	height: 36px;
	justify-content: space-between;
	padding: 0 12px;
}

.mobile-context__summary {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.ctx-popover {
	margin-top: 6px;
	background: var(--color-surface);
	border: 1px solid var(--color-dialog-border);
	border-radius: var(--radius-dialog);
	box-shadow: 0 12px 32px rgba(22, 24, 29, 0.16);
	z-index: 50;
}

.ctx-popover__content {
	display: flex;
	flex-direction: column;
	gap: 14px;
	padding: 16px;
	min-width: 260px;
}

.mobile-context__field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.mobile-context__field .ctx-select {
	width: 100%;
	height: var(--touch-target);
}
</style>
