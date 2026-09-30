<template>
	<div class="shell">
		<header class="shell__top">
			<RouterLink to="/" class="shell__brand">
				<span class="mono">{{ catalog.libraryName }}</span>
				<span class="shell__brand-sub">authoring</span>
			</RouterLink>
			<div class="shell__top-right">
				<a v-if="catalog.siteUrl" :href="catalog.siteUrl" target="_blank" rel="noopener" class="shell__site">View site ↗</a>
				<span v-if="auth.email" class="muted">{{ auth.email }}</span>
				<Button label="Sign out" icon="pi pi-sign-out" text size="small" @click="signOut" />
			</div>
		</header>
		<div class="shell__body">
			<nav class="shell__nav" aria-label="Sections">
				<RouterLink v-for="item in items" :key="item.to" :to="item.to" class="shell__link" :class="{ 'is-active': isActive(item.to) }">
					<i :class="item.icon" aria-hidden="true" />
					<span>{{ item.label }}</span>
				</RouterLink>
			</nav>
			<main class="shell__main">
				<RouterView />
			</main>
		</div>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import { RouterLink, RouterView, useRoute, useRouter } from "vue-router";

import { useAuthStore } from "stores/auth";
import { useCatalogStore } from "stores/catalog";

const auth = useAuthStore();
const catalog = useCatalogStore();
const route = useRoute();
const router = useRouter();

const items = [
	{ to: "/", label: "Dashboard", icon: "pi pi-home" },
	{ to: "/versions", label: "Versions", icon: "pi pi-tags" },
	{ to: "/modules", label: "Modules & symbols", icon: "pi pi-sitemap" },
	{ to: "/pages", label: "Pages", icon: "pi pi-file" },
	{ to: "/changes", label: "Changes", icon: "pi pi-history" },
	{ to: "/courses", label: "Courses", icon: "pi pi-book" },
	{ to: "/context-options", label: "Context options", icon: "pi pi-sliders-h" },
	{ to: "/import-export", label: "Import / export", icon: "pi pi-download" },
	{ to: "/users", label: "Users", icon: "pi pi-users" },
];

function isActive(to: string): boolean {
	return to === "/" ? route.path === "/" : route.path.startsWith(to);
}

async function signOut(): Promise<void> {
	await auth.logout();
	router.push({ name: "login" });
}
</script>

<style>
.shell {
	min-height: 100vh;
	display: flex;
	flex-direction: column;
}

.shell__top {
	height: 56px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 20px;
	border-bottom: 1px solid var(--color-rule);
	background: var(--color-surface);
}

.shell__brand {
	display: flex;
	align-items: baseline;
	gap: 8px;
	color: var(--color-ink);
	font-weight: 600;
	font-size: 16px;
}

.shell__brand:hover {
	text-decoration: none;
}

.shell__brand-sub {
	font-size: var(--text-label);
	font-weight: 500;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	color: var(--color-muted);
}

.shell__top-right {
	display: flex;
	align-items: center;
	gap: 14px;
	font-size: var(--text-meta);
}

.shell__body {
	flex: 1 1 auto;
	display: grid;
	grid-template-columns: 232px minmax(0, 1fr);
}

.shell__nav {
	display: flex;
	flex-direction: column;
	gap: 2px;
	padding: 16px 12px;
	border-right: 1px solid var(--color-rule);
	background: var(--color-surface-sunk);
}

.shell__link {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 9px 12px;
	border-radius: var(--radius-control);
	color: var(--color-ink3);
	font-size: var(--text-small);
}

.shell__link:hover {
	background: var(--color-chip);
	text-decoration: none;
}

.shell__link.is-active {
	background: var(--color-accent-tint);
	color: var(--color-accent-strong);
	font-weight: 600;
}

.shell__main {
	min-width: 0;
}

@media (max-width: 900px) {
	.shell__body {
		grid-template-columns: minmax(0, 1fr);
	}

	.shell__nav {
		flex-direction: row;
		flex-wrap: wrap;
		border-right: 0;
		border-bottom: 1px solid var(--color-rule);
	}

	.shell__link span {
		display: none;
	}
}
</style>
