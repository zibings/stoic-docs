import type { RouteRecordRaw } from "vue-router";

import type { Mode } from "types/docs";

declare module "vue-router" {
	interface RouteMeta {
		/** Reader mode this route belongs to; drives the active header tab. Absent on upgrade and error routes. */
		mode?: Mode;
	}
}

// URL scheme (see HANDOFF §8 and DocsReader route builders):
//   /:version                              landing page (hero from the `home/index` page plus generated sections)
//   /:version/reference/:module+/:symbol   symbol page; module path may span segments, nested names are dotted
//   /:version/do/:slug, /:version/explain/:slug
//   /:version/learn/:course/:lesson
//   /upgrade/:from/:to
//   /latest/...  → rewritten by the router guard to the current latest label
const routes: RouteRecordRaw[] = [
	{
		path: "/",
		component: () => import("layout/AppShell.vue"),
		children: [
			{
				path: "",
				name: "root",
				redirect: { path: "/latest" },
			},
			{
				path: "upgrade/:from/:to",
				name: "upgrade",
				component: () => import("views/UpgradeView.vue"),
				props: true,
			},
			{
				path: ":version",
				name: "home",
				component: () => import("views/HomeView.vue"),
				props: true,
			},
			{
				path: ":version/reference",
				name: "reference-index",
				component: () => import("views/ReferenceIndexView.vue"),
				meta: { mode: "reference" },
				props: true,
			},
			{
				path: ":version/reference/:module+/:symbol",
				name: "reference-symbol",
				component: () => import("views/ReferenceSymbolView.vue"),
				meta: { mode: "reference" },
				props: true,
			},
			{
				path: ":version/do",
				name: "do-index",
				component: () => import("views/ModeIndexView.vue"),
				meta: { mode: "do" },
				props: true,
			},
			{
				path: ":version/do/:slug(.*)",
				name: "do",
				component: () => import("views/PageView.vue"),
				meta: { mode: "do" },
				props: true,
			},
			{
				path: ":version/explain",
				name: "explain-index",
				component: () => import("views/ModeIndexView.vue"),
				meta: { mode: "explain" },
				props: true,
			},
			{
				path: ":version/explain/:slug(.*)",
				name: "explain",
				component: () => import("views/PageView.vue"),
				meta: { mode: "explain" },
				props: true,
			},
			{
				path: ":version/learn",
				name: "learn-index",
				component: () => import("views/LearnIndexView.vue"),
				meta: { mode: "learn" },
				props: true,
			},
			{
				path: ":version/learn/:course/:lesson",
				name: "learn-lesson",
				component: () => import("views/LearnView.vue"),
				meta: { mode: "learn" },
				props: true,
			},
			{
				path: ":pathMatch(.*)*",
				name: "not-found",
				component: () => import("views/NotFoundView.vue"),
			},
		],
	},
];

export default routes;
