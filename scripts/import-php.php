<?php

	const STOIC_CORE_PATH = './';
	require_once(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Utilities\ConsoleHelper;

	use Zibings\CliScriptHelper;
	use Zibings\DocPhpExtractor;
	use Zibings\DocSnapshotMerger;

	$ch     = new ConsoleHelper($argv);
	$script = new CliScriptHelper(
		"PHP Source Importer",
		"Extracts the public API (namespaces, classes, functions, public members) of one or more PHP source trees into a documentation bundle. Give several checkouts, oldest first, to get contract version ranges."
	)->addExample(
		<<< EXAMPLE
- One checkout

   php scripts/import-php.php --src "v1.4.0=vendor/stoic/web" --out bundle.json
EXAMPLE
	)->addExample(
		<<< EXAMPLE
- Three tags checked out side by side, skipping extra folders

   php scripts/import-php.php --src "v1.2.0=/tmp/lib-1.2,v1.3.0=/tmp/lib-1.3,v1.4.0=/tmp/lib-1.4" --exclude "vendor,tests,examples" --out bundle.json
EXAMPLE
	)->addOption("src", "s", "src", "Source trees to read", "Comma-separated list of label=path pairs, oldest first. Paths in the bundle are relative to each root.", true)
	->addOption("out", "o", "out", "Bundle file to write", "Path of the JSON bundle to write; '-' writes to stdout", false, "-")
	->addOption("exclude", "x", "exclude", "Folders to skip", "Comma-separated folder names to skip inside each root", false, "vendor,tests,test,node_modules");

	$opts      = $script->startScript($ch)->getOptions($ch);
	$extractor = new DocPhpExtractor();
	$exclude   = array_filter(array_map('trim', explode(',', (string) $opts['exclude'])));
	$snapshots = [];

	foreach (array_filter(array_map('trim', explode(',', (string) $opts['src']))) as $entry) {
		if (!preg_match('/^([^=]+)=(.+)$/', $entry, $m)) {
			$ch->putLine("ERROR: each --src entry needs a label, like v1.0=path (got '{$entry}')");

			exit(1);
		}

		try {
			$snapshots[] = $extractor->snapshot(trim($m[2]), trim($m[1]), $exclude);
		} catch (\Throwable $ex) {
			$ch->putLine("ERROR: {$ex->getMessage()}");

			exit(1);
		}

		$ch->putLine("Read {$m[2]} as {$m[1]}: " . count(end($snapshots)['modules']) . " namespace(s)");

		foreach ($extractor->errors as $error) {
			$ch->putLine("  skipped {$error}");
		}
	}

	$bundle = (new DocSnapshotMerger())->merge($snapshots, 'import-php.php');
	$json   = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$total  = array_sum(array_map(fn ($m) => count($m['symbols']), $bundle['modules']));

	if ((string) $opts['out'] === '-') {
		echo $json, "\n";
	} else {
		file_put_contents((string) $opts['out'], $json . "\n");
		$ch->putLine("Wrote {$opts['out']}: " . count($bundle['versions']) . " version(s), " . count($bundle['modules']) . " module(s), {$total} top-level symbol(s)");
	}
