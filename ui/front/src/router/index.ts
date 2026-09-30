import type { RouteLocationNormalized, RouteLocationRaw, Router } from "vue-router";

import { useSiteStore } from "stores/site";

const LATEST = "latest";

/**
 * Rewrites the `latest` alias to the real latest label and sends unknown versions to the not-found view. Runs after
 * the site store has loaded, so it can only decide once the version list is known.
 */
export function installVersionGuard(router: Router): void {
	router.beforeEach(async (to) => {
		const site = useSiteStore();

		await site.load();

		const versionParams = ["version", "from", "to"].filter((key) => typeof to.params[key] === "string");

		if (versionParams.length === 0) {
			return true;
		}

		let redirect: RouteLocationRaw | null = null;
		const params = { ...to.params };

		for (const key of versionParams) {
			const value = params[key] as string;

			if (value.toLowerCase() === LATEST) {
				if (!site.latest) {
					return notFound(to);
				}

				params[key] = site.latest;
				redirect = { name: to.name ?? undefined, params, query: to.query, hash: to.hash, replace: true };
			} else if (site.loaded && !site.isKnownVersion(value)) {
				return notFound(to);
			}
		}

		return redirect ?? true;
	});
}

function notFound(to: RouteLocationNormalized): RouteLocationRaw {
	return {
		name: "not-found",
		params: { pathMatch: to.path.replace(/^\//, "").split("/") },
		query: to.query,
		hash: to.hash,
		replace: true,
	};
}
