<template>
	<div class="page">
		<div class="page__head">
			<div>
				<p class="label"><RouterLink to="/courses">Courses</RouterLink></p>
				<h1 class="page__title">{{ course?.title ?? "…" }}</h1>
				<p v-if="course?.summary" class="page__subtitle">{{ course.summary }}</p>
			</div>
		</div>

		<section class="card stack">
			<div class="resource-table__head">
				<div>
					<h2 class="resource-table__title">Lessons</h2>
					<p class="muted">Drag to reorder; each lesson is a Learn page.</p>
				</div>
				<div class="row">
					<Select v-model="newPageId" :options="learnPages" option-label="title" option-value="id" placeholder="Add a Learn page…" filter size="small" style="min-width: 260px" />
					<Button label="Add lesson" icon="pi pi-plus" size="small" :disabled="!newPageId" @click="addLesson" />
				</div>
			</div>
			<OrderList v-model="lessonRows" data-key="id" :list-style="{ 'max-height': '420px' }" @reorder="reorderLessons">
				<template #option="{ option }">
					<div class="row lesson-row">
						<span class="mono muted">{{ option.ordinal }}</span>
						<span style="flex: 1 1 auto">{{ pageTitle(option.pageId) }}</span>
						<Button :label="`Steps (${stepCount(option.id)})`" text size="small" @click.stop="openSteps(option)" />
						<Button icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Remove lesson" @click.stop="removeLesson(option)" />
					</div>
				</template>
			</OrderList>
		</section>

		<section v-if="activeLesson" class="card stack">
			<div class="resource-table__head">
				<div>
					<h2 class="resource-table__title">Steps · {{ pageTitle(activeLesson.pageId) }}</h2>
					<p class="muted">"Try it" steps in order; hint and answer are revealed on request, expected output is what the workspace shows on success.</p>
				</div>
				<Button label="New step" icon="pi pi-plus" size="small" @click="steps.startCreate({ lessonId: activeLesson.id, ordinal: steps.rows.value.length + 1 })" />
			</div>
			<ResourceTable title="" :rows="steps.rows.value" :columns="stepColumns" :loading="steps.loading.value" sort-field="ordinal" :searchable="false" @edit="steps.startEdit($event)" @delete="steps.remove($event, (s) => `step ${s.ordinal}`)" />
		</section>

		<RecordDialog v-model:visible="steps.dialogOpen.value" :header="steps.isNew.value ? 'New step' : `Edit step ${steps.editing.value.ordinal}`" :saving="steps.saving.value" :errors="steps.errors.value" width="760px" @save="steps.save()">
			<FormField v-slot="{ id }" label="Ordinal" :span="3" required :error="steps.errors.value.ordinal">
				<InputNumber :id="id" v-model="(steps.editing.value.ordinal as number)" :use-grouping="false" :min="1" fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Prompt" :span="9" required :error="steps.errors.value.prompt">
				<Textarea :id="id" v-model="(steps.editing.value.prompt as string)" rows="2" auto-resize fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Hint" :span="6">
				<Textarea :id="id" :model-value="(steps.editing.value.hint as string | null) ?? ''" rows="2" auto-resize fluid @update:model-value="steps.editing.value.hint = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="Answer" :span="6">
				<Textarea :id="id" :model-value="(steps.editing.value.answer as string | null) ?? ''" rows="2" auto-resize fluid class="mono" @update:model-value="steps.editing.value.answer = $event || null" />
			</FormField>
			<FormField v-slot="{ id }" label="Expected output">
				<Textarea :id="id" :model-value="(steps.editing.value.expectedOutput as string | null) ?? ''" rows="2" auto-resize fluid class="mono" @update:model-value="steps.editing.value.expectedOutput = $event || null" />
			</FormField>
		</RecordDialog>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import OrderList from "primevue/orderlist";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";
import FormField from "components/FormField.vue";
import RecordDialog from "components/RecordDialog.vue";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";
import { useResource } from "composables/useResource";
import type { AdminCourse, AdminLesson, AdminPage } from "types/admin";

