<?php

	const STOIC_CORE_PATH = './';
	require_once(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Utilities\ConsoleHelper;

	use Zibings\CliScriptHelper;
	use Zibings\DocOpenApiExtractor;
	use Zibings\DocSnapshotMerger;

	$ch     = new ConsoleHelper($argv);
	$script = new CliScriptHelper(
		"OpenAPI Importer",
		"Turns one or more OpenAPI 3 documents (JSON or YAML) into a documentation bundle. Give several documents, oldest first, to get contract version ranges; import the result with scripts/seed-docs.php or the admin Import page."
	)->addExample(
		<<< EXAMPLE
- One document, version taken from info.version

   php scripts/import-openapi.php --spec web/sui/openapi.yaml --out bundle.json
EXAMPLE
	)->addExample(
		<<< EXAMPLE
- Three releases with explicit labels

   php scripts/import-openapi.php --spec "v1.0=specs/1.0.yaml,v1.1=specs/1.1.yaml,v2.0=specs/2.0.json" --prefix api --out bundle.json
EXAMPLE
	)->addOption("spec", "s", "spec", "Documents to read", "Comma-separated list of documents, each optionally prefixed with a version label (label=path). Oldest first.", true)
	->addOption("out", "o", "out", "Bundle file to write", "Path of the JSON bundle to write; '-' writes to stdout", false, "-")
	->addOption("prefix", "p", "prefix", "Module path prefix", "Prefix for module paths (modules become prefix/tag and prefix/schemas)", false, "api");

	$opts      = $script->startScript($ch)->getOptions($ch);
	$extractor = new DocOpenApiExtractor();
	$snapshots = [];

	foreach (array_filter(array_map('trim', explode(',', (string) $opts['spec']))) as $entry) {
		$label = null;
		$path  = $entry;

		if (preg_match('/^([^=]+)=(.+)$/', $entry, $m)) {
			$label = trim($m[1]);
			$path  = trim($m[2]);
		}

		try {
			$snapshots[] = $extractor->snapshot(DocOpenApiExtractor::load($path), $label, (string) $opts['prefix']);
			$ch->putLine("Read {$path} as " . end($snapshots)['label']);
		} catch (\Throwable $ex) {
			$ch->putLine("ERROR: {$ex->getMessage()}");

			exit(1);
		}
	}

	$bundle = (new DocSnapshotMerger())->merge($snapshots, 'import-openapi.php');
	$json   = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$total  = array_sum(array_map(fn ($m) => count($m['symbols']), $bundle['modules']));

	if ((string) $opts['out'] === '-') {
		echo $json, "\n";
	} else {
		file_put_contents((string) $opts['out'], $json . "\n");
		$ch->putLine("Wrote {$opts['out']}: " . count($bundle['versions']) . " version(s), " . count($bundle['modules']) . " module(s), {$total} top-level symbol(s)");
	}
