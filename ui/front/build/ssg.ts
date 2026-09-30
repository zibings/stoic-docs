// Build-time helpers for the pre-render pipeline (used by vite.config.ts and scripts/clean-web.mjs).
//
//   collectRoutes      every route to pre-render, from /Docs/Site + /Docs/Manifest/{version}
//   writeSearchIndexes web/search/{version}.json: records + a serialized MiniSearch index
//   writeSitemap       web/sitemap.xml when the site's public origin is configured
//   writeOutputList    web/.ssg-output.json so the next build can remove exactly what this one produced
import { existsSync, mkdirSync, readFileSync, readdirSync, rmSync, statSync, writeFileSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";

import { buildIndex, serializeIndex, toDocs } from "../src/search/index";
import type { SearchRecord, StaticSearchFile } from "../src/search/index";

export interface SsgVersion {
	id: number;
	tag: string;
	label: string;
	sortKey: number;
	isLatest: boolean;
	isSupported: boolean;
}

export interface SsgManifest {
	version: SsgVersion;
	routes: string[];
	searchRecords: SearchRecord[];
}

export interface SsgCollected {
	apiBase: string;
	siteUrl: string | null;
	versions: SsgVersion[];
	manifests: SsgManifest[];
	routes: string[];
}

export type JsonFetcher = (url: string) => Promise<unknown>;

export const OUTPUT_LIST_FILE = ".ssg-output.json";

/** Fetches JSON or throws with the URL and status, so a build never silently pre-renders a shell-only site. */
export const defaultFetcher: JsonFetcher = async (url) => {
	const response = await fetch(url, { headers: { Accept: "application/json" } });

	if (!response.ok) {
		throw new Error(`Docs API request failed: ${url} → HTTP ${response.status}`);
	}

	return response.json();
};

/**
 * The API base for the build: DOCS_API_BASE_URL, else public/config.json. Must be absolute; the build runs in Node
 * with no origin to resolve a relative path against.
 */
export function readApiBase(projectRoot: string, env: NodeJS.ProcessEnv = process.env): string {
	let base = env.DOCS_API_BASE_URL ?? "";

	if (!base) {
		const file = resolve(projectRoot, "public/config.json");

		if (existsSync(file)) {
			const config = JSON.parse(readFileSync(file, "utf8")) as { api?: { baseUrl?: string } };

			base = config.api?.baseUrl ?? "";
		}
	}

	if (!/^https?:\/\//.test(base)) {
		throw new Error(`The docs API base must be an absolute URL for pre-rendering (got "${base}"). Set DOCS_API_BASE_URL or api.baseUrl in public/config.json.`);
	}

	return base.replace(/\/+$/, "");
}

const MODES = ["learn", "do", "reference", "explain"];

export async function collectRoutes(apiBase: string, fetchJson: JsonFetcher = defaultFetcher): Promise<SsgCollected> {
	const site = (await fetchJson(`${apiBase}/1.1/Docs/Site`)) as { siteUrl?: string | null; versions?: SsgVersion[] };
	const versions = site.versions ?? [];

	if (versions.length === 0) {
		throw new Error("The docs API reports no versions; nothing to pre-render.");
	}

	const manifests: SsgManifest[] = [];

	for (const version of versions) {
		manifests.push((await fetchJson(`${apiBase}/1.1/Docs/Manifest/${encodeURIComponent(version.label)}`)) as SsgManifest);
	}

	const routes = new Set<string>(["/"]);

	// Landing and mode index pages are not in the manifests (they list content, not the shells around it), so add them
	// per version.
	for (const version of versions) {
		routes.add(`/${version.label}`);

		for (const mode of MODES) {
			routes.add(`/${version.label}/${mode}`);
		}
	}

	for (const manifest of manifests) {
		for (const route of manifest.routes) {
			routes.add(route);
		}
	}

	return {
		apiBase,
		siteUrl: site.siteUrl ? site.siteUrl.replace(/\/+$/, "") : null,
		versions,
		manifests,
		routes: [...routes],
	};
}

/** vite-ssg's flat file for a route: "/" → index.html, "/a/b" → a/b.html. */
export function routeToFile(route: string): string {
	if (route === "/" || route === "") {
		return "index.html";
	}

	return `${route.replace(/^\/+/, "").replace(/\/+$/, "")}.html`;
}

export function buildSearchFile(manifest: SsgManifest, now = new Date()): StaticSearchFile {
	const records = manifest.searchRecords;
	const index = buildIndex(toDocs(records));

	return {
		version: { label: manifest.version.label, tag: manifest.version.tag, sortKey: manifest.version.sortKey },
		generatedAt: now.toISOString(),
		records,
		index: serializeIndex(index),
	};
}

/** Writes search/{label}.json per version and returns the relative paths written. */
export function writeSearchIndexes(outDir: string, manifests: SsgManifest[]): string[] {
	const written: string[] = [];

	mkdirSync(join(outDir, "search"), { recursive: true });

	for (const manifest of manifests) {
		const rel = `search/${manifest.version.label}.json`;

		writeFileSync(join(outDir, rel), JSON.stringify(buildSearchFile(manifest)));
		written.push(rel);
	}

	return written;
}

export function buildSitemap(siteUrl: string, routes: string[], lastmod = new Date()): string {
	const origin = siteUrl.replace(/\/+$/, "");
	const date = lastmod.toISOString().slice(0, 10);
	const urls = [...routes]
		.sort()
		.map((route) => `\t<url>\n\t\t<loc>${escapeXml(origin + route)}</loc>\n\t\t<lastmod>${date}</lastmod>\n\t</url>`)
		.join("\n");

	return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`;
}

/** Writes sitemap.xml when the public origin is configured; returns its relative path or null. */
export function writeSitemap(outDir: string, siteUrl: string | null, routes: string[]): string | null {
	if (!siteUrl) {
		return null;
	}

	writeFileSync(join(outDir, "sitemap.xml"), buildSitemap(siteUrl, routes));

	return "sitemap.xml";
}

/** Relative paths of everything under outDir/assets (hashed bundles, fonts). */
export function listAssetFiles(outDir: string): string[] {
	const dir = join(outDir, "assets");

	if (!existsSync(dir)) {
		return [];
	}

	const files: string[] = [];
	const walk = (current: string): void => {
		for (const entry of readdirSync(current)) {
			const full = join(current, entry);

			if (statSync(full).isDirectory()) {
				walk(full);
			} else {
				files.push(relative(outDir, full).split("\\").join("/"));
			}
		}
	};

	walk(dir);

	return files;
}

export function writeOutputList(outDir: string, files: string[]): void {
	const unique = [...new Set(files)].sort();

	writeFileSync(join(outDir, OUTPUT_LIST_FILE), JSON.stringify({ generatedAt: new Date().toISOString(), files: unique }, null, "\t") + "\n");
}

/**
 * Removes the files listed by the previous build (and directories left empty by that), never anything else.
 * Returns the paths removed.
 */
export function cleanPreviousOutput(outDir: string): string[] {
	const listFile = join(outDir, OUTPUT_LIST_FILE);

	if (!existsSync(listFile)) {
		return [];
	}

	const { files } = JSON.parse(readFileSync(listFile, "utf8")) as { files: string[] };
	const removed: string[] = [];
	const root = resolve(outDir);

	for (const rel of files) {
		const full = resolve(outDir, rel);

		if (!full.startsWith(root + "/") || !existsSync(full)) {
			continue;
		}

		rmSync(full, { force: true });
		removed.push(rel);

		let parent = dirname(full);

		while (parent !== root && parent.startsWith(root + "/") && existsSync(parent) && readdirSync(parent).length === 0) {
			rmSync(parent, { recursive: true, force: true });
			parent = dirname(parent);
		}
	}

	rmSync(listFile, { force: true });

	return removed;
}

function escapeXml(text: string): string {
	return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;");
}
