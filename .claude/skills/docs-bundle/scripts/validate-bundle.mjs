#!/usr/bin/env node
// Validates a documentation bundle before import. No dependencies; run with any Node 18+.
//
//   node validate-bundle.mjs path/to/bundle.json
//
// Exits 1 when there are errors. Errors are things the importer would reject or the site would render wrong
// (dangling references, invalid enum values, overlapping contract ranges). Warnings are things that are legal but
// almost always mistakes (a reference page with no subject, a lesson with no titled samples).
//
// Mirrors the importer's rules as documented in reference/bundle-format.md. For a hand-written bundle this file plus
// that reference are the contract: if the importer later rejects a bundle this file passed, that is a bug here, so
// report it against the docs-site repo.

import { readFileSync } from "node:fs";

const MODES = ["learn", "do", "reference", "explain", "home"];
const SYMBOL_KINDS = ["fn", "class", "method", "type", "option", "const", "error"];
const STATUSES = ["stable", "experimental", "deprecated"];
const CHANGE_KINDS = ["breaking", "behavior", "deprecated", "added", "removed"];
const ROLES = ["subject", "mentions"];
const OPTION_KINDS = ["language", "packageManager"];

const errors = [];
const warnings = [];

function error(path, message) {
	errors.push(`${path}: ${message}`);
}

function warn(path, message) {
	warnings.push(`${path}: ${message}`);
}

function isObject(value) {
	return typeof value === "object" && value !== null && !Array.isArray(value);
}

function isString(value) {
	return typeof value === "string";
}

function nonEmptyString(value) {
	return isString(value) && value.trim() !== "";
}

function isInt(value) {
	return Number.isInteger(value);
}

function arrayOrEmpty(value, path) {
	if (value === undefined || value === null) {
		return [];
	}

	if (!Array.isArray(value)) {
		error(path, "must be an array");

		return [];
	}

	return value;
}

function main() {
	const file = process.argv[2];

	if (!file) {
		console.error("usage: node validate-bundle.mjs path/to/bundle.json");
		process.exit(2);
	}

	let bundle;

	try {
		bundle = JSON.parse(readFileSync(file, "utf8"));
	} catch (err) {
		console.error(`Could not read ${file}: ${err.message}`);
		process.exit(2);
	}

	if (!isObject(bundle)) {
		console.error("The bundle must be a JSON object.");
		process.exit(1);
	}

	for (const key of Object.keys(bundle)) {
		if (!["contextOptions", "versions", "modules", "pages", "changes", "courses"].includes(key)) {
			warn(key, "unknown top-level key; the importer ignores it");
		}
	}

	const ctx = validateContextOptions(arrayOrEmpty(bundle.contextOptions, "contextOptions"));
	const versions = validateVersions(arrayOrEmpty(bundle.versions, "versions"));
	const symbolRefs = validateModules(arrayOrEmpty(bundle.modules, "modules"), versions);
	const pageRefs = validatePages(arrayOrEmpty(bundle.pages, "pages"), versions, symbolRefs, ctx);
	validateChanges(arrayOrEmpty(bundle.changes, "changes"), versions, symbolRefs);
	validateCourses(arrayOrEmpty(bundle.courses, "courses"), pageRefs);

	const counts = {
		contextOptions: arrayOrEmpty(bundle.contextOptions).length,
		versions: versions.size,
		modules: arrayOrEmpty(bundle.modules).length,
		symbols: symbolRefs.size,
		pages: arrayOrEmpty(bundle.pages).length,
		changes: arrayOrEmpty(bundle.changes).length,
		courses: arrayOrEmpty(bundle.courses).length,
	};

	console.log(Object.entries(counts).map(([k, v]) => `${k} ${v}`).join(", "));

	for (const w of warnings) {
		console.log(`warning  ${w}`);
	}

	for (const e of errors) {
		console.log(`error    ${e}`);
	}

	console.log(`${errors.length} error(s), ${warnings.length} warning(s)`);
	process.exit(errors.length > 0 ? 1 : 0);
}

