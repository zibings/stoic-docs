// Removes the previous build's output from APP_TARGET (or dist) using the list that build left behind, so the
// shared web/ directory (which also holds api/, sui/, .htaccess) never accumulates stale files.
import { existsSync, readFileSync, readdirSync, rmSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const here = dirname(fileURLToPath(import.meta.url));
const outDir = resolve(here, "..", process.env.APP_TARGET ?? "dist");
const listFile = resolve(outDir, ".ssg-output.json");

if (!existsSync(listFile)) {
	console.log(`clean-web: no previous output list in ${outDir}`);
	process.exit(0);
}

const { files } = JSON.parse(readFileSync(listFile, "utf8"));
let removed = 0;

for (const rel of files) {
	const full = resolve(outDir, rel);

	if (!full.startsWith(outDir + "/") || !existsSync(full)) {
		continue;
	}

	rmSync(full, { force: true });
	removed++;

	let parent = dirname(full);

	while (parent !== outDir && parent.startsWith(outDir + "/") && existsSync(parent) && readdirSync(parent).length === 0) {
		rmSync(parent, { recursive: true, force: true });
		parent = dirname(parent);
	}
}

rmSync(listFile, { force: true });
console.log(`clean-web: removed ${removed} files from ${outDir}`);
