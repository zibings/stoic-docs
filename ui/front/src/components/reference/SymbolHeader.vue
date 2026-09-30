<template>
	<header class="symbol-header">
		<nav class="symbol-header__crumb mono" aria-label="Breadcrumb">{{ breadcrumb.join(" / ") }}</nav>
		<h1 class="symbol-header__name" :class="{ removed: node.state === 'removed' }">{{ node.shortName }}</h1>
		<p v-if="node.contract.summary" class="symbol-header__summary">{{ node.contract.summary }}</p>
		<div class="symbol-header__pills">
			<span v-if="node.sinceLabel" class="pill pill--accent">since {{ node.sinceLabel }}</span>
			<span v-if="node.changedLabel" class="pill pill--warn">changed {{ node.changedLabel }}</span>
			<span v-if="node.removedLabel" class="pill pill--warn">removed {{ node.removedLabel }}</span>
			<span class="pill">{{ node.contract.status }}</span>
			<span class="pill">{{ node.kind }}</span>
		</div>
	</header>
</template>

<script setup lang="ts">
import type { SymbolNode } from "types/docs";

defineProps<{ node: SymbolNode; breadcrumb: string[] }>();
</script>

<style>
.symbol-header {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.symbol-header__crumb {
	font-size: var(--text-meta);
	color: var(--color-muted);
}

.symbol-header__name {
	font-family: var(--font-mono);
	font-weight: 600;
	font-size: var(--text-symbol-title);
	letter-spacing: -0.02em;
	line-height: 1.15;
	overflow-wrap: anywhere;
}

.symbol-header__summary {
	font-family: var(--font-serif);
	font-size: var(--text-summary);
	line-height: 1.4;
	color: var(--color-ink2);
	max-width: 720px;
}

.symbol-header__pills {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
	margin-top: 4px;
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 768px) {
	.symbol-header__name {
		font-size: var(--text-symbol-title-mobile);
	}

	.symbol-header__summary {
		font-size: var(--text-summary-mobile);
	}
}
</style>