function validateContextOptions(options) {
	const keys = { language: new Set(), packageManager: new Set() };
	const defaults = { language: 0, packageManager: 0 };

	options.forEach((opt, i) => {
		const path = `contextOptions[${i}]`;

		if (!isObject(opt)) {
			error(path, "must be an object");

			return;
		}

		if (!OPTION_KINDS.includes(opt.kind)) {
			error(`${path}.kind`, `must be one of ${OPTION_KINDS.join(", ")}`);

			return;
		}

		if (!nonEmptyString(opt.key)) {
			error(`${path}.key`, "required");

			return;
		}

		if (keys[opt.kind].has(opt.key)) {
			error(`${path}.key`, `duplicate ${opt.kind} key '${opt.key}'`);
		}

		keys[opt.kind].add(opt.key);

		if (opt.default === true) {
			defaults[opt.kind]++;
		}

		if (opt.sortOrder !== undefined && !isInt(opt.sortOrder)) {
			error(`${path}.sortOrder`, "must be an integer");
		}
	});

	for (const kind of OPTION_KINDS) {
		if (keys[kind].size > 0 && defaults[kind] === 0) {
			warn("contextOptions", `no ${kind} option has "default": true; the first one will be used`);
		}

		if (defaults[kind] > 1) {
			error("contextOptions", `${defaults[kind]} ${kind} options have "default": true; only one may`);
		}
	}

	if (keys.language.size === 0) {
		warn("contextOptions", "no language options; every sample must still name a language");
	}

	return keys;
}

