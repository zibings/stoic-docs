import { spawnSync } from "node:child_process";
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";

import { afterEach, describe, expect, it } from "vitest";

// The agent-facing validator in .claude/skills/docs-bundle must agree with the fixture the importer round-trips,
// and it must reject the mistakes the importer would reject. Both are pinned here so the skill cannot drift.
const repoRoot = resolve(__dirname, "../../../..");
const validator = join(repoRoot, ".claude/skills/docs-bundle/scripts/validate-bundle.mjs");
const fixture = join(repoRoot, "fixtures/tessel.json");

function run(file: string): { status: number | null; out: string } {
	const result = spawnSync(process.execPath, [validator, file], { encoding: "utf8" });

	return { status: result.status, out: `${result.stdout}${result.stderr}` };
}

let dir: string | null = null;

afterEach(() => {
	if (dir) {
		rmSync(dir, { recursive: true, force: true });
		dir = null;
	}
});

function writeBundle(bundle: unknown): string {
	dir = mkdtempSync(join(tmpdir(), "bundle-"));
	const file = join(dir, "bundle.json");

	writeFileSync(file, JSON.stringify(bundle));

	return file;
}

describe("validate-bundle", () => {
	it("accepts the tessel fixture without errors", () => {
		const { status, out } = run(fixture);

		expect(out).toContain("0 error(s)");
		expect(status).toBe(0);
	});

	it("accepts the minimal example from the format reference", () => {
		const doc = readFileSync(join(repoRoot, ".claude/skills/docs-bundle/reference/bundle-format.md"), "utf8");
		const match = /## Minimal complete bundle\s+```json\n([\s\S]*?)```/.exec(doc);

		expect(match).not.toBeNull();

		const { status, out } = run(writeBundle(JSON.parse(match![1])));

		expect(out).toContain("0 error(s), 0 warning(s)");
		expect(status).toBe(0);
	});

	it("rejects dangling references and importer-fatal omissions", () => {
		const bundle = JSON.parse(readFileSync(fixture, "utf8"));

		bundle.pages[0].slug = "landing";
		bundle.pages[1].symbols[0].ref = "tessel/query#nope";
		bundle.pages[2].body += "\n\n<<sample:missing>>\n{sym:tessel/query#ghost}\n";
		bundle.changes[0].version = "v0.1";
		bundle.courses[0].lessons[0].page = "do/prefetch-on-hover";
		delete bundle.modules[0].symbols[0].versions[0].introduced;

		const { status, out } = run(writeBundle(bundle));

		expect(status).toBe(1);
		expect(out).toContain("pages[0].slug: the home page's slug must be 'index'");
		expect(out).toContain("unknown symbol 'tessel/query#nope'");
		expect(out).toContain("<<sample:missing>> has no sample");
		expect(out).toContain("{sym:tessel/query#ghost} does not resolve");
		expect(out).toContain("unknown version 'v0.1'");
		expect(out).toContain("lessons must be learn pages");
		expect(out).toContain("versions[0].introduced: required");
	});
});
