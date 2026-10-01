import hljs from "highlight.js/lib/core";
import bash from "highlight.js/lib/languages/bash";
import c from "highlight.js/lib/languages/c";
import cpp from "highlight.js/lib/languages/cpp";
import csharp from "highlight.js/lib/languages/csharp";
import css from "highlight.js/lib/languages/css";
import dart from "highlight.js/lib/languages/dart";
import diff from "highlight.js/lib/languages/diff";
import dockerfile from "highlight.js/lib/languages/dockerfile";
import go from "highlight.js/lib/languages/go";
import graphql from "highlight.js/lib/languages/graphql";
import ini from "highlight.js/lib/languages/ini";
import java from "highlight.js/lib/languages/java";
import javascript from "highlight.js/lib/languages/javascript";
import json from "highlight.js/lib/languages/json";
import kotlin from "highlight.js/lib/languages/kotlin";
import lua from "highlight.js/lib/languages/lua";
import makefile from "highlight.js/lib/languages/makefile";
import markdown from "highlight.js/lib/languages/markdown";
import objectivec from "highlight.js/lib/languages/objectivec";
import perl from "highlight.js/lib/languages/perl";
import php from "highlight.js/lib/languages/php";
import plaintext from "highlight.js/lib/languages/plaintext";
import powershell from "highlight.js/lib/languages/powershell";
import protobuf from "highlight.js/lib/languages/protobuf";
import python from "highlight.js/lib/languages/python";
import r from "highlight.js/lib/languages/r";
import ruby from "highlight.js/lib/languages/ruby";
import rust from "highlight.js/lib/languages/rust";
import scala from "highlight.js/lib/languages/scala";
import sql from "highlight.js/lib/languages/sql";
import swift from "highlight.js/lib/languages/swift";
import typescript from "highlight.js/lib/languages/typescript";
import xml from "highlight.js/lib/languages/xml";
import yaml from "highlight.js/lib/languages/yaml";

/*
 * The grammars the site can color. Each one is registered explicitly so the client bundle only carries what is
 * listed here (the full highlight.js language set is over a megabyte). To add one: import it from
 * `highlight.js/lib/languages/<name>` and add it to this map; the theme in styles/code.css maps every grammar's
 * token classes onto the design tokens, so no CSS changes are needed. Grammars also answer to the aliases they
 * declare themselves (`c++`, `hpp`, `kt`, `rb`, `ps1`, `toml`, `md`, ...); `aliases` below only adds the ones
 * highlight.js does not know.
 */
const grammars: Record<string, Parameters<typeof hljs.registerLanguage>[1]> = {
	bash,
	c,
	cpp,
	csharp,
	css,
	dart,
	diff,
	dockerfile,
	go,
	graphql,
	ini,
	java,
	javascript,
	json,
	kotlin,
	lua,
	makefile,
	markdown,
	objectivec,
	perl,
	php,
	plaintext,
	powershell,
	protobuf,
	python,
	r,
	ruby,
	rust,
	scala,
	sql,
	swift,
	typescript,
	xml,
	yaml,
};

for (const [name, grammar] of Object.entries(grammars)) {
	hljs.registerLanguage(name, grammar);
}

// Context-option keys and common shorthands → grammar names, for names highlight.js does not alias itself.
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
	cxx: "cpp",
	hxx: "cpp",
	hh: "cpp",
	hpp: "cpp",
	make: "makefile",
	mk: "makefile",
	dockerfile: "dockerfile",
	env: "ini",
	properties: "ini",
	mysql: "sql",
	pgsql: "sql",
	postgres: "sql",
	sqlite: "sql",
	text: "plaintext",
	txt: "plaintext",
	none: "plaintext",
};

/** All grammar names and aliases the highlighter answers to, sorted; for documentation and validation. */
export function supportedLanguages(): string[] {
	return hljs.listLanguages().flatMap((name) => [name, ...(hljs.getLanguage(name)?.aliases ?? [])])
		.concat(Object.keys(aliases))
		.filter((name, index, all) => all.indexOf(name) === index)
		.sort();
}

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
