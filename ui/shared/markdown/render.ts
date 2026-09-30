import MarkdownIt from "markdown-it";
import type { Env as MarkdownItEnv, MarkdownIt as MarkdownItInstance, StateBlock, StateInline, Token } from "markdown-it";
import container from "markdown-it-container";

import { escapeHtml, highlight } from "./highlight";
import type { CodeSample, SymbolNode } from "../types/docs";

/** What the renderer knows about a symbol the prose may reference. */
export interface SymbolRefInfo {
	ref: string;
	name: string;
	shortName: string;
	route: string;
	removed: boolean;
}

/** Everything a page body needs to render for one reader context. */
export interface RenderContext {
	symbols: Map<string, SymbolRefInfo>;
	samples: CodeSample[];
	language: string | null;
	languageLabel: string;
	packageManager: string | null;
	versionLabel: string;
	/** Decides whether an upgrade note renders in full or as a one-line link. Defaults to full. */
	showUpgradeNote?: (from: string, to: string) => boolean;
}

interface Env extends RenderContext {
	counter: number;
	[key: string]: unknown;
}

export function symbolRefInfo(node: SymbolNode): SymbolRefInfo {
	return { ref: node.ref, name: node.name, shortName: node.shortName, route: node.route, removed: node.state === "removed" };
}

/** Collects every node (and its children) into the ref map the renderer resolves against. */
export function collectSymbolRefs(nodes: Iterable<SymbolNode | null | undefined>, into = new Map<string, SymbolRefInfo>()): Map<string, SymbolRefInfo> {
	for (const node of nodes) {
		if (!node) {
			continue;
		}

		into.set(node.ref, symbolRefInfo(node));
		collectSymbolRefs(node.children ?? [], into);
	}

	return into;
}

/** Picks the sample to show for a key given the reader's context, with sensible fallbacks. */
export function pickSample(samples: CodeSample[], key: string, language: string | null, packageManager: string | null): { sample: CodeSample | null; languageMismatch: boolean } {
	const candidates = samples.filter((s) => s.key === key);

	if (candidates.length === 0) {
		return { sample: null, languageMismatch: false };
	}

	const byLanguage = language ? candidates.filter((s) => s.language === language) : candidates;
	const pool = byLanguage.length > 0 ? byLanguage : candidates;
	const exact = pool.find((s) => s.variant === packageManager) ?? pool.find((s) => s.variant === null) ?? pool[0];

	return { sample: exact ?? null, languageMismatch: byLanguage.length === 0 };
}

/** HTML for one code-sample card. Shared by the Markdown embed and the standalone sample component. */
export function sampleCardHtml(sample: CodeSample | null, key: string, ctx: RenderContext, id: string): string {
	if (!sample) {
		return `<div class="code-sample code-sample--missing card" data-sample-key="${escapeHtml(key)}"><span class="code-sample__meta">No sample “${escapeHtml(key)}” for ${escapeHtml(ctx.languageLabel || ctx.language || "this language")} at ${escapeHtml(ctx.versionLabel)}</span></div>`;
	}

	const title = sample.title ?? sample.key;
	const context = [ctx.versionLabel, ctx.languageLabel || sample.language].filter(Boolean).join(" + ");
	const tested = sample.isTested && sample.lastTestPassLabel ? `<span class="code-sample__tested"> · tested on ${escapeHtml(sample.lastTestPassLabel)}</span>` : "";

	return (
		`<figure class="code-sample card" data-sample-key="${escapeHtml(sample.key)}" data-language="${escapeHtml(sample.language)}">` +
		`<figcaption class="code-sample__bar">` +
		`<span class="code-sample__meta"><span class="code-sample__title">${escapeHtml(title)}</span><span class="code-sample__context"> · rendered for ${escapeHtml(context)}</span>${tested}</span>` +
		`<button type="button" class="code-sample__copy" data-copy="${id}">Copy</button>` +
		`</figcaption>` +
		`<pre class="code-sample__pre"><code id="${id}" class="hljs language-${escapeHtml(sample.language)}">${highlight(sample.code.replace(/\n$/, ""), sample.language)}</code></pre>` +
		`</figure>`
	);
}

