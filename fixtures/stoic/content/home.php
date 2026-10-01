<?php

	return [
		page('home', 'index', 'Stoic:PHP',
			'A small PHP framework that keeps your code clear: plain classes, explicit wiring, and helpers you can read in an afternoon.',
			<<<'MD'
Stoic:PHP is four Composer packages that build on each other. `stoic/stoic` gives you return values that carry messages, class-based enums, a PSR-3 logger, and the chain-of-nodes event model. `stoic/io` adds console, file, string, and typed-parameter helpers. `stoic/pdo` wraps PDO with stored queries and a small model base class. `stoic/web` boots a site or a JSON API from one `siteSettings.json` and a folder of classes. Nothing is generated at runtime and nothing is injected behind your back: you call what you use.

## Install

<<sample:install>>

Requiring `stoic/web` pulls in the other three packages. Then scaffold the folders, a first page, and the settings file:

<<sample:scaffold>>

## Your first page

Every page defines where the project root is, requires `inc/core.php`, and asks for its page helper:

<<sample:hello>>

The course on the Learn tab builds a small JSON API from that starting point. The Do tab has recipes for what comes next: models, migrations, request input, logging, and command-line scripts.
MD,
			[],
			[
				sample('install', "composer require stoic/web", 'terminal', 'composer'),
				sample('scaffold', "vendor/bin/stoic-create --site\nvendor/bin/stoic-configure", 'terminal'),
				sample('hello', <<<'PHP'
<?php

	const STOIC_CORE_PATH = '../';
	require(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Web\PageHelper;

	$page = PageHelper::getPage('hello.php');
	$page->setTitle('Hello');

	echo("<h1>{$page->getTitle()}</h1>");
PHP, 'web/hello.php')
			]
		)
	];
