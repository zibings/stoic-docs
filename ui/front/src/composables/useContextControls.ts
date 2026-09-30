import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";

import { useCurrentVersion } from "composables/useCurrentVersion";
import { useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";

export interface PickerOption {
	key: string;
	label: string;
}

/** Shared state and handlers for the desktop context bar and the mobile context chip. */
export function useContextControls() {
	const route = useRoute();
	const router = useRouter();
	const site = useSiteStore();
	const context = useContextStore();
	const { label, fromLabel, isUpgrade } = useCurrentVersion();

	const versionOptions = computed<PickerOption[]>(() => {
		const from = isUpgrade.value && fromLabel.value ? site.versionByLabel(fromLabel.value) : undefined;

		return site.newestFirst
			.filter((v) => !from || v.sortKey > from.sortKey)
			.map((v) => ({ key: v.label, label: v.isLatest ? `${v.label} · latest` : v.label }));
	});

	const versionValue = computed(() => label.value ?? site.latest ?? null);

	const versionDisplay = computed(() => {
		if (isUpgrade.value && fromLabel.value && label.value) {
			return `${fromLabel.value} → ${label.value}`;
		}

		return versionValue.value ?? "—";
	});

	function selectVersion(next: string | null): void {
		if (!next || next === versionValue.value) {
			return;
		}

		if (isUpgrade.value) {
			router.push({ name: "upgrade", params: { from: route.params.from, to: next } });

			return;
		}

		if (typeof route.params.version === "string") {
			router.push({ name: route.name ?? undefined, params: { ...route.params, version: next }, query: route.query, hash: route.hash });

			return;
		}

		router.push(`/${next}/reference`);
	}

	const languageOptions = computed<PickerOption[]>(() => site.languages.map((o) => ({ key: o.key, label: o.label })));
	const packageManagerOptions = computed<PickerOption[]>(() => site.packageManagers.map((o) => ({ key: o.key, label: o.label })));

	const summary = computed(() => [versionDisplay.value, context.languageLabel, context.packageManagerLabel].filter(Boolean).join(" · "));

	return {
		isUpgrade,
		versionOptions,
		versionValue,
		versionDisplay,
		selectVersion,
		languageOptions,
		languageValue: computed(() => context.effectiveLanguage),
		selectLanguage: (key: string | null) => key && context.setLanguage(key),
		packageManagerOptions,
		packageManagerValue: computed(() => context.effectivePackageManager),
		selectPackageManager: (key: string | null) => key && context.setPackageManager(key),
		summary,
	};
}
