import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { ref } from "vue";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";
import { blank, toPayload, validate } from "lib/forms";
import type { Errors } from "lib/forms";
import type { RecordTypes, Resource } from "types/admin";

/**
 * List + create/edit dialog + delete for one resource. Views supply the form fields; this handles loading, saving,
 * validation, confirmation, and toasts.
 */
export function useResource<R extends Resource>(resource: R, filters: () => Record<string, string | number | null | undefined> = () => ({}), afterChange?: () => Promise<void> | void) {
	type Row = RecordTypes[R];

	const toast = useToast();
	const confirm = useConfirm();
	const rows = ref<Row[]>([]) as { value: Row[] };
	const loading = ref(false);
	const dialogOpen = ref(false);
	const saving = ref(false);
	const editing = ref<Record<string, unknown>>(blank(resource));
	const errors = ref<Errors>({});
	const isNew = ref(true);

	async function load(): Promise<void> {
		loading.value = true;

		try {
			rows.value = await adminApi.list(resource, filters());
		} catch (error) {
			toast.add({ severity: "error", summary: "Could not load", detail: errorMessage(error), life: 6000 });
		} finally {
			loading.value = false;
		}
	}

	function startCreate(defaults: Record<string, unknown> = {}): void {
		editing.value = { ...blank(resource), ...defaults };
		errors.value = {};
		isNew.value = true;
		dialogOpen.value = true;
	}

	function startEdit(row: Row): void {
		editing.value = { ...(row as unknown as Record<string, unknown>) };
		errors.value = {};
		isNew.value = false;
		dialogOpen.value = true;
	}

	async function save(): Promise<Row | null> {
		errors.value = validate(resource, editing.value);

		if (Object.keys(errors.value).length > 0) {
			return null;
		}

		saving.value = true;

		try {
			const payload = toPayload(editing.value) as Partial<Row>;
			const saved = isNew.value ? await adminApi.create(resource, payload) : await adminApi.update(resource, Number(editing.value.id), payload);

			dialogOpen.value = false;
			toast.add({ severity: "success", summary: isNew.value ? "Created" : "Saved", life: 2500 });
			await load();
			await afterChange?.();

			return saved;
		} catch (error) {
			errors.value = { _: errorMessage(error) };

			return null;
		} finally {
			saving.value = false;
		}
	}

	function remove(row: Row, describe: (row: Row) => string): void {
		confirm.require({
			message: `Delete ${describe(row)}? Anything that belongs to it is deleted too.`,
			header: "Delete",
			icon: "pi pi-exclamation-triangle",
			acceptProps: { label: "Delete", severity: "danger" },
			rejectProps: { label: "Cancel", severity: "secondary", outlined: true },
			accept: async () => {
				try {
					await adminApi.remove(resource, (row as { id: number }).id);
					toast.add({ severity: "success", summary: "Deleted", life: 2500 });
					await load();
					await afterChange?.();
				} catch (error) {
					toast.add({ severity: "error", summary: "Could not delete", detail: errorMessage(error), life: 8000 });
				}
			},
		});
	}

	return { rows, loading, load, dialogOpen, saving, editing, errors, isNew, startCreate, startEdit, save, remove };
}
