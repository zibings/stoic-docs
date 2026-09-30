<template>
	<Dialog
		:visible="visible"
		modal
		:closable="false"
		:close-on-escape="true"
		:dismissable-mask="true"
		:block-scroll="true"
		:draggable="false"
		append-to="body"
		:pt="pt"
		:unstyled="true"
		@update:visible="emit('update:visible', $event)"
	>
		<template #container>
			<div class="palette" :class="[`palette--${size}`]" role="document" @keydown="emit('keydown', $event)">
				<slot />
			</div>
		</template>
	</Dialog>
</template>

<script setup lang="ts">
import Dialog from "primevue/dialog";

const props = defineProps<{
	visible: boolean;
	/** Accessible name of the dialog. */
	label: string;
	size: "search" | "browse";
}>();

const emit = defineEmits<{
	(e: "update:visible", value: boolean): void;
	(e: "keydown", event: KeyboardEvent): void;
}>();

// PrimeVue supplies the modal behavior (focus trap, Escape, scroll lock, mask click); these classes supply the look.
const pt = {
	mask: { class: "palette-mask" },
	root: () => ({ class: "palette-root", "aria-label": props.label }),
};
</script>

<style>
.palette-mask {
	position: fixed;
	inset: 0;
	z-index: 40;
	display: flex;
	justify-content: center;
	align-items: flex-start;
	padding: 96px 16px 16px;
	background: rgba(217, 213, 204, 0.92);
}

.palette-root {
	display: flex;
	max-width: 100%;
	max-height: calc(100vh - 112px);
	max-height: calc(100dvh - 112px);
}

.palette {
	display: flex;
	flex-direction: column;
	width: 100%;
	max-height: calc(100vh - 112px);
	max-height: calc(100dvh - 112px);
	background: var(--color-surface);
	border: 1px solid var(--color-dialog-border);
	border-radius: var(--radius-dialog);
	overflow: hidden;
	color: var(--color-ink);
	font-size: var(--text-body);
}

.palette--search {
	width: 720px;
	max-width: 100%;
}

.palette--browse {
	width: 1000px;
	max-width: 100%;
	height: 680px;
	max-height: 100%;
}

.palette__footer {
	display: flex;
	gap: 20px;
	padding: 12px 20px;
	border-top: 1px solid var(--color-rule-soft);
	background: var(--color-surface-sunk);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-muted);
	flex-wrap: wrap;
}

.palette__footer .is-right {
	margin-left: auto;
}

.palette__scope {
	font-family: var(--font-mono);
	font-size: var(--text-label);
	color: var(--color-muted);
	white-space: nowrap;
}

@media (max-width: 768px) {
	.palette-mask {
		padding: 0;
		align-items: stretch;
	}

	.palette-root,
	.palette {
		max-height: 100vh;
		max-height: 100dvh;
	}

	.palette--search,
	.palette--browse {
		width: 100%;
		height: 100vh;
		height: 100dvh;
		border-radius: 0;
		border: 0;
	}

	.palette__footer {
		display: none;
	}
}
</style>
