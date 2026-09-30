<template>
	<div class="context-bar" role="group" :aria-label="isUpgrade ? 'Upgrade context' : 'Your context'">
		<span class="label context-bar__label">{{ isUpgrade ? "Upgrading" : "Your context" }}</span>
		<RangePicker v-if="isUpgrade && fromLabel && label" ref="rangePicker" :from="fromLabel" :to="label" />
		<ContextPicker
			v-else
			ref="versionPicker"
			:model-value="controls.versionValue.value"
			:options="controls.versionOptions.value"
			label="Library version"
			@update:model-value="controls.selectVersion"
		/>
		<ContextPicker
			v-if="controls.languageOptions.value.length > 0"
			:model-value="controls.languageValue.value"
			:options="controls.languageOptions.value"
			label="Language"
			@update:model-value="controls.selectLanguage"
		/>
		<ContextPicker
			v-if="controls.packageManagerOptions.value.length > 0"
			:model-value="controls.packageManagerValue.value"
			:options="controls.packageManagerOptions.value"
			label="Package manager"
			@update:model-value="controls.selectPackageManager"
		/>
	</div>
</template>

<script setup lang="ts">
import { ref } from "vue";

import ContextPicker from "components/header/ContextPicker.vue";
import RangePicker from "components/header/RangePicker.vue";
import { useContextControls } from "composables/useContextControls";
import { useCurrentVersion } from "composables/useCurrentVersion";

const controls = useContextControls();
const isUpgrade = controls.isUpgrade;
const { label, fromLabel } = useCurrentVersion();
const versionPicker = ref<InstanceType<typeof ContextPicker> | null>(null);
const rangePicker = ref<InstanceType<typeof RangePicker> | null>(null);

defineExpose({ focusVersion: () => (rangePicker.value ?? versionPicker.value)?.focus() });
</script>

<style>
.context-bar {
	display: flex;
	align-items: center;
	gap: 8px;
}
</style>
