import { definePreset, palette } from "@primevue/themes";
import Aura from "@primevue/themes/aura";

// PrimeVue runs styled here (internal tooling, no mockups). The Aura preset is re-colored from the shared design
// tokens so the authoring UI reads as the same product as the public site: blue accent, warm neutral surfaces.
export const preset = definePreset(Aura, {
	semantic: {
		primary: palette("#2747C7"),
		colorScheme: {
			light: {
				surface: {
					0: "#FFFFFF",
					50: "#FAF8F4",
					100: "#F6F4EF",
					200: "#EFEBE3",
					300: "#E2DED5",
					400: "#D8D3C8",
					500: "#8A8478",
					600: "#5A5F6B",
					700: "#3E4350",
					800: "#2A2D35",
					900: "#16181D",
					950: "#0F1114",
				},
			},
		},
	},
});
