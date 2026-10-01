<?php

	/**
	 * Contract summaries for symbols whose source has no docblock: the constants of the enum and string-bag classes.
	 * Keyed by symbol ref. A closure receives the constant's name and its literal value and returns the summary, so
	 * one entry can cover every constant of a class (`Class.*`).
	 */

	$httpPhrases = [
		100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing',
		200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used',
		300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Switch Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
		400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Payload Too Large', 414 => 'URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable', 417 => 'Expectation Failed', 418 => "I'm a teapot", 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required'
	];

	$settingsKeys = [
		'api.cacheControl' => 'Cache-Control header the API sends.',
		'api.contentType'  => 'Content-Type header the API sends.',
		'classesExt'       => 'Extension of the class files loaded at boot.',
		'classesPath'      => 'Folder under includePath holding class files.',
		'cors.headers'     => 'Access-Control-Allow-Headers value for API preflight.',
		'cors.methods'     => 'Access-Control-Allow-Methods value for API preflight.',
		'cors.origins'     => 'Origins the API echoes in Access-Control-Allow-Origin.',
		'dbDsns'           => 'Group of named database DSNs.',
		'dbDsns.default'   => 'DSN of the default database connection.',
		'dbPasses'         => 'Group of named database passwords.',
		'dbPasses.default' => 'Password of the default database connection.',
		'dbUsers'          => 'Group of named database users.',
		'dbUsers.default'  => 'User of the default database connection.',
		'enableLogging'    => 'Whether the generated API front controller attaches a file appender.',
		'includePath'      => 'Folder holding the classes, repositories, and utilities folders.',
		'migrateCfg'       => 'Folder of configuration migration files.',
		'migrateDb'        => 'Folder of database migration files.',
		'reposExt'         => 'Extension of the repository files loaded at boot.',
		'reposPath'        => 'Folder under includePath holding repository files.',
		'utilitiesExt'     => 'Extension of the utility files loaded at boot.',
		'utilitiesPath'    => 'Folder under includePath holding utility files.'
	];

	$pdoDrivers = [
		'PDO_UNKNOWN' => 'No recognised DSN prefix.', 'PDO_4D' => '4D (DSN prefix 4D).', 'PDO_CUBRID' => 'CUBRID (DSN prefix cubrid).', 'PDO_FIREBIRD' => 'Firebird (DSN prefix firebird).', 'PDO_FREETDS' => 'FreeTDS (DSN prefix dblib).', 'PDO_IBM' => 'IBM DB2 (DSN prefix ibm).', 'PDO_INFORMIX' => 'Informix (DSN prefix informix).', 'PDO_MSSQL' => 'Microsoft SQL Server via mssql (DSN prefix mssql).', 'PDO_MYSQL' => 'MySQL and MariaDB (DSN prefix mysql).', 'PDO_ODBC' => 'ODBC (DSN prefix odbc).', 'PDO_ORACLE' => 'Oracle (DSN prefix oci).', 'PDO_PGSQL' => 'PostgreSQL (DSN prefix pgsql).', 'PDO_SQLITE' => 'SQLite (DSN prefix sqlite).', 'PDO_SQLSRV' => 'Microsoft SQL Server via sqlsrv (DSN prefix sqlsrv).', 'PDO_SYBASE' => 'Sybase (DSN prefix sybase).'
	];

	return [
		'stoic/web/resources#HttpStatusCodes.*'           => fn (string $name, string $value) => "HTTP {$value} " . ($httpPhrases[intval($value)] ?? '') . '.',
		'stoic/web/resources#ServerIndices.*'             => fn (string $name, string $value) => "Index of \$_SERVER[{$value}].",
		'stoic/web/resources#SettingsStrings.*'           => fn (string $name, string $value) => $settingsKeys[trim($value, "'")] ?? "Settings key {$value}.",
		'stoic/web/resources#StoicStrings.*'              => fn (string $name, string $value) => 'Location of the settings file, relative to the core path.',
		'stoic/web/resources#AuthorizationDispatchStrings.*' => fn (string $name, string $value) => match ($name) {
			'INDEX_CONSUMABLE' => 'Optional initialize() key: whether the dispatch is consumable.',
			'INDEX_INPUT'      => 'Required initialize() key: the request input ParameterHelper.',
			'INDEX_ROLES'      => 'Required initialize() key: the roles value from the endpoint registration.'
		},
		'stoic/web/resources#RequestType.*'               => fn (string $name, string $value) => ($name === 'ERROR') ? 'Placeholder for a request whose method could not be parsed.' : "HTTP {$name} request.",
		'stoic/pdo#PdoDrivers.*'                          => fn (string $name, string $value) => $pdoDrivers[$name] ?? '',
		'stoic/pdo#BaseDbTypes.*'                         => fn (string $name, string $value) => match ($name) {
			'INTEGER'  => 'Integer column, bound as PDO::PARAM_INT.',
			'STRING'   => 'String column, bound as PDO::PARAM_STR.',
			'BOOLEAN'  => 'Boolean column, bound as PDO::PARAM_BOOL and written as 1 or 0.',
			'NILL'     => 'Always-null column, bound as PDO::PARAM_NULL.',
			'DATETIME' => 'Date-time column, written as Y-m-d H:i:s and read as DateTimeImmutable.'
		},
		'stoic/pdo#BaseDbQueryTypes.*'                    => fn (string $name, string $value) => ($name === 'INVALID') ? 'No query type set.' : "Generate the {$name} statement.",
		'stoic/pdo#BaseDbColumnFlags.*'                   => fn (string $name, string $value) => match ($name) {
			'IS_KEY'         => 'Column is part of the key used in WHERE clauses.',
			'SHOULD_INSERT'  => 'Column is included in generated INSERT statements.',
			'SHOULD_UPDATE'  => 'Column is included in generated UPDATE statements.',
			'ALLOWS_NULLS'   => 'A null property binds as PDO::PARAM_NULL.',
			'AUTO_INCREMENT' => 'Property receives lastInsertId() after create().'
		},
		'stoic/utilities#ReturnHelper.*'                  => fn (string $name, string $value) => ($name === 'STATUS_BAD') ? 'Status value of a failed result; the default.' : 'Status value of a successful result.',
		'stoic/utilities#SanitationHelper.*'              => fn (string $name, string $value) => 'Key of the default ' . strtolower($name) . ' sanitizer.',
		'stoic/utilities#StringHelper.*'                  => fn (string $name, string $value) => ($name === 'CMP_STRPOS') ? 'Name of the case-sensitive search function.' : 'Name of the case-insensitive search function.',
		'stoic/utilities#ConsoleHelper.*'                 => fn (string $name, string $value) => match ($name) {
			'ARGINFO_ARGC'   => 'Internal key: number of arguments.',
			'ARGINFO_ARGV'   => 'Internal key: arguments as received.',
			'ARGINFO_ARGA'   => 'Internal key: parsed arguments with lowercased keys.',
			'ARGINFO_ARGACS' => 'Internal key: parsed arguments with keys as given.'
		},
		'stoic/utilities#FileHelperGlobs.*'               => fn (string $name, string $value) => match ($name) {
			'GLOB_ALL'     => 'List files and folders.',
			'GLOB_FOLDERS' => 'List folders only.',
			'GLOB_FILES'   => 'List files only.'
		},
		'stoic/utilities#LogFileOutputTypes.*'            => fn (string $name, string $value) => ($name === 'PLAIN') ? 'One plain-text line per message.' : 'One JSON object per message.'
	];
