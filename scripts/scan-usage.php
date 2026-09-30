<?php

	const STOIC_CORE_PATH = './';
	require_once(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Utilities\ConsoleHelper;

	use Zibings\CliScriptHelper;
	use Zibings\DocUsageScanner;

	$ch     = new ConsoleHelper($argv);
	$script = new CliScriptHelper(
		"Usage Scanner",
		"Scans a PHP project for the symbols it uses from one library and writes the scan report the docs site's upgrade view loads for its 'only symbols my code imports' filter. The report never leaves the machine unless you upload it."
	)->addExample(
		<<< EXAMPLE
- A project that installs the library through Composer (namespaces and version read from composer.lock)

   php scripts/scan-usage.php --project /path/to/app --package stoic/web --out scan.json
EXAMPLE
	)->addExample(
		<<< EXAMPLE
- No composer.lock: name the namespaces yourself

   php scripts/scan-usage.php --project . --package acme/widgets --namespaces "Acme\\Widgets" --out scan.json
EXAMPLE
	)->addOption("project", "d", "project", "Project to scan", "Root directory of the consuming project", false, ".")
	->addOption("package", "k", "package", "Library package", "Composer package name of the documented library (vendor/name)", true)
	->addOption("namespaces", "n", "namespaces", "Library namespaces", "Comma-separated namespaces to match; defaults to the package's PSR-4 roots from composer.lock", false, "")
	->addOption("exclude", "x", "exclude", "Folders to skip", "Comma-separated folder names to skip", false, "vendor,node_modules,.git")
	->addOption("out", "o", "out", "Report file to write", "Path of the JSON report to write; '-' writes to stdout", false, "-");

	$opts    = $script->startScript($ch)->getOptions($ch);
	$scanner = new DocUsageScanner();

	try {
		$report = $scanner->scan(
			(string) $opts['project'],
			(string) $opts['package'],
			array_filter(array_map('trim', explode(',', (string) $opts['namespaces']))),
			array_filter(array_map('trim', explode(',', (string) $opts['exclude'])))
		);
	} catch (\Throwable $ex) {
		$ch->putLine("ERROR: {$ex->getMessage()}");

		exit(1);
	}

	foreach ($scanner->errors as $error) {
		$ch->putLine("  skipped {$error}");
	}

	$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	if ((string) $opts['out'] === '-') {
		echo $json, "\n";
	} else {
		file_put_contents((string) $opts['out'], $json . "\n");
		$ch->putLine("Wrote {$opts['out']}: " . count($report['imports']) . " symbol(s) from {$report['package']}" . ($report['fromVersion'] ? " {$report['fromVersion']}" : ''));
	}