function validateVersions(list) {
	const versions = new Map();
	let latest = 0;
	let previousSortKey = null;

	list.forEach((ver, i) => {
		const path = `versions[${i}]`;

		if (!isObject(ver)) {
			error(path, "must be an object");

			return;
		}

		if (!nonEmptyString(ver.label)) {
			error(`${path}.label`, "required");

			return;
		}

		if (versions.has(ver.label)) {
			error(`${path}.label`, `duplicate version label '${ver.label}'`);
		}

		if (/[\s/?#%]/.test(ver.label)) {
			error(`${path}.label`, "is a URL segment; no spaces, slashes, ?, #, or %");
		}

		if (!isInt(ver.sortKey)) {
			error(`${path}.sortKey`, "must be an integer");
		} else {
			if (previousSortKey !== null && ver.sortKey <= previousSortKey) {
				error(`${path}.sortKey`, "versions must be listed oldest first with strictly increasing sortKey");
			}

			previousSortKey = ver.sortKey;
		}

		if (!nonEmptyString(ver.tag)) {
			warn(`${path}.tag`, "empty; the release tag is shown next to the label and used in source links");
		}

		if (ver.releasedAt !== undefined && ver.releasedAt !== null && !/^\d{4}-\d{2}-\d{2}$/.test(String(ver.releasedAt))) {
			error(`${path}.releasedAt`, "must be YYYY-MM-DD or null");
		}

		if (ver.latest === true) {
			latest++;
		}

		versions.set(ver.label, { sortKey: isInt(ver.sortKey) ? ver.sortKey : 0, path });
	});

	if (versions.size === 0) {
		error("versions", "at least one version is required; every contract and page needs an introduced version");
	} else if (latest === 0) {
		warn("versions", 'no version has "latest": true; /latest will have nothing to redirect to unless the install already has one');
	} else if (latest > 1) {
		error("versions", `${latest} versions have "latest": true; exactly one may`);
	}

	return versions;
}

function versionSortKey(versions, label) {
	return versions.get(label)?.sortKey ?? null;
}

function validateModules(modules, versions) {
	const refs = new Set();
	const paths = new Set();

	modules.forEach((mod, i) => {
		const path = `modules[${i}]`;

		if (!isObject(mod)) {
			error(path, "must be an object");

			return;
		}

		if (!nonEmptyString(mod.path)) {
			error(`${path}.path`, "required");

			return;
		}

		if (mod.path.includes("#")) {
			error(`${path}.path`, "must not contain '#'");
		}

		if (paths.has(mod.path)) {
			error(`${path}.path`, `duplicate module path '${mod.path}'`);
		}

		paths.add(mod.path);

		if (!nonEmptyString(mod.summary)) {
			warn(`${path}.summary`, "empty; shown in the browse tree, Reference index, and landing page");
		}

		validateSymbols(arrayOrEmpty(mod.symbols, `${path}.symbols`), `${path}.symbols`, mod.path, "", versions, refs);
	});

	return refs;
}

function validateSymbols(symbols, path, modulePath, prefix, versions, refs) {
	const names = new Set();

	symbols.forEach((sym, i) => {
		const symPath = `${path}[${i}]`;

		if (!isObject(sym)) {
			error(symPath, "must be an object");

			return;
		}

		if (!nonEmptyString(sym.name)) {
			error(`${symPath}.name`, "required");

			return;
		}

		if (/[.#\s]/.test(sym.name)) {
			error(`${symPath}.name`, "must not contain '.', '#', or whitespace (nesting is expressed with children)");
		}

		if (names.has(sym.name)) {
			error(`${symPath}.name`, `duplicate sibling symbol '${sym.name}'`);
		}

		names.add(sym.name);

		if (!SYMBOL_KINDS.includes(sym.kind)) {
			error(`${symPath}.kind`, `must be one of ${SYMBOL_KINDS.join(", ")}`);
		}

		const ref = `${modulePath}#${prefix}${sym.name}`;
		refs.add(ref);

		const rows = arrayOrEmpty(sym.versions, `${symPath}.versions`);

		if (rows.length === 0) {
			warn(`${symPath}.versions`, `'${ref}' has no contract rows, so it is invisible at every version`);
		}

		validateContracts(rows, `${symPath}.versions`, ref, versions);
		validateSymbols(arrayOrEmpty(sym.children, `${symPath}.children`), `${symPath}.children`, modulePath, `${prefix}${sym.name}.`, versions, refs);
	});
}

function validateContracts(rows, path, ref, versions) {
	const ranges = [];

	rows.forEach((row, i) => {
		const rowPath = `${path}[${i}]`;

		if (!isObject(row)) {
			error(rowPath, "must be an object");

			return;
		}

		if (!nonEmptyString(row.introduced)) {
			error(`${rowPath}.introduced`, "required (the importer rejects a contract without one)");

			return;
		}

		if (!versions.has(row.introduced)) {
			error(`${rowPath}.introduced`, `unknown version '${row.introduced}'`);

			return;
		}

		let removedKey = Infinity;

		if (row.removed !== undefined && row.removed !== null) {
			if (!versions.has(row.removed)) {
				error(`${rowPath}.removed`, `unknown version '${row.removed}'`);
			} else {
				removedKey = versionSortKey(versions, row.removed);

				if (removedKey <= versionSortKey(versions, row.introduced)) {
					error(`${rowPath}.removed`, "must be a later version than introduced");
				}
			}
		}

		if (row.status !== undefined && !STATUSES.includes(row.status)) {
			error(`${rowPath}.status`, `must be one of ${STATUSES.join(", ")}`);
		}

		if (!nonEmptyString(row.signature)) {
			warn(`${rowPath}.signature`, "empty; the contract pane shows the signature first");
		}

		if (!nonEmptyString(row.summary)) {
			warn(`${rowPath}.summary`, "empty; used in search results and the browse tree");
		}

		if (row.sourceLine !== undefined && row.sourceLine !== null && !isInt(row.sourceLine)) {
			error(`${rowPath}.sourceLine`, "must be an integer");
		}

		if (row.params !== undefined) {
			if (!Array.isArray(row.params)) {
				error(`${rowPath}.params`, "must be an array");
			} else {
				row.params.forEach((param, p) => {
					const paramPath = `${rowPath}.params[${p}]`;

					if (!isObject(param) || !nonEmptyString(param.name)) {
						error(paramPath, "each param needs a name");

						return;
					}

					if (!isString(param.type)) {
						error(`${paramPath}.type`, "must be a string");
					}

					if (param.required !== undefined && typeof param.required !== "boolean") {
						error(`${paramPath}.required`, "must be a boolean");
					}

					if (param.default !== undefined && param.default !== null && !isString(param.default)) {
						error(`${paramPath}.default`, "must be a string of source text, or null");
					}

					if (param.since !== undefined && param.since !== null && !versions.has(param.since)) {
						error(`${paramPath}.since`, `unknown version '${param.since}'`);
					}
				});
			}
		}

		if (row.returns !== undefined && row.returns !== null && !isObject(row.returns)) {
			error(`${rowPath}.returns`, "must be an object ({type, description}) or {}");
		}

		if (row.throws !== undefined && row.throws !== null) {
			if (!Array.isArray(row.throws)) {
				error(`${rowPath}.throws`, "must be an array");
			} else {
				row.throws.forEach((t, k) => {
					if (!isObject(t) || !nonEmptyString(t.type)) {
						error(`${rowPath}.throws[${k}]`, "each entry needs a type");
					}
				});
			}
		}

		ranges.push({ from: versionSortKey(versions, row.introduced), to: removedKey, path: rowPath, introduced: row.introduced });
	});

	const seen = new Set();

	for (const range of ranges) {
		if (seen.has(range.introduced)) {
			error(range.path, `two contract rows of '${ref}' share introduced '${range.introduced}'; the importer would overwrite one with the other`);
		}

		seen.add(range.introduced);
	}

	ranges.sort((a, b) => a.from - b.from);

	for (let i = 1; i < ranges.length; i++) {
		if (ranges[i].from < ranges[i - 1].to) {
			error(ranges[i].path, `contract range overlaps the previous row of '${ref}'; set the earlier row's removed to '${ranges[i].introduced}'`);
		}
	}
}

const SAMPLE_EMBED = /^<<sample:([\w.-]+)>>$/gm;
const SYM_REF = /\{sym:([^}]*)\}/g;
const UPGRADE_BLOCK = /^:::upgrade\s*\{([^}]*)\}/gm;
const FENCE = /```[\s\S]*?```/g;

