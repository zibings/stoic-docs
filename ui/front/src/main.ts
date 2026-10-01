import "@fontsource-variable/ibm-plex-sans/wght.css";
import "@fontsource/ibm-plex-mono/400.css";
import "@fontsource/ibm-plex-mono/500.css";
import "@fontsource/ibm-plex-mono/600.css";
import "@fontsource-variable/newsreader/opsz.css";
import "design/tokens.css";
import "design/theme-dark.css";
import "styles/base.css";
import "styles/code.css";
import "styles/rail.css";
import "styles/sheet.css";

import { createPinia } from "pinia";
import PrimeVue from "primevue/config";
import { ViteSSG } from "vite-ssg";

import App from "./App.vue";
import { loadConfig } from "./config";
import { createApi } from "composables/useApi";
import { installVersionGuard } from "./router";
import routes from "./router/routes";
import { useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";
import { useThemeStore } from "stores/theme";

export const createApp = ViteSSG(
	App,
	{
		base: import.meta.env.BASE_URL,
		routes,
		scrollBehavior(to, from, savedPosition) {
			if (savedPosition) {
				return savedPosition;
			}

			if (to.hash) {
				return { el: to.hash, top: 0 };
			}

			return { left: 0, top: 0 };
		},
	},
	async ({ app, router, initialState, isClient }) => {
		const pinia = createPinia();

		app.use(pinia);

		// PrimeVue supplies behavior only (dialogs, trees, selects, focus and keyboard handling). Every visual comes
		// from our own CSS built on design/tokens.css, so no theme preset is loaded.
		app.use(PrimeVue, { unstyled: true, ripple: false });

		const config = await loadConfig();

		createApi(config.api.baseUrl);

		if (isClient) {
			pinia.state.value = initialState.pinia ?? {};
			useContextStore(pinia).hydrate();
			useThemeStore(pinia).hydrate();
		} else {
			initialState.pinia = pinia.state.value;
		}

		await useSiteStore(pinia).load();

		installVersionGuard(router);
	},
);
