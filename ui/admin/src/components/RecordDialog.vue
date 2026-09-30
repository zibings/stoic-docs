<template>
	<Dialog :visible="visible" modal :header="header" :style="{ width: width }" :draggable="false" @update:visible="emit('update:visible', $event)">
		<form class="stack" @submit.prevent="emit('save')">
			<Message v-if="errors._" severity="error" :closable="false">{{ errors._ }}</Message>
			<div class="form">
				<slot />
			</div>
			<div class="row record-dialog__foot">
				<Button label="Cancel" severity="secondary" outlined type="button" @click="emit('update:visible', false)" />
				<Button :label="saveLabel" type="submit" :loading="saving" />
			</div>
		</form>
	</Dialog>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Message from "primevue/message";

import type { Errors } from "lib/forms";

withDefaults(defineProps<{ visible: boolean; header: string; saving?: boolean; errors: Errors; saveLabel?: string; width?: string }>(), { saving: false, saveLabel: "Save", width: "640px" });

const emit = defineEmits<{ (e: "update:visible", value: boolean): void; (e: "save"): void }>();
</script>

<style>
.record-dialog__foot {
	justify-content: flex-end;
	padding-top: 6px;
}
</style>
