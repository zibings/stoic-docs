import { describe, expect, it } from "vitest";

import { collectSymbolRefs, pickSample, renderDocBody, renderInline } from "markdown/render";
import type { RenderContext } from "markdown/render";
import type { CodeSample, SymbolNode } from "types/docs";

function node(ref: string, route: string, removed = false): SymbolNode {
	const name = ref.slice(ref.indexOf("#") + 1);

	return {
		ref,
		name,
		shortName: name.includes(".") ? name.slice(name.lastIndexOf(".") + 1) : name,
		kind: "fn",
		state: removed ? "removed" : "current",
		sinceLabel: "v2.0",
		changedLabel: null,
		removedLabel: removed ? "v4.0" : null,
		route,
		contract: { id: 1, symbolId: 1, introducedVersionId: 1, removedVersionId: null, introducedLabel: "v2.0", removedLabel: null, signature: "", summary: "", status: "stable", params: [], returns: {}, throws: [], sourcePath: null, sourceLine: null, sourceUrl: null },
		children: [],
	};
}

const samples: CodeSample[] = [
	{ key: "minimal", title: "user.ts", language: "ts", variant: null, code: "const a: number = 1;\n", isTested: true, lastTestPassLabel: "v4.2" },
	{ key: "minimal", title: "user.js", language: "js", variant: null, code: "const a = 1;\n", isTested: false, lastTestPassLabel: null },
	{ key: "install", title: "terminal", language: "ts", variant: "pnpm", code: "pnpm add tessel\n", isTested: false, lastTestPassLabel: null },
	{ key: "install", title: "terminal", language: "ts", variant: null, code: "npm install tessel\n", isTested: false, lastTestPassLabel: null },
];

function ctx(overrides: Partial<RenderContext> = {}): RenderContext {
	return {
		symbols: collectSymbolRefs([node("tessel/query#QueryOptions.staleTime", "/v4.2/reference/tessel/query/QueryOptions.staleTime"), node("tessel/query#useLegacyCache", "/v4.2/reference/tessel/query/useLegacyCache", true)]),
		samples,
		language: "ts",
		languageLabel: "TypeScript",
		packageManager: "pnpm",
		versionLabel: "v4.2",
		...overrides,
	};
}

describe("renderDocBody", () => {
	it("turns symbol refs into links carrying data-symbol-ref", () => {
		const html = renderDocBody("Fresh until {sym:tessel/query#QueryOptions.staleTime} elapses.", ctx());

		expect(html).toContain('<a href="/v4.2/reference/tessel/query/QueryOptions.staleTime" class="sym-ref" data-symbol-ref="tessel/query#QueryOptions.staleTime"');
		expect(html).toContain(">staleTime</a>");
	});

	it("strikes removed symbols and falls back to code for unknown refs", () => {
		const html = renderDocBody("{sym:tessel/query#useLegacyCache} and {sym:tessel/nope#Missing.thing}", ctx());

		expect(html).toContain('class="sym-ref removed"');
		expect(html).toContain('<code class="sym-ref sym-ref--unresolved" data-symbol-ref="tessel/nope#Missing.thing">thing</code>');
	});

	it("renders card containers as full-row cards with a label", () => {
		const html = renderDocBody(":::card[You'll leave able to]\nExplain **fresh** vs stale.\n:::\n", ctx());

		expect(html).toContain(`<section class="card doc-card"><div class="doc-card__label">You'll leave able to</div><div class="doc-card__body">`);
		expect(html).toContain("<strong>fresh</strong>");
		expect(html).toContain("</div></section>");
	});

	it("renders upgrade notes in full when the rule says so, otherwise as a one-line link with hidden body", () => {
		const body = ":::upgrade{from=v3.8 to=v4.0}\nThe default changed.\n:::\n";

		const full = renderDocBody(body, ctx({ showUpgradeNote: () => true }));
		expect(full).toContain('<aside class="card card--warn upgrade-note" data-upgrade-from="v3.8" data-upgrade-to="v4.0">');
		expect(full).toContain("Upgrade note · v3.8 → v4.0");
		expect(full).toContain('<a class="upgrade-note__link" href="/upgrade/v3.8/v4.0">See the full v3.8 → v4.0 diff</a>');

		const collapsed = renderDocBody(body, ctx({ showUpgradeNote: () => false }));
		expect(collapsed).toContain('class="upgrade-note upgrade-note--collapsed"');
		expect(collapsed).toContain('<span class="pill pill--warn">changed v4.0</span>');
		expect(collapsed).toContain('<div class="upgrade-note__body" hidden>');
		expect(collapsed).not.toContain("upgrade-note__link");
	});

	it("embeds the sample matching the reader's language and variant, with copy button and highlighting", () => {
		const html = renderDocBody("## Minimal example\n\n<<sample:minimal>>\n", ctx());

		expect(html).toContain('data-sample-key="minimal" data-language="ts"');
		expect(html.replace(/<[^>]+>/g, "")).toContain("user.ts · rendered for v4.2 + TypeScript · tested on v4.2");
		expect(html).toContain('<span class="code-sample__title">user.ts</span>');
		expect(html).toContain('<button type="button" class="code-sample__copy" data-copy="sample-1-minimal">Copy</button>');
		expect(html).toContain('class="hljs-keyword">const</span>');
		expect(html).not.toContain("<<sample:");
	});

	it("re-renders samples for another language and falls back to the variant-less sample", () => {
		const js = renderDocBody("<<sample:minimal>>", ctx({ language: "js", languageLabel: "JavaScript" }));
		expect(js.replace(/<[^>]+>/g, "")).toContain("user.js · rendered for v4.2 + JavaScript");

		const yarn = renderDocBody("<<sample:install>>", ctx({ packageManager: "yarn" }));
		expect(yarn).toContain("npm install tessel");

		const pnpm = renderDocBody("<<sample:install>>", ctx({ packageManager: "pnpm" }));
		expect(pnpm).toContain("pnpm add tessel");
	});

	it("renders a placeholder for a missing sample key", () => {
		const html = renderDocBody("<<sample:nope>>", ctx());

		expect(html).toContain('class="code-sample code-sample--missing card" data-sample-key="nope"');
		expect(html).toContain("No sample “nope” for TypeScript at v4.2");
	});

	it("highlights fenced code and escapes raw HTML", () => {
		const html = renderDocBody("```ts\nconst x = \"<b>\";\n```\n\n<script>alert(1)</script>", ctx());

		expect(html).toContain('<pre class="code-block"><code class="hljs language-ts">');
		expect(html).toContain("&lt;b&gt;");
		expect(html).not.toContain("<script>");
	});
});

describe("pickSample", () => {
	it("prefers exact language and variant, then variant-less, then any language", () => {
		expect(pickSample(samples, "install", "ts", "pnpm").sample?.code).toBe("pnpm add tessel\n");
		expect(pickSample(samples, "install", "ts", "yarn").sample?.code).toBe("npm install tessel\n");
		expect(pickSample(samples, "minimal", "php", null)).toMatchObject({ languageMismatch: true });
		expect(pickSample(samples, "missing", "ts", null).sample).toBeNull();
	});

	it("renders one line of Markdown inline, without a paragraph", () => {
		const html = renderInline("Click **Load profile** twice and read `staleTime`.");

		expect(html).toBe("Click <strong>Load profile</strong> twice and read <code>staleTime</code>.");
		expect(html).not.toContain("<p>");
	});
});
