<?php

	const STOIC_CORE_PATH = './';
	require_once(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Utilities\ConsoleHelper;

	use Zibings\CliScriptHelper;
	use Zibings\DocBundleImporter;

	global $Db, $Log, $Stoic;

	/**
	 * @var \Stoic\Pdo\PdoHelper $Db
	 * @var \Stoic\Log\Logger $Log
	 * @var \Stoic\Web\Stoic $Stoic
	 */

	$ch     = new ConsoleHelper($argv);
	$script = new CliScriptHelper(
		"Docs Bundle Importer",
		"Imports a documentation bundle (JSON) into the Doc* tables. Re-importing updates existing records in place."
	)->addExample(
		<<< EXAMPLE
- Seed the fictional 'tessel' fixture

   php scripts/seed-docs.php
EXAMPLE
	)->addExample(
		<<< EXAMPLE
- Wipe every documentation record, then import a bundle

   php scripts/seed-docs.php --reset --file path/to/bundle.json
EXAMPLE
	)->addOption(
		"file",
		"f",
		"file",
		"Bundle file to import",
		"Path to the JSON bundle to import, relative to the project root",
		false,
		"fixtures/tessel.json"
	)->addOption(
		"reset",
		"r",
		"reset",
		"Delete all docs records first",
		"Deletes every record in the Doc* tables before importing"
	);

	$opts     = $script->startScript($ch)->getOptions($ch);
	$importer = new DocBundleImporter($Db, $Log);

	if ($ch->hasShortLongArg('r', 'reset')) {
		$ch->put("Resetting documentation tables.. ");

		$reset = $importer->reset();

		if ($reset->isBad()) {
			$ch->putLine("ERROR");
			$ch->putLine($reset->getMessages()[0] ?? 'unknown error');

			exit(1);
		}

		$ch->putLine("DONE");
	}

	$file = $opts['file'];

	$ch->putLine("Importing bundle '{$file}'..");

	$result = $importer->importFile($file);

	if ($result->isBad()) {
		$ch->putLine("ERROR");
		$ch->putLine($result->getMessages()[0] ?? 'unknown error');

		exit(1);
	}

	foreach ($importer->counts as $type => $count) {
		$ch->putLine(sprintf("  %-16s %d", $type, $count));
	}

	$ch->putLine();
	$ch->putLine("Import complete");
	$ch->putLine();
