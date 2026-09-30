<template>
	<slot v-if="mounted" />
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";

// Renders its content only in the browser, after mount. Used for pieces that depend on viewport or browser APIs
// (the mobile contract sheet, for instance) so pre-rendered markup stays deterministic.
const mounted = ref(false);

onMounted(() => {
	mounted.value = true;
});
</script>
