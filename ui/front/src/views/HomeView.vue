<template>
	<div class="shell-content home">
		<section class="home-hero">
			<div class="label">
				{{ version }}<template v-if="releasedOn"> · released {{ releasedOn }}</template>
			</div>
			<h1 class="home-hero__title">{{ site.libraryName }}</h1>
			<p v-if="tagline" class="home-hero__tagline">{{ tagline }}</p>
			<p v-else-if="pageError" class="muted">Could not load the landing page: {{ pageError }}</p>

			<div class="home-hero__actions">
				<RouterLink :to="startRoute" class="home-cta home-cta--primary">{{ startLabel }}</RouterLink>
				<RouterLink :to="`/${version}/reference`" class="home-cta">Browse the reference</RouterLink>
				<a v-if="site.repoUrl" :href="site.repoUrl" class="home-cta" rel="noopener">Source</a>
			</div>
		</section>

		<!-- Optional prose from the home/index page: install lines, a first example, whatever the author wants under the hero. -->
		<ProseBody v-if="body" :body="body" :context="renderContext" class="home-prose" />

		<section class="home-section" aria-labelledby="home-modes-heading">
			<h2 id="home-modes-heading" class="label">Four ways in</h2>
			<p v-if="manifestError" class="muted">Could not load the page lists: {{ manifestError }}</p>
			<ul class="home-modes">
				<li v-for="entry in modeCards" :key="entry.mode" class="card home-mode">
					<div class="home-mode__head">
						<RouterLink :to="entry.route" class="home-mode__title">{{ entry.title }}</RouterLink>
						<span class="muted home-mode__count">{{ entry.count }}</span>
					</div>
					<p class="home-mode__blurb muted">{{ entry.blurb }}</p>
					<ul v-if="entry.links.length > 0" class="home-mode__links">
						<li v-for="link in entry.links" :key="link.route + link.title">
							<RouterLink :to="link.route" :class="{ mono: link.mono }">{{ link.title }}</RouterLink>
							<span v-if="link.meta" class="muted">{{ link.meta }}</span>
						</li>
					</ul>
					<p v-else class="muted home-mode__empty">Nothing here yet at {{ version }}.</p>
				</li>
			</ul>
		</section>

		<section v-if="previous" class="home-section" aria-labelledby="home-new-heading">
			<h2 id="home-new-heading" class="label">New in {{ version }}</h2>
			<div class="card home-new">
				<p v-if="diffError" class="muted">Could not load the changes since {{ previous.label }}: {{ diffError }}</p>
				<p v-else-if="!diff" class="muted">Loading…</p>
				<p v-else-if="diff.total === 0" class="muted">No recorded changes since {{ previous.label }}.</p>
				<template v-else>
					<div class="home-new__kinds">
						<span v-for="group in kindCounts" :key="group.kind" class="pill" :class="kindPillClass(group.kind)">{{ group.count }} {{ kindLabel(group.kind, group.count) }}</span>
					</div>
					<ul class="home-new__list">
						<li v-for="change in headlineChanges" :key="change.id">
							<span class="pill" :class="kindPillClass(change.kind)">{{ change.kind }}</span>
							<RouterLink :to="diff.route">{{ change.title }}</RouterLink>
						</li>
					</ul>
					<RouterLink :to="diff.route" class="home-new__link">Upgrade guide from {{ previous.label }} to {{ version }}</RouterLink>
				</template>
			</div>
		</section>
	</div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink } from "vue-router";

import { docsApi } from "api/docs";
import ProseBody from "components/prose/ProseBody.vue";
import { useDocsData } from "composables/useDocsData";
import { usePageMeta } from "composables/usePageMeta";
import { useProseContext } from "composables/useProseContext";
import { useSiteStore } from "stores/site";
import type { DiffChange, DocVersion, Mode } from "types/docs";

const props = defineProps<{ version: string }>();
const site = useSiteStore();

// The landing page composes four independent responses so every one of them pre-renders: the optional home/index
// page (hero copy and prose), the manifest (page lists and courses), the tree (modules), and the diff from the
// previous version (what's new). The previous version comes from the site store, which the router guard has already
// loaded, so nothing here waits on another request.
const pageKey = computed(() => `page:${props.version}:home/index`);
const { data: pageData, error: pageError } = useDocsData(pageKey, () => docsApi.page(props.version, "home", "index"));

