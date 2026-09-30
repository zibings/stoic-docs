import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

import { afterEach, describe, expect, it } from "vitest";

import { buildSearchFile, buildSitemap, cleanPreviousOutput, collectRoutes, listAssetFiles, readApiBase, routeToFile, writeOutputList, writeSearchIndexes, writeSitemap } from "../ssg";
import type { SsgManifest } from "../ssg";
import { loadIndex, toDocs } from "../../src/search/index";

const versions = [
	{ id: 1, tag: "3.8.0", label: "v3.8", sortKey: 380, isLatest: false, isSupported: true },
	{ id: 2, tag: "4.2.0", label: "v4.2", sortKey: 420, isLatest: true, isSupported: true },
];

const manifests: Record<string, SsgManifest> = {
	"v3.8": {
		version: versions[0],
		routes: ["/v3.8/reference/tessel/query/createQuery", "/v3.8/do/show-cached"],
		searchRecords: [{ type: "symbol", ref: "tessel/query#createQuery", name: "createQuery", kind: "fn", module: "tessel/query", summary: "Declare data.", signature: "function createQuery()", state: "current", route: "/v3.8/reference/tessel/query/createQuery" }],
	},
	"v4.2": {
		version: versions[1],
		routes: ["/v4.2/reference/tessel/query/createQuery", "/v4.2/do/show-cached", "/upgrade/v3.8/v4.2"],
		searchRecords: [
			{ type: "symbol", ref: "tessel/query#createQuery", name: "createQuery", kind: "fn", module: "tessel/query", summary: "Declare data.", signature: "function createQuery()", state: "current", route: "/v4.2/reference/tessel/query/createQuery" },
			{ type: "page", mode: "do", slug: "show-cached", title: "Show cached data", summary: "", minutes: 3, route: "/v4.2/do/show-cached" },
		],
	},
};

const fetcher = async (url: string): Promise<unknown> => {
	if (url.endsWith("/1.1/Docs/Site")) {
		return { siteUrl: "https://docs.example.com/", versions };
	}

	const match = /Manifest\/(.+)$/.exec(url);

	if (match) {
		return manifests[decodeURIComponent(match[1])];
	}

	throw new Error(`unexpected ${url}`);
};

const dirs: string[] = [];

function tempDir(): string {
	const dir = mkdtempSync(join(tmpdir(), "ssg-"));

	dirs.push(dir);

	return dir;
}

afterEach(() => {
	for (const dir of dirs.splice(0)) {
		rmSync(dir, { recursive: true, force: true });
	}
});

describe("collectRoutes", () => {
	it("unions every version's routes with the root and keeps the site url", async () => {
		const collected = await collectRoutes("http://api.test/api", fetcher);

		const indexes = ["/v3.8", "/v3.8/learn", "/v3.8/do", "/v3.8/reference", "/v3.8/explain", "/v4.2", "/v4.2/learn", "/v4.2/do", "/v4.2/reference", "/v4.2/explain"];

		expect(collected.routes).toEqual(["/", ...indexes, "/v3.8/reference/tessel/query/createQuery", "/v3.8/do/show-cached", "/v4.2/reference/tessel/query/createQuery", "/v4.2/do/show-cached", "/upgrade/v3.8/v4.2"]);
		expect(collected.siteUrl).toBe("https://docs.example.com");
		expect(collected.manifests).toHaveLength(2);
	});

	it("fails loudly when the API has no versions", async () => {
		await expect(collectRoutes("http://api.test/api", async () => ({ versions: [] }))).rejects.toThrow(/no versions/);
	});
});

