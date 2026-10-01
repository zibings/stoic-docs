<?php

	/**
	 * Aggregates the hand-written parts of the Stoic:PHP bundle. Each file returns plain arrays in the bundle's shape
	 * (see .claude/skills/docs-bundle/reference/bundle-format.md); build.php merges them with the extracted API.
	 */

	require_once(__DIR__ . '/helpers.php');

	$learn = require(__DIR__ . '/learn.php');

	return [
		'contextOptions' => [
			['kind' => 'language',       'key' => 'php',      'label' => 'PHP',      'sortOrder' => 1, 'default' => true],
			['kind' => 'packageManager', 'key' => 'composer', 'label' => 'Composer', 'sortOrder' => 1, 'default' => true]
		],
		'modules' => require(__DIR__ . '/modules.php'),
		'pages'   => array_merge(
			require(__DIR__ . '/home.php'),
			require(__DIR__ . '/reference-core.php'),
			require(__DIR__ . '/reference-pdo.php'),
			require(__DIR__ . '/reference-web.php'),
			require(__DIR__ . '/do.php'),
			require(__DIR__ . '/explain.php'),
			$learn['pages']
		),
		'changes' => require(__DIR__ . '/changes.php'),
		'courses' => $learn['courses']
	];
