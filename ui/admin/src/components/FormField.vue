<template>
	<div class="field" :class="span ? `field--${span}` : ''">
		<label :for="id">{{ label }}<span v-if="required" aria-hidden="true"> *</span></label>
		<slot :id="id" />
		<span v-if="hint && !error" class="field__hint">{{ hint }}</span>
		<span v-if="error" class="field__error" role="alert">{{ error }}</span>
	</div>
</template>

<script setup lang="ts">
import { useId } from "vue";

defineProps<{ label: string; span?: 2 | 3 | 4 | 6 | 8 | 9 | 12; required?: boolean; hint?: string; error?: string }>();

const id = useId();
</script>
