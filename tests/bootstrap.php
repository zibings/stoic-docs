<?php

	$zsfDefaultConstants = [
		'STOIC_CORE_PATH'             => './',
		'STOIC_API_AUTH_COOKIE'       => true,
		'STOIC_DISABLE_SESSION'       => false,
		'STOIC_DISABLE_DB_EXCEPTIONS' => false,
		'STOIC_ENABLE_DEBUG'          => false
	];

	foreach ($zsfDefaultConstants as $constant => $default) {
		if (!defined($constant)) {
			define($constant, $default);
		}
	}

	$corePath       = STOIC_CORE_PATH;
	$corePathSuffix = $corePath[strlen($corePath) - 1];

	if ($corePathSuffix != '/') {
		$corePath .= '/';
	}

	require(STOIC_CORE_PATH . 'vendor/autoload.php');
	require(STOIC_CORE_PATH . 'tests/ZsfTestCase.php');
	require(STOIC_CORE_PATH . 'tests/DocTestHelper.php');

	use Stoic\Utilities\LogFileAppender;
	use Stoic\Web\Resources\PageVariables;
	use Stoic\Web\Stoic;

	global $Db, $Log, $Settings, $Stoic;

	/**
	 * @var \Stoic\Pdo\PdoHelper $Db
	 * @var \Stoic\Log\Logger $Log
	 * @var \AndyM84\Config\ConfigContainer $Settings
	 * @var Stoic $Stoic
	 */

	$Stoic    = Stoic::getInstance(STOIC_CORE_PATH, new PageVariables([], [], [], [], [], [], ['REQUEST_METHOD' => 'GET'], []));
	$Log      = $Stoic->getLog();
	$Db       = $Stoic->getDb();
	$Settings = $Stoic->getConfig();

	// The doc tests reset the Doc* tables of whichever database the site points at. `Exec.ps1 test` switches the DSN to
	// the test database first; refuse to run against anything else so a direct phpunit call cannot wipe dev data.
	$testDsn = (string) $Settings->get('dbDsns.default', '');

	if (getenv('ZSF_ALLOW_ANY_DB') === false && !preg_match('/dbname=[A-Za-z0-9_]+_test(;|$)/', $testDsn)) {
		fwrite(STDERR, "Refusing to run tests: dbDsns.default is \"{$testDsn}\", which is not a *_test database. Run `./Exec.ps1 test`, or set ZSF_ALLOW_ANY_DB=1 to override.\n");
		exit(1);
	}

	if (getenv('OUTPUT_LOGS') !== false) {
		$fh = $Stoic->getFileHelper();

		if ($fh->folderExists('~/tests/logs')) {
			$fh->makeFolder('~/tests/logs');
		}

		$Log->addAppender(new LogFileAppender($fh, '~/tests/logs/test-' . date('Y-m-d') . '.log'));
	}