const manifestKey = computed(() => `manifest:${props.version}`);
const { data: manifest, error: manifestError } = useDocsData(manifestKey, () => docsApi.manifest(props.version));

const treeKey = computed(() => `tree:${props.version}`);
const { data: tree } = useDocsData(treeKey, () => docsApi.tree(props.version));

const current = computed<DocVersion | undefined>(() => site.versionByLabel(props.version));
const previous = computed<DocVersion | null>(() => {
	const sortKey = current.value?.sortKey;

	if (sortKey === undefined) {
		return null;
	}

	return site.newestFirst.find((v) => v.sortKey < sortKey) ?? null;
});

const diffKey = computed(() => (previous.value ? `diff:${previous.value.label}:${props.version}` : "diff:none"));
const { data: diff, error: diffError } = useDocsData(diffKey, () => {
	if (!previous.value) {
		return Promise.reject(new Error("No previous version"));
	}

	return docsApi.diff(previous.value.label, props.version);
});

const tagline = computed(() => pageData.value?.page.summary?.trim() || null);
const body = computed(() => pageData.value?.page.body?.trim() || null);
const releasedOn = computed(() => {
	const raw = current.value?.releasedAt;

	if (!raw) {
		return null;
	}

	const date = new Date(raw);

	return Number.isNaN(date.getTime()) ? raw : date.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric", timeZone: "UTC" });
});

usePageMeta(
	computed(() => props.version),
	computed(() => tagline.value),
);

const versionLabel = computed(() => props.version);
const symbols = computed(() => pageData.value?.symbols ?? []);
const samples = computed(() => pageData.value?.samples ?? []);
const renderContext = useProseContext(versionLabel, symbols, samples);

interface ModeLink {
	title: string;
	route: string;
	meta: string | null;
	mono?: boolean;
}

interface ModeCard {
	mode: Mode;
	title: string;
	blurb: string;
	count: string;
	route: string;
	links: ModeLink[];
}

const MAX_LINKS = 4;

const modeTitles: Record<Mode, string> = { learn: "Learn", do: "Do", reference: "Reference", explain: "Explain" };
const modeBlurbs: Record<Mode, string> = {
	learn: "Courses that build something small, one step at a time.",
	do: "Short recipes for the things you need to ship.",
	reference: "Every symbol with its signature, defaults, and history at each version.",
	explain: "Why the library behaves the way it does.",
};

const pageRecords = computed(() =>
	(manifest.value?.searchRecords ?? [])
		.filter((r) => r.type === "page")
		.map((r) => r as { mode: Mode; title: string; summary: string; minutes: number | null; route: string }),
);

function pagesOf(mode: Mode): ModeLink[] {
	return pageRecords.value.filter((r) => r.mode === mode).map((r) => ({ title: r.title, route: r.route, meta: r.minutes ? `${r.minutes} min` : null }));
}

function plural(count: number, noun: string): string {
	return `${count} ${noun}${count === 1 ? "" : "s"}`;
}

const modeCards = computed<ModeCard[]>(() =>
	site.modes.map((mode) => {
		const route = `/${props.version}/${mode}`;
		const base = { mode, title: modeTitles[mode], blurb: modeBlurbs[mode], route };

		if (mode === "learn") {
			const courses = manifest.value?.courses ?? [];

			if (courses.length > 0) {
				return {
					...base,
					count: plural(courses.length, "course"),
					links: courses.slice(0, MAX_LINKS).map((c) => ({
						title: c.title,
						route: c.lessons[0]?.route ?? route,
						meta: `${plural(c.lessonCount, "lesson")} · about ${c.totalMinutes} min`,
					})),
				};
			}
		}

		if (mode === "reference") {
			const modules = tree.value?.modules ?? [];

			return {
				...base,
				count: plural(modules.length, "module"),
				links: modules.slice(0, MAX_LINKS).map((m) => ({ title: m.path, route, meta: m.summary || plural(m.count, "symbol"), mono: true })),
			};
		}

		const pages = pagesOf(mode);

		return { ...base, count: plural(pages.length, "page"), links: pages.slice(0, MAX_LINKS) };
	}),
);

const firstCourse = computed(() => manifest.value?.courses[0] ?? null);
const startRoute = computed(() => firstCourse.value?.lessons[0]?.route ?? `/${props.version}/learn`);
const startLabel = computed(() => (firstCourse.value ? `Start ${firstCourse.value.title}` : "Start learning"));

