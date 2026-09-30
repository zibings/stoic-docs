<template>
	<div class="sheet pane">
		<button type="button" class="sheet__handle" aria-label="Expand workspace" :aria-expanded="open" @click="open = true">
			<span class="sheet__grip" />
		</button>
		<div class="sheet__peek" @click="open = true">
			<div class="sheet__peek-head">
				<span class="pane__label">Workspace</span>
				<span class="pane__label">{{ step ? `step ${step.ordinal}` : "" }}</span>
			</div>
			<div class="sheet__peek-row">
				<span class="sheet__peek-name">{{ tabs[0]?.title ?? "No files" }}</span>
				<span class="sheet__peek-type">{{ stepDone ? "passed" : "tap to open" }}</span>
			</div>
		</div>

		<Drawer v-model:visible="open" position="bottom" :modal="true" :block-scroll="true" :show-close-icon="false" :pt="pt" :unstyled="true" append-to="body">
			<template #container="{ closeCallback }">
				<div class="sheet__panel pane" role="dialog" aria-label="Workspace">
					<span class="sheet__grip sheet__grip--panel" aria-hidden="true" />
					<div class="sheet__panel-head">
						<span class="pane__label">Workspace</span>
						<button type="button" class="icon-button sheet__close" aria-label="Close workspace" @click="closeCallback">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
						</button>
					</div>
					<div class="sheet__panel-body">
						<Workspace :tabs="tabs" :step="step" :next-step="nextStep" :step-done="stepDone" :all-done="allDone" compact @done="emit('done')" @undo="emit('undo')" />
					</div>
				</div>
			</template>
		</Drawer>
	</div>
</template>

<script setup lang="ts">
import Drawer from "primevue/drawer";
import { ref } from "vue";

import Workspace from "components/learn/Workspace.vue";
import type { CodeSample, CourseStep } from "types/docs";

defineProps<{ tabs: CodeSample[]; step: CourseStep | null; nextStep: CourseStep | null; stepDone: boolean; allDone: boolean }>();
const emit = defineEmits<{ (e: "done"): void; (e: "undo"): void }>();

const open = ref(false);
const pt = { mask: { class: "sheet__mask" }, root: { class: "sheet__drawer" } };
</script>
