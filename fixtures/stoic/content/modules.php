<?php

	/**
	 * Module summaries and order. Paths are the lowercased namespaces the extractor produces.
	 */

	return [
		'stoic/utilities'            => ['sortOrder' => 1, 'summary' => 'Return values, class-based enums, strings, files, typed parameters, console scripts, and the built-in log appenders.'],
		'stoic/utilities/sanitizers' => ['sortOrder' => 2, 'summary' => 'The coercion strategies behind SanitationHelper and the typed getters of ParameterHelper.'],
		'stoic/chain'                => ['sortOrder' => 3, 'summary' => 'Send a dispatch object through an ordered chain of nodes; the mechanism under logging and API authorization.'],
		'stoic/log'                  => ['sortOrder' => 4, 'summary' => 'A PSR-3 logger that buffers messages and hands them to appenders when output() runs.'],
		'stoic/pdo'                  => ['sortOrder' => 5, 'summary' => 'PdoHelper, a PDO wrapper with stored queries and error capture, and the BaseDbModel mini-ORM.'],
		'stoic/web'                  => ['sortOrder' => 6, 'summary' => 'The Stoic singleton that boots a site, the Request wrapper, and helpers for pages, pagination, uploads, and HTML.'],
		'stoic/web/api'              => ['sortOrder' => 7, 'summary' => 'Routing, responses, and a base class for JSON API endpoints.'],
		'stoic/web/resources'        => ['sortOrder' => 8, 'summary' => 'Enums, structs, exceptions, and the string constants the web layer reads from siteSettings.json and $_SERVER.']
	];
