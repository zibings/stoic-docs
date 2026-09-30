import type MiniSearch from "minisearch";

import { docsApi } from "api/docs";
import { buildIndex, loadIndex, toDocs } from "search/index";
import type { SearchDoc, SearchRecord, StaticSearchFile } from "search/index";
import { useCacheStore } from "stores/cache";
import type { Manifest } from "types/docs";

export interface VersionIndex {
	docs: SearchDoc[];
	index: MiniSearch<SearchDoc>;
	/** "static" when loaded from web/search/{version}.json, "api" when built from the manifest endpoint. */
	source: "static" | "api";
}

// Built indexes are not serializable state, so they live in a module-level map (client only).
const indexes = new Map<string, Promise<VersionIndex>>();

export function getVersionIndex(versionLabel: string): Promise<VersionIndex> {
	let pending = indexes.get(versionLabel);

	if (!pending) {
		pending = load(versionLabel);
		pending.catch(() => indexes.delete(versionLabel));
		indexes.set(versionLabel, pending);
	}

	return pending;
}

async function load(versionLabel: string): Promise<VersionIndex> {
	const fromStatic = await loadStatic(versionLabel);

	if (fromStatic) {
		return fromStatic;
	}

	const records = await loadRecordsFromApi(versionLabel);
	const docs = toDocs(records);

	return { docs, index: buildIndex(docs), source: "api" };
}

/** The build writes one file per version; the dev server has none, so a miss falls through to the API. */
async function loadStatic(versionLabel: string): Promise<VersionIndex | null> {
	if (typeof fetch !== "function") {
		return null;
	}

	try {
		const response = await fetch(`${import.meta.env.BASE_URL}search/${encodeURIComponent(versionLabel)}.json`, { headers: { Accept: "application/json" } });

		if (!response.ok || !(response.headers.get("content-type") ?? "").includes("json")) {
			return null;
		}

		const file = (await response.json()) as StaticSearchFile;

		if (!Array.isArray(file.records) || !file.index) {
			return null;
		}

		return { docs: toDocs(file.records), index: loadIndex(file.index), source: "static" };
	} catch {
		return null;
	}
}

async function loadRecordsFromApi(versionLabel: string): Promise<SearchRecord[]> {
	const cache = useCacheStore();
	const key = `manifest:${versionLabel}`;
	let manifest = cache.entries[key] as Manifest | undefined;

	if (!manifest) {
		manifest = await docsApi.manifest(versionLabel);
		cache.set(key, manifest);
	}

	return manifest.searchRecords as SearchRecord[];
}

/** Test hook: forget every built index. */
export function resetIndexes(): void {
	indexes.clear();
}