const props = defineProps<{ id: string }>();

const toast = useToast();
const confirm = useConfirm();
const courseId = computed(() => Number(props.id));
const course = ref<AdminCourse | null>(null);
const lessonRows = ref<AdminLesson[]>([]);
const pages = ref<AdminPage[]>([]);
const newPageId = ref<number | null>(null);
const activeLesson = ref<AdminLesson | null>(null);
const stepCounts = ref<Record<number, number>>({});

const steps = useResource("steps", () => ({ lessonId: activeLesson.value?.id ?? -1 }), async () => refreshStepCount());
const stepColumns: ColumnDef[] = [
	{ field: "ordinal", header: "#", kind: "mono", width: "6%" },
	{ field: "prompt", header: "Prompt" },
	{ field: "hint", header: "Hint", width: "22%" },
	{ field: "expectedOutput", header: "Expected output", width: "22%" },
];

const learnPages = computed(() => pages.value.filter((p) => p.mode === "learn" && !lessonRows.value.some((l) => l.pageId === p.id)));

function pageTitle(pageId: number): string {
	return pages.value.find((p) => p.id === pageId)?.title ?? `page #${pageId}`;
}

function stepCount(lessonId: number): number {
	return stepCounts.value[lessonId] ?? 0;
}

async function load(): Promise<void> {
	try {
		[course.value, lessonRows.value, pages.value] = await Promise.all([adminApi.get("courses", courseId.value), adminApi.list("lessons", { courseId: courseId.value }), adminApi.list("pages", { mode: "learn" })]);

		const allSteps = await adminApi.list("steps");
		const counts: Record<number, number> = {};

		for (const step of allSteps) {
			counts[step.lessonId] = (counts[step.lessonId] ?? 0) + 1;
		}

		stepCounts.value = counts;
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not load course", detail: errorMessage(err), life: 6000 });
	}
}

async function refreshStepCount(): Promise<void> {
	if (activeLesson.value) {
		stepCounts.value = { ...stepCounts.value, [activeLesson.value.id]: steps.rows.value.length };
	}
}

async function addLesson(): Promise<void> {
	if (!newPageId.value) {
		return;
	}

	try {
		await adminApi.create("lessons", { courseId: courseId.value, pageId: newPageId.value, ordinal: lessonRows.value.length + 1 });
		newPageId.value = null;
		lessonRows.value = await adminApi.list("lessons", { courseId: courseId.value });
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not add lesson", detail: errorMessage(err), life: 6000 });
	}
}

async function reorderLessons(): Promise<void> {
	try {
		lessonRows.value = await adminApi.reorder("lessons", lessonRows.value.map((l) => l.id));
	} catch (err) {
		toast.add({ severity: "error", summary: "Could not reorder", detail: errorMessage(err), life: 6000 });
	}
}

function removeLesson(lesson: AdminLesson): void {
	confirm.require({
		message: `Remove "${pageTitle(lesson.pageId)}" from this course? The page itself is kept; its steps are deleted.`,
		header: "Remove lesson",
		icon: "pi pi-exclamation-triangle",
		acceptProps: { label: "Remove", severity: "danger" },
		rejectProps: { label: "Cancel", severity: "secondary", outlined: true },
		accept: async () => {
			try {
				await adminApi.remove("lessons", lesson.id);

				if (activeLesson.value?.id === lesson.id) {
					activeLesson.value = null;
				}

				lessonRows.value = await adminApi.reorder("lessons", lessonRows.value.filter((l) => l.id !== lesson.id).map((l) => l.id));
			} catch (err) {
				toast.add({ severity: "error", summary: "Could not remove", detail: errorMessage(err), life: 6000 });
			}
		},
	});
}

async function openSteps(lesson: AdminLesson): Promise<void> {
	activeLesson.value = lesson;
	await steps.load();
}

watch(courseId, load);
onMounted(load);
</script>

<style>
.lesson-row {
	width: 100%;
	flex-wrap: nowrap;
}
</style>