const KIND_ORDER = ["breaking", "removed", "deprecated", "behavior", "added"];
const kindLabels: Record<string, [string, string]> = {
	breaking: ["breaking change", "breaking changes"],
	behavior: ["behavior change", "behavior changes"],
	deprecated: ["deprecation", "deprecations"],
	added: ["addition", "additions"],
	removed: ["removal", "removals"],
};

function kindLabel(kind: string, count: number): string {
	const pair = kindLabels[kind] ?? [kind, kind];

	return count === 1 ? pair[0] : pair[1];
}

function kindPillClass(kind: string): string {
	if (kind === "breaking" || kind === "removed") {
		return "pill--warn";
	}

	return kind === "added" ? "pill--accent" : "";
}

const MAX_CHANGES = 5;

// The diff reports every kind, including empty ones; the landing page only mentions kinds that have changes.
const kindGroups = computed(() =>
	[...(diff.value?.groups ?? [])].filter((g) => g.count > 0).sort((a, b) => KIND_ORDER.indexOf(a.kind) - KIND_ORDER.indexOf(b.kind)),
);
const kindCounts = computed(() => kindGroups.value.map((g) => ({ kind: g.kind, count: g.count })));
const headlineChanges = computed<DiffChange[]>(() => kindGroups.value.flatMap((g) => g.changes).slice(0, MAX_CHANGES));
</script>

<style>
.home {
	gap: 40px;
}

.home-hero {
	display: flex;
	flex-direction: column;
	gap: 14px;
}

.home-hero__title {
	font-family: var(--font-serif);
	font-weight: 500;
	font-size: 48px;
	line-height: 1.1;
	letter-spacing: -0.01em;
}

.home-hero__tagline {
	font-family: var(--font-serif);
	font-size: var(--text-summary);
	line-height: 1.4;
	color: var(--color-ink2);
	max-width: 720px;
}

.home-hero__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	margin-top: 6px;
}

.home-cta {
	display: inline-flex;
	align-items: center;
	min-height: var(--touch-target);
	padding: 0 18px;
	border: 1px solid var(--color-control-border);
	border-radius: var(--radius-control);
	background: var(--color-surface);
	color: var(--color-ink);
	font-weight: 500;
	font-size: var(--text-small);
}

.home-cta:hover {
	border-color: var(--color-faint);
	text-decoration: none;
	color: var(--color-ink);
}

.home-cta--primary {
	background: var(--color-ink);
	border-color: var(--color-ink);
	color: var(--color-surface);
}

.home-cta--primary:hover {
	background: var(--color-ink2);
	border-color: var(--color-ink2);
	color: var(--color-surface);
}

.home-prose {
	max-width: 760px;
}

.home-section {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.home-modes {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.home-mode {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.home-mode__head {
	display: flex;
	align-items: baseline;
	gap: 10px;
}

.home-mode__title {
	font-size: var(--text-h2);
	font-weight: 600;
	color: var(--color-ink);
}

.home-mode__count,
.home-mode__empty {
	font-size: var(--text-meta);
}

.home-mode__blurb {
	font-size: var(--text-small);
	max-width: 640px;
}

.home-mode__links {
	list-style: none;
	margin: 4px 0 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.home-mode__links li {
	display: flex;
	flex-wrap: wrap;
	align-items: baseline;
	gap: 8px;
	font-size: var(--text-small);
}

.home-mode__links .muted {
	font-size: var(--text-meta);
}

.home-new {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.home-new__kinds {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
}

.home-new__list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.home-new__list li {
	display: flex;
	align-items: baseline;
	gap: 8px;
	font-size: var(--text-small);
}

.home-new__list .pill,
.home-new__kinds .pill {
	white-space: nowrap;
}

.home-new__link {
	font-size: var(--text-small);
	font-weight: 500;
}

.muted {
	color: var(--color-muted);
}

.mono {
	font-family: var(--font-mono);
}

@media (max-width: 768px) {
	.home {
		gap: 28px;
	}

	.home-hero__title {
		font-size: 34px;
	}

	.home-hero__tagline {
		font-size: var(--text-summary-mobile);
	}

	.home-cta {
		flex: 1 1 auto;
		justify-content: center;
	}
}
</style>
