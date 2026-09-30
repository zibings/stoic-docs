<template>
	<article class="change-detail" :aria-labelledby="`change-title-${change.id}`">
		<header class="change-detail__head">
			<div class="change-detail__badges">
				<span class="pill" :class="kindPill(change.kind)">{{ change.kind }}</span>
				<span class="pill mono">{{ change.versionLabel }}</span>
				<span v-if="change.hasCodemod" class="pill pill--accent">codemod available</span>
				<span v-if="isReviewed" class="pill pill--accent">reviewed</span>
			</div>
			<h1 :id="`change-title-${change.id}`" class="change-detail__title">{{ change.title }}</h1>
			<p v-if="change.why" class="change-detail__why">
				{{ change.why }}
				<a v-if="change.rfcUrl" :href="change.rfcUrl" target="_blank" rel="noopener">{{ rfcLabel }}</a>
			</p>
			<div v-if="affected" class="change-detail__affected">Affects {{ affected.callSites }} call site{{ affected.callSites === 1 ? "" : "s" }} in {{ affected.files }} file{{ affected.files === 1 ? "" : "s" }}</div>
		</header>

		<div v-if="change.beforeCode || change.afterCode" class="before-after">
			<div class="before-after__col before-after__col--before">
				<div class="before-after__bar before-after__bar--before">{{ from }} · you read this</div>
				<pre class="before-after__code"><code class="hljs" v-html="highlight(change.beforeCode ?? '', language)" /></pre>
			</div>
			<div class="before-after__col">
				<div class="before-after__bar before-after__bar--after">{{ to }} · the same call now</div>
				<pre class="before-after__code"><code class="hljs" v-html="highlight(change.afterCode ?? '', language)" /></pre>
			</div>
		</div>

		<div v-for="block in blocks" :key="block.ref" class="contract-diff pane">
			<div class="contract-diff__bar">Contract diff · {{ block.name }}</div>
			<pre class="contract-diff__pre"><template v-for="(line, i) in block.lines" :key="i"><span class="contract-diff__line" :class="{ 'is-minus': line.kind === '-', 'is-plus': line.kind === '+' }">{{ line.kind === " " ? "  " : line.kind + " " }} {{ line.text }}</span>
</template></pre>
		</div>

		<section v-if="change.codemodCmd" class="card codemod">
			<div class="codemod__label">Migrate automatically</div>
			<div class="codemod__cmd-row">
				<pre class="codemod__cmd"><code>{{ change.codemodCmd }}</code></pre>
				<button type="button" class="codemod__copy" @click="copyCodemod">{{ copied ? "Copied" : "Copy" }}</button>
			</div>
			<p class="codemod__note">Run it from your project root, then review the changes it makes before committing.</p>
		</section>

		<footer class="change-detail__foot">
			<button type="button" class="btn btn--primary" @click="emit('review-next')">{{ isReviewed ? "Next change" : "Mark reviewed · next change" }}</button>
			<RouterLink v-if="firstSymbol" :to="firstSymbol.route" class="btn">Open {{ firstSymbol.name }} at {{ to }}</RouterLink>
			<span class="change-detail__keys mono" aria-hidden="true">J / K move · R reviewed</span>
		</footer>
	</article>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { RouterLink } from "vue-router";

import { highlight } from "markdown/highlight";
import type { ScanReport } from "upgrade/diff";
import { affectedSummary, contractDiffBlocks } from "upgrade/diff";
import type { DiffChange } from "types/docs";

const props = defineProps<{
	change: DiffChange;
	from: string;
	to: string;
	language: string | null;
	isReviewed: boolean;
	report: ScanReport | null;
}>();

const emit = defineEmits<{ (e: "review-next"): void }>();

const blocks = computed(() => contractDiffBlocks(props.change));
const affected = computed(() => affectedSummary(props.change, props.report));
const firstSymbol = computed(() => props.change.symbols[0] ?? null);
const rfcLabel = computed(() => {
	const url = props.change.rfcUrl ?? "";
	const match = /(\d+)\/?$/.exec(url);

	return match ? `RFC #${match[1]}` : "Read the discussion";
});

function kindPill(kind: string): string {
	if (kind === "breaking" || kind === "removed" || kind === "deprecated") {
		return "pill--warn";
	}

	return kind === "added" ? "pill--accent" : "";
}

const copied = ref(false);