function validatePages(pages, versions, symbolRefs, ctx) {
	const pageRefs = new Map();
	const naturalKeys = new Set();
	let homeCount = 0;

	pages.forEach((page, i) => {
		const path = `pages[${i}]`;

		if (!isObject(page)) {
			error(path, "must be an object");

			return;
		}

		if (!MODES.includes(page.mode)) {
			error(`${path}.mode`, `must be one of ${MODES.join(", ")}`);

			return;
		}

		if (!nonEmptyString(page.slug)) {
			error(`${path}.slug`, "required");

			return;
		}

		const ref = `${page.mode}/${page.slug}`;

		if (!nonEmptyString(page.title)) {
			error(`${path}.title`, "required (the page model rejects an empty title)");
		}

		if (!nonEmptyString(page.introduced)) {
			error(`${path}.introduced`, "required (the importer rejects a page without one)");
		} else if (!versions.has(page.introduced)) {
			error(`${path}.introduced`, `unknown version '${page.introduced}'`);
		}

		if (page.removed !== undefined && page.removed !== null) {
			if (!versions.has(page.removed)) {
				error(`${path}.removed`, `unknown version '${page.removed}'`);
			} else if (versions.has(page.introduced) && versionSortKey(versions, page.removed) <= versionSortKey(versions, page.introduced)) {
				error(`${path}.removed`, "must be a later version than introduced");
			}
		}

		const naturalKey = `${ref}@${page.introduced}`;

		if (naturalKeys.has(naturalKey)) {
			error(path, `duplicate page ${ref} introduced at ${page.introduced}`);
		}

		naturalKeys.add(naturalKey);
		pageRefs.set(ref, { mode: page.mode, path });

		if (page.minutes !== undefined && page.minutes !== null && !isInt(page.minutes)) {
			error(`${path}.minutes`, "must be an integer or null");
		}

		if (!nonEmptyString(page.summary) && page.mode !== "reference") {
			warn(`${path}.summary`, "empty; shown in mode indexes, search, and the landing page");
		}

		if (page.mode === "home") {
			homeCount++;

			if (page.slug !== "index") {
				error(`${path}.slug`, "the home page's slug must be 'index'");
			}
		}

		// Symbol links.
		const linked = new Set();
		let subjects = 0;
		let subjectRef = null;

		arrayOrEmpty(page.symbols, `${path}.symbols`).forEach((link, k) => {
			const linkPath = `${path}.symbols[${k}]`;

			if (!isObject(link) || !nonEmptyString(link.ref)) {
				error(linkPath, "each link needs a ref");

				return;
			}

			if (!symbolRefs.has(link.ref)) {
				error(`${linkPath}.ref`, `unknown symbol '${link.ref}'`);
			}

			if (link.role !== undefined && !ROLES.includes(link.role)) {
				error(`${linkPath}.role`, `must be one of ${ROLES.join(", ")}`);
			}

			if (link.role === "subject") {
				subjects++;
				subjectRef = link.ref;
			}

			linked.add(link.ref);
		});

		if (page.mode === "reference" && subjects === 0) {
			warn(`${path}.symbols`, "reference page with no 'subject' link; it will not appear on any symbol page");
		}

		if (page.mode === "reference" && subjectRef) {
			const expectedSlug = subjectRef.replace("#", "/");

			if (page.slug !== expectedSlug) {
				warn(`${path}.slug`, `reference pages conventionally use the subject's path, '${expectedSlug}'`);
			}
		}

		if (subjects > 1) {
			warn(`${path}.symbols`, "more than one 'subject'; only one reference page is shown per symbol");
		}

		// Samples.
		const sampleKeys = new Set();
		const sampleNaturalKeys = new Set();
		let titled = 0;

		arrayOrEmpty(page.samples, `${path}.samples`).forEach((sample, k) => {
			const samplePath = `${path}.samples[${k}]`;

			if (!isObject(sample) || !nonEmptyString(sample.key)) {
				error(samplePath, "each sample needs a key");

				return;
			}

			if (!/^[\w.-]+$/.test(sample.key)) {
				error(`${samplePath}.key`, "only letters, digits, '.', '_', and '-' can be embedded with <<sample:key>>");
			}

			if (!nonEmptyString(sample.language)) {
				error(`${samplePath}.language`, "required");
			} else if (ctx.language.size > 0 && !ctx.language.has(sample.language)) {
				error(`${samplePath}.language`, `'${sample.language}' is not a language context option key`);
			}

			if (sample.variant !== undefined && sample.variant !== null) {
				if (!isString(sample.variant)) {
					error(`${samplePath}.variant`, "must be a package-manager key or null");
				} else if (ctx.packageManager.size > 0 && !ctx.packageManager.has(sample.variant)) {
					error(`${samplePath}.variant`, `'${sample.variant}' is not a packageManager context option key`);
				}
			}

			if (!isString(sample.code) || sample.code.trim() === "") {
				error(`${samplePath}.code`, "required");
			}

			if (sample.title !== undefined && sample.title !== null && !isString(sample.title)) {
				error(`${samplePath}.title`, "must be a string or null");
			}

			if (nonEmptyString(sample.title)) {
				titled++;
			}

			if (sample.tested === true && !nonEmptyString(sample.lastTestPass)) {
				warn(`${samplePath}.lastTestPass`, "tested is true but lastTestPass is empty");
			}

			if (sample.lastTestPass !== undefined && sample.lastTestPass !== null && !versions.has(sample.lastTestPass)) {
				error(`${samplePath}.lastTestPass`, `unknown version '${sample.lastTestPass}'`);
			}

			const natural = `${sample.key}|${sample.language}|${sample.variant ?? ""}`;

			if (sampleNaturalKeys.has(natural)) {
				error(samplePath, `duplicate sample ${natural}; key + language + variant must be unique on a page`);
			}

			sampleNaturalKeys.add(natural);
			sampleKeys.add(sample.key);
		});

		if (page.mode === "learn" && titled === 0) {
			warn(`${path}.samples`, "lesson page with no titled samples; the Learn workspace will have no file tabs");
		}

		// Body directives.
		const body = isString(page.body) ? page.body : "";

		if (!isString(page.body)) {
			error(`${path}.body`, "must be a string");
		}

		const prose = body.replace(FENCE, "");
		const embedded = new Set();

		for (const match of prose.matchAll(SAMPLE_EMBED)) {
			embedded.add(match[1]);

			if (!sampleKeys.has(match[1])) {
				error(`${path}.body`, `<<sample:${match[1]}>> has no sample with that key on this page`);
			}
		}

		for (const key of sampleKeys) {
			if (!embedded.has(key) && page.mode !== "learn") {
				warn(`${path}.samples`, `sample '${key}' is never embedded with <<sample:${key}>>`);
			}
		}

		for (const match of prose.matchAll(SYM_REF)) {
			const target = match[1].trim();

			if (!symbolRefs.has(target)) {
				error(`${path}.body`, `{sym:${target}} does not resolve to a symbol in this bundle`);
			} else if (!linked.has(target) && page.mode !== "reference") {
				warn(`${path}.body`, `{sym:${target}} is used but not listed in symbols; it will render as plain text`);
			}
		}

		for (const match of prose.matchAll(UPGRADE_BLOCK)) {
			const attrs = Object.fromEntries([...match[1].matchAll(/(\w+)\s*=\s*"?([^"\s]+)"?/g)].map((m) => [m[1], m[2]]));

			for (const key of ["from", "to"]) {
				if (!attrs[key]) {
					error(`${path}.body`, `:::upgrade block is missing ${key}=`);
				} else if (!versions.has(attrs[key])) {
					error(`${path}.body`, `:::upgrade ${key}='${attrs[key]}' is not a known version`);
				}
			}
		}

		if (/^#\s/m.test(prose)) {
			warn(`${path}.body`, "contains a level-1 heading; the title is already the h1, start body headings at ##");
		}

		if (/\b(since|changed in|removed in|added in)\s+v?\d/i.test(prose)) {
			warn(`${path}.body`, "prose mentions version history; that belongs in contract rows and changes, not text");
		}
	});

	if (homeCount > 1) {
		const introduced = pages.filter((p) => isObject(p) && p.mode === "home").map((p) => p.introduced);

		if (new Set(introduced).size !== introduced.length) {
			error("pages", "more than one home page with the same introduced version");
		}
	}

	if (homeCount === 0) {
		warn("pages", "no home page (mode 'home', slug 'index'); the landing page will have no tagline or prose");
	}

	return pageRefs;
}

function validateChanges(changes, versions, symbolRefs) {
	const keys = new Set();

	changes.forEach((change, i) => {
		const path = `changes[${i}]`;

		if (!isObject(change)) {
			error(path, "must be an object");

			return;
		}

		if (!nonEmptyString(change.title)) {
			error(`${path}.title`, "required");
		}

		if (!nonEmptyString(change.version)) {
			error(`${path}.version`, "required (the importer rejects a change without one)");
		} else if (!versions.has(change.version)) {
			error(`${path}.version`, `unknown version '${change.version}'`);
		}

		if (!CHANGE_KINDS.includes(change.kind)) {
			error(`${path}.kind`, `must be one of ${CHANGE_KINDS.join(", ")}`);
		}

		const key = `${change.version}|${change.title}`;

		if (keys.has(key)) {
			error(path, `duplicate change '${change.title}' at ${change.version}`);
		}

		keys.add(key);

		if (!nonEmptyString(change.why)) {
			warn(`${path}.why`, "empty; the upgrade view shows the reason for each change");
		}

		if ((change.kind === "breaking" || change.kind === "behavior") && !(nonEmptyString(change.beforeCode) && nonEmptyString(change.afterCode))) {
			warn(path, `${change.kind} change without beforeCode and afterCode`);
		}

		const refs = arrayOrEmpty(change.symbols, `${path}.symbols`);

		if (refs.length === 0) {
			warn(`${path}.symbols`, "no symbols linked; the upgrade view cannot filter this change by what a reader imports");
		}

		refs.forEach((ref, k) => {
			if (!isString(ref) || !symbolRefs.has(ref)) {
				error(`${path}.symbols[${k}]`, `unknown symbol '${ref}'`);
			}
		});
	});
}

function validateCourses(courses, pageRefs) {
	const slugs = new Set();

	courses.forEach((course, i) => {
		const path = `courses[${i}]`;

		if (!isObject(course)) {
			error(path, "must be an object");

			return;
		}

		if (!nonEmptyString(course.slug)) {
			error(`${path}.slug`, "required");

			return;
		}

		if (slugs.has(course.slug)) {
			error(`${path}.slug`, `duplicate course slug '${course.slug}'`);
		}

		slugs.add(course.slug);

		if (!nonEmptyString(course.title)) {
			warn(`${path}.title`, "empty");
		}

		const lessons = arrayOrEmpty(course.lessons, `${path}.lessons`);
		const ordinals = new Set();

		if (lessons.length === 0) {
			warn(`${path}.lessons`, "course with no lessons");
		}

		lessons.forEach((lesson, l) => {
			const lessonPath = `${path}.lessons[${l}]`;

			if (!isObject(lesson)) {
				error(lessonPath, "must be an object");

				return;
			}

			const ordinal = lesson.ordinal ?? l + 1;

			if (!isInt(ordinal)) {
				error(`${lessonPath}.ordinal`, "must be an integer");
			} else if (ordinals.has(ordinal)) {
				error(`${lessonPath}.ordinal`, `duplicate lesson ordinal ${ordinal}`);
			}

			ordinals.add(ordinal);

			if (!nonEmptyString(lesson.page)) {
				error(`${lessonPath}.page`, "required, as 'learn/<slug>'");
			} else {
				const target = pageRefs.get(lesson.page);

				if (!target) {
					error(`${lessonPath}.page`, `'${lesson.page}' is not a page in this bundle (lesson pages must ship in the same bundle)`);
				} else if (target.mode !== "learn") {
					error(`${lessonPath}.page`, `'${lesson.page}' is a ${target.mode} page; lessons must be learn pages`);
				}
			}

			const steps = arrayOrEmpty(lesson.steps, `${lessonPath}.steps`);
			const stepOrdinals = new Set();

			if (steps.length === 0) {
				warn(`${lessonPath}.steps`, "lesson with no steps; the Try it card will be empty");
			}

			steps.forEach((step, s) => {
				const stepPath = `${lessonPath}.steps[${s}]`;

				if (!isObject(step)) {
					error(stepPath, "must be an object");

					return;
				}

				const stepOrdinal = step.ordinal ?? s + 1;

				if (!isInt(stepOrdinal)) {
					error(`${stepPath}.ordinal`, "must be an integer");
				} else if (stepOrdinals.has(stepOrdinal)) {
					error(`${stepPath}.ordinal`, `duplicate step ordinal ${stepOrdinal}`);
				}

				stepOrdinals.add(stepOrdinal);

				if (!nonEmptyString(step.prompt)) {
					error(`${stepPath}.prompt`, "required");
				}

			});
		});
	});
}

main();
