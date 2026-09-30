import hljs from "highlight.js/lib/core";
import bash from "highlight.js/lib/languages/bash";
import csharp from "highlight.js/lib/languages/csharp";
import css from "highlight.js/lib/languages/css";
import go from "highlight.js/lib/languages/go";
import java from "highlight.js/lib/languages/java";
import javascript from "highlight.js/lib/languages/javascript";
import json from "highlight.js/lib/languages/json";
import php from "highlight.js/lib/languages/php";
import python from "highlight.js/lib/languages/python";
import rust from "highlight.js/lib/languages/rust";
import typescript from "highlight.js/lib/languages/typescript";
import xml from "highlight.js/lib/languages/xml";
import yaml from "highlight.js/lib/languages/yaml";

// A deliberately small set of grammars; the theme in styles/code.css maps token classes onto design tokens.
const grammars: Record<string, Parameters<typeof hljs.registerLanguage>[1]> = {
	bash,
	csharp,
	css,
	go,
	java,
	javascript,
	json,
	php,
	python,
	rust,
	typescript,
	xml,
	yaml,
};

for (const [name, grammar] of Object.entries(grammars)) {
	hljs.registerLanguage(name, grammar);
}

// Context-option keys and common shorthands → grammar names.
const aliases: Record<string, string> = {
	ts: "typescript",
	tsx: "typescript",
	js: "javascript",
	jsx: "javascript",
	mjs: "javascript",
	sh: "bash",
	shell: "bash",
	zsh: "bash",
	console: "bash",
	yml: "yaml",
	html: "xml",
	vue: "xml",
	cs: "csharp",
	py: "python",
	rs: "rust",
	golang: "go",
};

export function resolveLanguage(key: string | null | undefined): string | null {
	if (!key) {
		return null;
	}

	const lower = key.toLowerCase();
	const name = aliases[lower] ?? lower;

	return hljs.getLanguage(name) ? name : null;
}

export function escapeHtml(text: string): string {
	return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

/** Returns highlighted HTML for the code, or escaped plain text when the language is unknown. */
export function highlight(code: string, language: string | null | undefined): string {
	const name = resolveLanguage(language);

	if (!name) {
		return escapeHtml(code);
	}

	try {
		return hljs.highlight(code, { language: name, ignoreIllegals: true }).value;
	} catch {
		return escapeHtml(code);
	}
}
