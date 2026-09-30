import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";

import { useAuthStore } from "stores/auth";
import { useCatalogStore } from "stores/catalog";

declare module "vue-router" {
	interface RouteMeta {
		public?: boolean;
		title?: string;
	}
}

const routes: RouteRecordRaw[] = [
	{ path: "/login", name: "login", component: () => import("views/LoginView.vue"), meta: { public: true, title: "Sign in" } },
	{
		path: "/",
		component: () => import("layout/AdminShell.vue"),
		children: [
			{ path: "", name: "dashboard", component: () => import("views/DashboardView.vue"), meta: { title: "Dashboard" } },
			{ path: "versions", name: "versions", component: () => import("views/VersionsView.vue"), meta: { title: "Versions" } },
			{ path: "modules", name: "modules", component: () => import("views/ModulesView.vue"), meta: { title: "Modules" } },
			{ path: "modules/:id(\\d+)", name: "module", component: () => import("views/ModuleSymbolsView.vue"), props: true, meta: { title: "Symbols" } },
			{ path: "symbols/:id(\\d+)", name: "symbol", component: () => import("views/SymbolEditView.vue"), props: true, meta: { title: "Symbol" } },
			{ path: "pages", name: "pages", component: () => import("views/PagesView.vue"), meta: { title: "Pages" } },
			{ path: "pages/:id(\\d+)", name: "page", component: () => import("views/PageEditView.vue"), props: true, meta: { title: "Page" } },
			{ path: "changes", name: "changes", component: () => import("views/ChangesView.vue"), meta: { title: "Changes" } },
			{ path: "changes/:id(\\d+)", name: "change", component: () => import("views/ChangeEditView.vue"), props: true, meta: { title: "Change" } },
			{ path: "courses", name: "courses", component: () => import("views/CoursesView.vue"), meta: { title: "Courses" } },
			{ path: "courses/:id(\\d+)", name: "course", component: () => import("views/CourseEditView.vue"), props: true, meta: { title: "Course" } },
			{ path: "context-options", name: "contextOptions", component: () => import("views/ContextOptionsView.vue"), meta: { title: "Context options" } },
			{ path: "import-export", name: "importExport", component: () => import("views/ImportExportView.vue"), meta: { title: "Import / export" } },
			{ path: "users", name: "users", component: () => import("views/UsersView.vue"), meta: { title: "Users" } },
		],
	},
	{ path: "/:pathMatch(.*)*", redirect: "/" },
];

export const router = createRouter({
	history: createWebHistory(import.meta.env.BASE_URL),
	routes,
	scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to) => {
	const auth = useAuthStore();

	if (!auth.checked) {
		await auth.check();
	}

	if (to.meta.public) {
		return auth.loggedIn ? { name: "dashboard" } : true;
	}

	if (!auth.loggedIn) {
		return { name: "login", query: { next: to.fullPath } };
	}

	await useCatalogStore().load();

	return true;
});

router.afterEach((to) => {
	document.title = to.meta.title ? `${to.meta.title} · Docs authoring` : "Docs authoring";
});
