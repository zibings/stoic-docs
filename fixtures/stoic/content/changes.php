<?php

	/**
	 * What changed between the 1.3 family (core 1.3.1, io 1.3.1, pdo 1.3.4, web 1.3.16) and the 1.4 family (core
	 * 1.4.0, io 1.4.0, pdo 1.4.0, web 1.4.2). Sources: the git history between the tags; none of the packages keeps a
	 * changelog.
	 */

	return [
		[
			'version'    => 'v1.4',
			'kind'       => 'breaking',
			'title'      => 'CliScriptHelper takes its ConsoleHelper once, in the constructor',
			'why'        => 'Every method on the helper took the same ConsoleHelper as its first argument. Passing it once at construction removes the repetition from every script (stoic-php-io #27).',
			'rfcUrl'     => 'https://github.com/zibings/stoic-php-io/issues/27',
			'beforeCode' => "\$ch     = new ConsoleHelper(\$argv);\n\$script = new CliScriptHelper('Exporter', 'Writes notes to a file.');\n\n\$opts = \$script->startScript(\$ch)->getOptions(\$ch);\n\$script->showBasicHelp(\$ch, 'Nothing to do');",
			'afterCode'  => "\$ch     = new ConsoleHelper(\$argv);\n\$script = new CliScriptHelper('Exporter', 'Writes notes to a file.', \$ch);\n\n\$opts = \$script->startScript()->getOptions();\n\$script->showBasicHelp('Nothing to do');",
			'codemodCmd' => null,
			'sortOrder'  => 1,
			'symbols'    => ['stoic/utilities#CliScriptHelper.__construct', 'stoic/utilities#CliScriptHelper.startScript', 'stoic/utilities#CliScriptHelper.getOptions', 'stoic/utilities#CliScriptHelper.checkRequirements', 'stoic/utilities#CliScriptHelper.satisfiesRequirements', 'stoic/utilities#CliScriptHelper.showBasicHelp', 'stoic/utilities#CliScriptHelper.showOptionHelp']
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'behavior',
			'title'      => 'PaginateHelper::getPages() includes the last page',
			'why'        => 'The page window stopped one page short of the total, so a five-page set asked for five links and got four. The loop bound and the window start were corrected in web 1.4.1 and 1.4.2.',
			'rfcUrl'     => null,
			'beforeCode' => "\$pager = new PaginateHelper(3, 100, 20);\n\$pager->getPages(5);\n// [1, 2, 3, 4]",
			'afterCode'  => "\$pager = new PaginateHelper(3, 100, 20);\n\$pager->getPages(5);\n// [1, 2, 3, 4, 5]",
			'codemodCmd' => null,
			'sortOrder'  => 2,
			'symbols'    => ['stoic/web#PaginateHelper.getPages']
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'behavior',
			'title'      => 'BOOLEAN model columns bind as PDO::PARAM_BOOL',
			'why'        => 'Part of the PHP 8.4 pass over stoic/pdo. BaseDbTypes::BOOLEAN reported PDO::PARAM_INT as its parameter type; it now reports the constant that matches the column type. Generated statements still pass 1 or 0 as the value, so MySQL and SQLite see no difference; drivers that distinguish booleans receive true or false.',
			'rfcUrl'     => 'https://github.com/zibings/stoic-php-pdo/issues/16',
			'beforeCode' => "(new BaseDbTypes(BaseDbTypes::BOOLEAN))->getDbType();\n// PDO::PARAM_INT",
			'afterCode'  => "(new BaseDbTypes(BaseDbTypes::BOOLEAN))->getDbType();\n// PDO::PARAM_BOOL",
			'codemodCmd' => null,
			'sortOrder'  => 3,
			'symbols'    => ['stoic/pdo#BaseDbTypes.getDbType', 'stoic/pdo#BaseDbTypes.BOOLEAN']
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'behavior',
			'title'      => 'Nullable parameters are declared explicitly',
			'why'        => 'PHP 8.4 deprecates implicitly nullable parameters such as `array $x = null`. Every such parameter, and every `?T`, is now written `null|T`. Calls that passed null keep working; the change removes the deprecation notices under 8.4.',
			'rfcUrl'     => null,
			'beforeCode' => "public function __construct(string \$relativePath, array \$preIncludes = null)",
			'afterCode'  => "public function __construct(string \$relativePath, null|array \$preIncludes = null)",
			'codemodCmd' => null,
			'sortOrder'  => 4,
			'symbols'    => ['stoic/utilities#FileHelper.__construct', 'stoic/utilities#ConsoleHelper.__construct', 'stoic/pdo#PdoHelper.__construct', 'stoic/pdo#PdoStoredQuery.__construct', 'stoic/pdo#PdoQuery.__construct', 'stoic/web#PageHelper.getPage', 'stoic/web#PageHelper.getAssetPath', 'stoic/web#Request.__construct']
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'added',
			'title'      => 'ParameterHelper::hasAny() checks for any of several keys',
			'why'        => 'hasAll() covered required fields; hasAny() covers the "at least one of these" case without a loop (stoic-php-io #26).',
			'rfcUrl'     => 'https://github.com/zibings/stoic-php-io/issues/26',
			'beforeCode' => null,
			'afterCode'  => "if (\$input->hasAny('email', 'phone')) {\n\t// contact the user\n}",
			'codemodCmd' => null,
			'sortOrder'  => 5,
			'symbols'    => ['stoic/utilities#ParameterHelper.hasAny', 'stoic/utilities#ParameterHelper.hasAll']
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'added',
			'title'      => 'stoic-create scaffolds sites, pages, and API controllers from shipped templates',
			'why'        => 'The script now takes `--site`, `--page <name>`, and `--api <name>` and copies from `Templates/` in the package, so a new project starts with a boot file, an index page, a Plates template, and optionally an API front controller (stoic-php-web #17, #20).',
			'rfcUrl'     => 'https://github.com/zibings/stoic-php-web/issues/20',
			'beforeCode' => null,
			'afterCode'  => "vendor/bin/stoic-create --site\nvendor/bin/stoic-create --page admin/users\nvendor/bin/stoic-create --api Notes --namespace Api --folder ~/api",
			'codemodCmd' => null,
			'sortOrder'  => 6,
			'symbols'    => []
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'added',
			'title'      => 'stoic-migrate targets any configured database with -dsn, -user, and -pass',
			'why'        => 'Sites with several `dbDsns.*` connections could only migrate the default one. The three options name the settings keys to read instead.',
			'rfcUrl'     => null,
			'beforeCode' => null,
			'afterCode'  => "vendor/bin/stoic-migrate -up -dsn=dbDsns.reports -user=dbUsers.reports -pass=dbPasses.reports",
			'codemodCmd' => null,
			'sortOrder'  => 7,
			'symbols'    => ['stoic/web/resources#SettingsStrings.DB_DSN_DEFAULT', 'stoic/web/resources#SettingsStrings.DB_USER_DEFAULT', 'stoic/web/resources#SettingsStrings.DB_PASS_DEFAULT']
		],
		[
			'version'    => 'v1.4',
			'kind'       => 'added',
			'title'      => 'enableLogging setting',
			'why'        => 'stoic-create writes `enableLogging` into new settings files and the generated API front controller attaches a LogFileAppender writing to `~/logs/api.log` when it is true. The library itself does not read the setting.',
			'rfcUrl'     => null,
			'beforeCode' => null,
			'afterCode'  => "if (\$Settings->get(SettingsStrings::ENABLE_LOGGING, false) !== false) {\n\t\$Log->addAppender(new LogFileAppender(\$Api->getFileHelper(), '~/logs/api.log'));\n}",
			'codemodCmd' => null,
			'sortOrder'  => 8,
			'symbols'    => ['stoic/web/resources#SettingsStrings.ENABLE_LOGGING', 'stoic/utilities#LogFileAppender']
		]
	];