function parseAttrs(raw: string): Record<string, string> {
	const attrs: Record<string, string> = {};

	for (const match of raw.matchAll(/([A-Za-z_][\w-]*)=("[^"]*"|'[^']*'|[^\s}]+)/g)) {
		attrs[match[1]] = match[2].replace(/^["']|["']$/g, "");
	}

	return attrs;
}

function symRefRule(state: StateInline, silent: boolean): boolean {
	const src = state.src;
	const start = state.pos;

	if (src.charCodeAt(start) !== 0x7b /* { */ || !src.startsWith("{sym:", start)) {
		return false;
	}

	const end = src.indexOf("}", start + 5);

	if (end < 0) {
		return false;
	}

	if (!silent) {
		const token = state.push("sym_ref", "", 0);

		token.meta = { ref: src.slice(start + 5, end).trim() };
	}

	state.pos = end + 1;

	return true;
}

function sampleEmbedRule(state: StateBlock, startLine: number, _endLine: number, silent: boolean): boolean {
	const lineStart = state.bMarks[startLine] + state.tShift[startLine];
	const lineEnd = state.eMarks[startLine];
	const line = state.src.slice(lineStart, lineEnd).trim();
	const match = /^<<sample:([\w.-]+)>>$/.exec(line);

	if (!match) {
		return false;
	}

	if (!silent) {
		const token = state.push("sample_embed", "", 0);

		token.meta = { key: match[1] };
		token.map = [startLine, startLine + 1];
	}

	state.line = startLine + 1;

	return true;
}

function metaOf(token: Token): Record<string, unknown> {
	return token.meta ?? {};
}

function createMarkdown(): MarkdownItInstance {
	const md = new MarkdownIt({ html: false, linkify: false, typographer: false });

	md.inline.ruler.before("emphasis", "sym_ref", symRefRule);
	md.block.ruler.before("paragraph", "sample_embed", sampleEmbedRule);

	md.renderer.rules.sym_ref = (tokens, idx, _options, rawEnv) => {
		const env = rawEnv as unknown as Env;
		const ref = String(metaOf(tokens[idx]).ref ?? "");
		const info = env.symbols.get(ref);
		const fallbackName = ref.includes("#") ? ref.slice(ref.indexOf("#") + 1) : ref;
		const shortName = fallbackName.includes(".") ? fallbackName.slice(fallbackName.lastIndexOf(".") + 1) : fallbackName;

		if (!info) {
			return `<code class="sym-ref sym-ref--unresolved" data-symbol-ref="${escapeHtml(ref)}">${escapeHtml(shortName)}</code>`;
		}

		const classes = ["sym-ref", info.removed ? "removed" : ""].filter(Boolean).join(" ");

		return `<a href="${escapeHtml(info.route)}" class="${classes}" data-symbol-ref="${escapeHtml(ref)}" title="${escapeHtml(info.name)}">${escapeHtml(info.shortName)}</a>`;
	};

	md.renderer.rules.sample_embed = (tokens, idx, _options, rawEnv) => {
		const env = rawEnv as unknown as Env;
		const key = String(metaOf(tokens[idx]).key ?? "");
		const { sample } = pickSample(env.samples, key, env.language, env.packageManager);

		env.counter += 1;

		return sampleCardHtml(sample, key, env, `sample-${env.counter}-${key.replace(/[^\w-]/g, "")}`);
	};

	md.renderer.rules.fence = (tokens, idx) => {
		const token = tokens[idx];
		const lang = token.info.trim().split(/\s+/)[0] ?? "";

		return `<pre class="code-block"><code class="hljs${lang ? ` language-${escapeHtml(lang)}` : ""}">${highlight(token.content.replace(/\n$/, ""), lang)}</code></pre>\n`;
	};

	md.use(container, "card", {
		validate: (params: string) => /^card(\[.*\])?\s*$/.test(params.trim()),
		render: (tokens, idx) => {
			if (tokens[idx].nesting === 1) {
				const label = /\[(.*)\]/.exec(tokens[idx].info)?.[1] ?? "";

				return `<section class="card doc-card">${label ? `<div class="doc-card__label">${escapeHtml(label)}</div>` : ""}<div class="doc-card__body">\n`;
			}

			return "</div></section>\n";
		},
	});

	md.use(container, "upgrade", {
		validate: (params: string) => /^upgrade\s*\{.*\}\s*$/.test(params.trim()),
		render: (tokens, idx, _options, rawEnv) => {
			const env = rawEnv as unknown as Env;
			const token = tokens[idx];

			if (token.nesting === 1) {
				const attrs = parseAttrs(token.info);
				const from = attrs.from ?? "";
				const to = attrs.to ?? "";
				const full = env.showUpgradeNote ? env.showUpgradeNote(from, to) : true;
				const href = `/upgrade/${encodeURIComponent(from)}/${encodeURIComponent(to)}`;
				const range = `${escapeHtml(from)} → ${escapeHtml(to)}`;

				token.meta = { from, to, full };

				if (!full) {
					return (
						`<aside class="upgrade-note upgrade-note--collapsed" data-upgrade-from="${escapeHtml(from)}" data-upgrade-to="${escapeHtml(to)}">` +
						`<span class="pill pill--warn">changed ${escapeHtml(to)}</span> <a href="${href}">See the ${range} diff</a>` +
						`<div class="upgrade-note__body" hidden>\n`
					);
				}

				return (
					`<aside class="card card--warn upgrade-note" data-upgrade-from="${escapeHtml(from)}" data-upgrade-to="${escapeHtml(to)}">` +
					`<div class="upgrade-note__label">Upgrade note · ${range}</div>` +
					`<div class="upgrade-note__body">\n`
				);
			}

			// Find the matching opening token to know how it rendered.
			let depth = 0;
			let openIdx = idx - 1;

			for (; openIdx >= 0; openIdx--) {
				if (tokens[openIdx].type === "container_upgrade_close") {
					depth++;
				} else if (tokens[openIdx].type === "container_upgrade_open") {
					if (depth === 0) {
						break;
					}

					depth--;
				}
			}

			const meta = openIdx >= 0 ? metaOf(tokens[openIdx]) : {};
			const from = String(meta.from ?? "");
			const to = String(meta.to ?? "");

			if (meta.full === false) {
				return "</div></aside>\n";
			}

			const href = `/upgrade/${encodeURIComponent(from)}/${encodeURIComponent(to)}`;

			return `</div><a class="upgrade-note__link" href="${href}">See the full ${escapeHtml(from)} → ${escapeHtml(to)} diff</a></aside>\n`;
		},
	});

	return md;
}

let instance: MarkdownItInstance | null = null;

const inlineDefaults: RenderContext = { symbols: new Map(), samples: [], language: null, languageLabel: "", packageManager: null, versionLabel: "" };

/** Renders one line of Markdown (emphasis, code, links, `{sym:ref}`) without a wrapping paragraph. */
export function renderInline(text: string, ctx: Partial<RenderContext> = {}): string {
	instance ??= createMarkdown();

	const env: Env = { ...inlineDefaults, ...ctx, counter: 0 };

	return instance.renderInline(text, env as unknown as MarkdownItEnv);
}

/** Renders a page body (Markdown with directives) to HTML for the given reader context. */
export function renderDocBody(body: string, ctx: RenderContext): string {
	instance ??= createMarkdown();

	const env: Env = { ...ctx, counter: 0 };

	return instance.render(body, env as unknown as MarkdownItEnv);
}