describe("readApiBase", () => {
	it("prefers the environment, then public/config.json, and insists on an absolute URL", () => {
		const root = tempDir();

		mkdirSync(join(root, "public"));
		writeFileSync(join(root, "public/config.json"), JSON.stringify({ api: { baseUrl: "http://localhost:8080/api/" } }));

		expect(readApiBase(root, {})).toBe("http://localhost:8080/api");
		expect(readApiBase(root, { DOCS_API_BASE_URL: "https://api.example.com/api/" })).toBe("https://api.example.com/api");
		expect(() => readApiBase(tempDir(), {})).toThrow(/absolute URL/);
	});
});

describe("search files", () => {
	it("serializes an index that loads back and answers queries", () => {
		const file = buildSearchFile(manifests["v4.2"], new Date("2026-09-30T00:00:00Z"));

		expect(file.version.label).toBe("v4.2");
		expect(file.generatedAt).toBe("2026-09-30T00:00:00.000Z");
		expect(file.records).toHaveLength(2);

		const roundTripped = JSON.parse(JSON.stringify(file));
		const index = loadIndex(roundTripped.index);
		const docs = toDocs(roundTripped.records);
		const hits = index.search("create").map((h) => docs.find((d) => d.id === h.id)?.title);

		expect(hits).toContain("createQuery");
	});

	it("writes one file per version", () => {
		const out = tempDir();
		const written = writeSearchIndexes(out, Object.values(manifests));

		expect(written).toEqual(["search/v3.8.json", "search/v4.2.json"]);
		expect(existsSync(join(out, "search/v4.2.json"))).toBe(true);
	});
});

describe("sitemap and output list", () => {
	it("maps routes to flat files", () => {
		expect(routeToFile("/")).toBe("index.html");
		expect(routeToFile("/v4.2/do/show-cached")).toBe("v4.2/do/show-cached.html");
		expect(routeToFile("/v4.2/reference/tessel/query/QueryOptions.staleTime")).toBe("v4.2/reference/tessel/query/QueryOptions.staleTime.html");
	});

	it("builds a sitemap with absolute, escaped urls and writes it only when an origin exists", () => {
		const xml = buildSitemap("https://docs.example.com/", ["/", "/v4.2/do/a&b"], new Date("2026-09-30T12:00:00Z"));

		expect(xml).toContain("<loc>https://docs.example.com/</loc>");
		expect(xml).toContain("<loc>https://docs.example.com/v4.2/do/a&amp;b</loc>");
		expect(xml).toContain("<lastmod>2026-09-30</lastmod>");

		const out = tempDir();

		expect(writeSitemap(out, null, ["/"])).toBeNull();
		expect(writeSitemap(out, "https://docs.example.com", ["/"])).toBe("sitemap.xml");
		expect(existsSync(join(out, "sitemap.xml"))).toBe(true);
	});

	it("removes exactly the previous build's files and the directories they leave empty", () => {
		const out = tempDir();

		mkdirSync(join(out, "v4.2/do"), { recursive: true });
		mkdirSync(join(out, "assets"), { recursive: true });
		mkdirSync(join(out, "api/1.1"), { recursive: true });
		writeFileSync(join(out, "v4.2/do/a.html"), "a");
		writeFileSync(join(out, "assets/app-123.js"), "js");
		writeFileSync(join(out, "api/1.1/index.php"), "php");
		writeFileSync(join(out, "config.json"), "{}");

		expect(listAssetFiles(out)).toEqual(["assets/app-123.js"]);
		writeOutputList(out, ["v4.2/do/a.html", ...listAssetFiles(out), "missing.html"]);

		const removed = cleanPreviousOutput(out);

		expect(removed).toEqual(["assets/app-123.js", "v4.2/do/a.html"]);
		expect(existsSync(join(out, "v4.2"))).toBe(false);
		expect(existsSync(join(out, "assets"))).toBe(false);
		expect(existsSync(join(out, "api/1.1/index.php"))).toBe(true);
		expect(existsSync(join(out, "config.json"))).toBe(true);
		expect(readFileSync(join(out, "config.json"), "utf8")).toBe("{}");
		expect(cleanPreviousOutput(out)).toEqual([]);
	});
});
