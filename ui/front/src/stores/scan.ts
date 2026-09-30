import { defineStore } from "pinia";

import { parseScanReport } from "upgrade/diff";
import type { ScanReport } from "upgrade/diff";

// A scan report the reader loaded from their own machine. Session memory only: it describes their private code and
// never leaves the browser.
export const useScanStore = defineStore("scan", {
	state: () => ({
		report: null as ScanReport | null,
		fileName: null as string | null,
		error: null as string | null,
	}),

	actions: {
		loadText(text: string, fileName: string | null = null) {
			try {
				this.report = parseScanReport(text);
				this.fileName = fileName;
				this.error = null;
			} catch (err) {
				this.error = err instanceof Error ? err.message : String(err);
			}
		},

		clear() {
			this.report = null;
			this.fileName = null;
			this.error = null;
		},
	},
});
