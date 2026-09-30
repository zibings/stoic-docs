import "@fontsource-variable/ibm-plex-sans/wght.css";
import "@fontsource/ibm-plex-mono/400.css";
import "@fontsource/ibm-plex-mono/500.css";
import "@fontsource/ibm-plex-mono/600.css";
import "primeicons/primeicons.css";
import "design/tokens.css";
import "styles/admin.css";

import { createPinia } from "pinia";
import PrimeVue from "primevue/config";
import ConfirmationService from "primevue/confirmationservice";
import ToastService from "primevue/toastservice";
import Tooltip from "primevue/tooltip";
import { createApp } from "vue";

import App from "./App.vue";
import { createClient } from "api/client";
import { loadConfig } from "./config";
import { router } from "./router";
import { preset } from "./theme";

async function boot(): Promise<void> {
	const config = await loadConfig();

	createClient(config.api.baseUrl);

	const app = createApp(App);

	app.provide("adminConfig", config);
	app.use(createPinia());
	app.use(PrimeVue, { theme: { preset, options: { darkModeSelector: false } }, ripple: false });
	app.use(ToastService);
	app.use(ConfirmationService);
	app.directive("tooltip", Tooltip);
	app.use(router);
	app.mount("#app");
}

void boot();
