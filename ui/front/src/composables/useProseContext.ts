import { computed } from "vue";
import type { Ref } from "vue";

import { collectSymbolRefs } from "markdown/render";
import type { RenderContext } from "markdown/render";
import { useContextStore } from "stores/context";
import { useSiteStore } from "stores/site";
import { useTrailStore } from "stores/trail";
import type { CodeSample, SymbolNode } from "types/docs";

/**
 * Builds the render context for a page body from the reader's context stores plus the symbols and samples the API
 * returned for the page. Upgrade notes render in full only when the trail includes a page at a version older than
 * the change (decision from phase 5), otherwise as a one-line link.
 */
export function useProseContext(versionLabel: Ref<string>, symbols: Ref<Iterable<SymbolNode | null | undefined>>, samples: Ref<CodeSample[]>) {
	const site = useSiteStore();
	const context = useContextStore();
	const trail = useTrailStore();

	return computed<RenderContext>(() => ({
		symbols: collectSymbolRefs(symbols.value),
		samples: samples.value,
		language: context.effectiveLanguage,
		languageLabel: context.languageLabel,
		packageManager: context.effectivePackageManager,
		versionLabel: versionLabel.value,
		showUpgradeNote: (_from, to) => {
			const target = site.versionByLabel(to);

			return target ? trail.hasVersionOlderThan(target.sortKey) : false;
		},
	}));
}