async function copyCodemod(): Promise<void> {
	try {
		await navigator.clipboard.writeText(props.change.codemodCmd ?? "");
		copied.value = true;
		window.setTimeout(() => (copied.value = false), 1500);
	} catch {
		// Clipboard unavailable; the command is still selectable text.
	}
}
</script>

<style>
.change-detail {
	display: flex;
	flex-direction: column;
	gap: 22px;
	padding: 32px 44px 48px;
	min-width: 0;
}

.change-detail__head {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.change-detail__badges {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
}

.change-detail__title {
	font-family: var(--font-serif);
	font-weight: 500;
	font-size: 32px;
	line-height: 1.2;
	overflow-wrap: anywhere;
}

.change-detail__why {
	color: var(--color-ink2);
	max-width: 720px;
}

.change-detail__affected {
	font-size: var(--text-meta);
	color: var(--color-muted);
}

.before-after {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	border: 1px solid var(--color-rule);
	border-radius: var(--radius-card);
	background: var(--color-surface);
	overflow: hidden;
}

.before-after__col--before {
	border-right: 1px solid var(--color-rule);
}

.before-after__bar {
	padding: 8px 16px;
	border-bottom: 1px solid var(--color-rule-soft);
	font-family: var(--font-mono);
	font-size: var(--text-label);
}

.before-after__bar--before {
	color: var(--color-warn-text);
	background: var(--color-warn-card-bg);
}

.before-after__bar--after {
	color: var(--color-accent-strong);
	background: var(--color-accent-card-bg);
}

.before-after__code {
	padding: 14px 16px;
	font-size: var(--text-meta);
	line-height: 1.7;
	overflow-x: auto;
}

.contract-diff {
	border-radius: var(--radius-card);
	background: var(--pane-bg);
	color: var(--pane-text);
	overflow: hidden;
}

.contract-diff__bar {
	padding: 8px 16px;
	border-bottom: 1px solid var(--pane-rule);
	font-family: var(--font-mono);
	font-size: var(--text-label);
	letter-spacing: 0.06em;
	text-transform: uppercase;
	color: var(--pane-muted);
}

.contract-diff__pre {
	padding: 14px 16px;
	font-size: var(--text-meta);
	line-height: 1.7;
	overflow-x: auto;
}

.contract-diff__line.is-minus {
	color: var(--pane-warn);
}

.contract-diff__line.is-plus {
	color: var(--pane-link);
}

.codemod {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.codemod__label {
	font-family: var(--font-mono);
	font-size: var(--text-label);
	font-weight: 600;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: var(--color-muted);
}

.codemod__cmd-row {
	display: flex;
	align-items: stretch;
	gap: 8px;
}

.codemod__cmd {
	flex: 1 1 auto;
	min-width: 0;
	padding: 10px 12px;
	border-radius: var(--radius-control);
	background: var(--color-ground);
	font-size: var(--text-code);
	overflow-x: auto;
}

.codemod__copy {
	padding: 0 12px;
	border: 1px solid var(--color-control-border);
	border-radius: var(--radius-control);
	background: var(--color-surface);
	color: var(--color-accent);
	font-family: var(--font-mono);
	font-size: var(--text-label);
}

.codemod__note {
	font-size: var(--text-small);
	color: var(--color-ink2);
}

.change-detail__foot {
	margin-top: auto;
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
	padding-top: 18px;
	border-top: 1px solid var(--color-rule);
}

.btn {
	display: inline-flex;
	align-items: center;
	min-height: var(--touch-target);
	padding: 0 18px;
	border-radius: var(--radius-card);
	border: 1px solid var(--color-control-border);
	background: var(--color-surface);
	color: var(--color-ink);
	font-size: var(--text-small);
	text-decoration: none;
}

.btn:hover {
	text-decoration: none;
	border-color: var(--color-faint);
}

.btn--primary {
	border-color: var(--color-ink);
	background: var(--color-ink);
	color: var(--color-surface);
	font-weight: 500;
}

.btn--primary:hover {
	background: var(--color-ink2);
	border-color: var(--color-ink2);
}

.change-detail__keys {
	margin-left: auto;
	font-size: var(--text-label);
	color: var(--color-muted);
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 768px) {
	.change-detail {
		padding: 20px 18px 40px;
	}

	.before-after {
		grid-template-columns: minmax(0, 1fr);
	}

	.before-after__col--before {
		border-right: 0;
		border-bottom: 1px solid var(--color-rule);
	}

	.change-detail__keys {
		display: none;
	}
}
</style>
