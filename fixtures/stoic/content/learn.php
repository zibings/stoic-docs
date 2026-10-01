<?php

	/**
	 * The Learn course: a five-lesson build of a small notes API. Each lesson page's titled samples are the workspace
	 * files in the state they have after the lesson's steps; nothing runs in the browser, so the expected output lines
	 * are what the real commands print.
	 */

	$coreFile = <<<'PHP'
<?php

	$stoicDefaultConstants = [
		'STOIC_CORE_PATH'             => './',
		'STOIC_API_AUTH_COOKIE'       => true,
		'STOIC_DISABLE_SESSION'       => false,
		'STOIC_DISABLE_DB_EXCEPTIONS' => false,
		'STOIC_ENABLE_DEBUG'          => false
	];

	foreach ($stoicDefaultConstants as $name => $value) {
		if (!defined($name)) {
			define($name, $value);
		}
	}

	$corePath = STOIC_CORE_PATH;

	if (!str_ends_with($corePath, '/')) {
		$corePath .= '/';
	}

	if (STOIC_ENABLE_DEBUG) {
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
	}

	require($corePath . 'vendor/autoload.php');

	use AndyM84\Config\ConfigContainer;

	use Stoic\Log\Logger;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Utilities\ParameterHelper;
	use Stoic\Web\Resources\PageVariables;
	use Stoic\Web\Stoic;

	global $Db, $Log, $Settings, $Stoic;

	/**
	 * @var PdoHelper $Db
	 * @var Logger $Log
	 * @var ConfigContainer $Settings
	 * @var Stoic $Stoic
	 */

	if (PHP_SAPI == 'cli') {
		$Stoic = Stoic::getInstance(STOIC_CORE_PATH, new PageVariables([], [], [], [], [], [], ['REQUEST_METHOD' => 'GET'], []));
	} else {
		$Stoic = Stoic::getInstance(STOIC_CORE_PATH);
	}

	$Log      = $Stoic->getLog();
	$Db       = $Stoic->getDb();
	$Session  = $Stoic->getSession();
	$Settings = $Stoic->getConfig();
PHP;

	$indexPage = <<<'PHP'
<?php

	const STOIC_CORE_PATH = '../';
	require(STOIC_CORE_PATH . 'inc/core.php');

	use League\Plates\Engine;
	use Stoic\Web\PageHelper;
	use Stoic\Web\Stoic;

	$stoic = Stoic::getInstance(STOIC_CORE_PATH);

	$page = PageHelper::getPage('index.php');
	$page->setTitle('index');

	$tpl = new Engine(null, 'tpl.php');
	$tpl->addFolder('page', STOIC_CORE_PATH . 'tpl/index');

	echo(
		$tpl->render(
			'page::index',
			[
				'page' => $page
			]
		)
	);
PHP;

	$htaccess = <<<'TXT'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
TXT;

	$apiIndexLesson2 = <<<'PHP'
<?php

	const STOIC_CORE_PATH = '../../';
	require(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Web\Api\Stoic;

	global $Db, $Log;

	$api = Stoic::getInstance(STOIC_CORE_PATH, null, $Log);

	foreach ($api->loadFilesByExtension('~/api', '.api.php') as $file) {
		$class = '\\Api\\' . basename($file, '.api.php');

		new $class($api, $Db, $Log);
	}

	$api->handle();
PHP;

	$apiIndexLesson5 = <<<'PHP'
<?php

	const STOIC_CORE_PATH = '../../';
	require(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Web\Api\Stoic;

	global $Db, $Log;

	$api = Stoic::getInstance(STOIC_CORE_PATH, null, $Log);

	$authorizer = new TokenAuthorizer(getenv('NOTES_API_TOKEN') ?: '');
	$api->linkAuthorizationNode($authorizer);

	foreach ($api->loadFilesByExtension('~/api', '.api.php') as $file) {
		$class = '\\Api\\' . basename($file, '.api.php');

		new $class($api, $Db, $Log);
	}

	$api->handle();
PHP;

	$notesLesson2 = <<<'PHP'
<?php

	namespace Api;

	use Stoic\Log\Logger;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Web\Api\BaseDbApi;
	use Stoic\Web\Api\Response;
	use Stoic\Web\Api\Stoic;
	use Stoic\Web\Request;
	use Stoic\Web\Resources\HttpStatusCodes;

	class Notes extends BaseDbApi {
		public function __construct(Stoic $api, PdoHelper $db, null|Logger $log = null) {
			parent::__construct($db, $log);

			$api->registerEndpoint('GET', '/^notes$/i', [$this, 'all']);

			return;
		}

		public function all(Request $request, array $matches) : Response {
			return new Response(HttpStatusCodes::OK, [
				['id' => 1, 'title' => 'Hello'],
				['id' => 2, 'title' => 'Stoic']
			]);
		}
	}
PHP;

	$noteModel = <<<'PHP'
<?php

	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	class Note extends StoicDbModel {
		public int $id = 0;
		public string $title = '';
		public string $body = '';
		public null|\DateTimeInterface $created = null;


		protected function __setupModel() : void {
			$this->setTableName('Note');
			$this->setColumn('id',      'ID',      BaseDbTypes::INTEGER,  BCF::IS_KEY | BCF::AUTO_INCREMENT);
			$this->setColumn('title',   'Title',   BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('body',    'Body',    BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('created', 'Created', BaseDbTypes::DATETIME, BCF::SHOULD_INSERT);

			return;
		}

		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || trim($this->title) === '') {
				$ret->addMessage("A new note needs a title and no identifier");

				return $ret;
			}

			$this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
			$ret->makeGood();

			return $ret;
		}

		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}
	}
PHP;

	$noteSql = <<<'SQL'
CREATE TABLE `Note` (
	`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`Title` VARCHAR(128) NOT NULL,
	`Body` TEXT NOT NULL,
	`Created` DATETIME NOT NULL,
	PRIMARY KEY (`ID`)
);
SQL;

	$notesLesson4 = <<<'PHP'
<?php

	namespace Api;

	use Note;
	use Stoic\Log\Logger;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Web\Api\BaseDbApi;
	use Stoic\Web\Api\Response;
	use Stoic\Web\Api\Stoic;
	use Stoic\Web\Request;
	use Stoic\Web\Resources\HttpStatusCodes;

	class Notes extends BaseDbApi {
		public function __construct(Stoic $api, PdoHelper $db, null|Logger $log = null) {
			parent::__construct($db, $log);

			$api->registerEndpoint('GET', '/^notes$/i', [$this, 'all']);
			$api->registerEndpoint('GET', '/^notes\/(\d+)$/i', [$this, 'one']);
			$api->registerEndpoint('POST', '/^notes$/i', [$this, 'create']);

			return;
		}

		public function all(Request $request, array $matches) : Response {
			$stmt = $this->db->query("SELECT `ID`, `Title` FROM `Note` ORDER BY `ID` DESC");

			return new Response(HttpStatusCodes::OK, $stmt->fetchAll(\PDO::FETCH_ASSOC));
		}

		public function one(Request $request, array $matches) : Response {
			$note     = new Note($this->db, $this->log);
			$note->id = intval($matches[1][0]);

			if ($note->read()->isBad()) {
				$ret = new Response();
				$ret->setAsError("No note with that identifier", HttpStatusCodes::NOT_FOUND);

				return $ret;
			}

			return new Response(HttpStatusCodes::OK, $note);
		}

		public function create(Request $request, array $matches) : Response {
			$input = $request->getInput();

			if (!$input->hasAll('title', 'body')) {
				$ret = new Response();
				$ret->setAsError("Both title and body are required", HttpStatusCodes::BAD_REQUEST);

				return $ret;
			}

			$note        = new Note($this->db, $this->log);
			$note->title = $input->getString('title');
			$note->body  = $input->getString('body');

			$result = $note->create();

			if ($result->isBad()) {
				$ret = new Response();
				$ret->setAsError($result->getMessages()[0] ?? "Could not create the note", HttpStatusCodes::BAD_REQUEST);

				return $ret;
			}

			return new Response(HttpStatusCodes::CREATED, $note);
		}
	}
PHP;

	$notesLesson5 = str_replace(
		"\$api->registerEndpoint('POST', '/^notes\$/i', [\$this, 'create']);",
		"\$api->registerEndpoint('POST', '/^notes\$/i', [\$this, 'create'], true);",
		$notesLesson4
	);

	$authorizer = <<<'PHP'
<?php

	use Stoic\Chain\DispatchBase;
	use Stoic\Chain\NodeBase;
	use Stoic\Web\Resources\ApiAuthorizationDispatch;

	class TokenAuthorizer extends NodeBase {
		public function __construct(protected string $token) {
			$this->setKey('TokenAuthorizer');
			$this->setVersion('1.0.0');

			return;
		}

		public function process(mixed $sender, DispatchBase &$dispatch) : void {
			if (!($dispatch instanceof ApiAuthorizationDispatch) || $this->token === '') {
				return;
			}

			$sent = $sender->getRequest()->getServer()->getString('HTTP_X_API_TOKEN', '');

			if (hash_equals($this->token, $sent)) {
				$dispatch->authorize();
			}

			$dispatch->consume();

			return;
		}
	}
PHP;

	$pages = [
		page('learn', 'scaffold-the-site', 'Scaffold the site',
			'Install stoic/web, generate the folders, and load the first page.',
			<<<'MD'
Stoic keeps a site in a handful of folders you can see at once. This lesson creates them with `stoic-create --site`, which writes a settings file, a boot file at `inc/core.php`, and a first page that renders a Plates template.

:::card[You'll leave able to]
- Install the framework with Composer.
- Recognise what `inc/core.php` sets up on every request.
- Serve a page from `web/`.
:::

The generated `web/index.php` is the shape of every Stoic page: name the project root, require the boot file, ask for the page helper, render. Point your web server's document root at `web/` and open the site.
MD,
			['stoic/web#Stoic.getInstance', 'stoic/web#PageHelper.getPage'],
			[
				sample('core', $coreFile, 'inc/core.php'),
				sample('index', $indexPage, 'web/index.php')
			], 8),

		page('learn', 'answer-a-request', 'Answer a request from the API',
			'Boot the API singleton, load a controller, and return JSON from a route.',
			<<<'MD'
A JSON API in Stoic is a second front controller. It boots {sym:stoic/web/api#Stoic}, which knows how to route by verb and pattern, loads every `*.api.php` file under `api/`, and hands the request to whichever route matches. Each controller registers its routes in its constructor and returns a {sym:stoic/web/api#Response} from each handler; the framework encodes the data and sends the status.

:::card[You'll leave able to]
- Rewrite `/api/*` to one front controller.
- Register a route with a verb, a pattern, and a callable.
- Return structured data with a status code.
:::

The route pattern is a regular expression matched against the path after `/api/`. Keep the anchors, or `notes` will also match `notes/7`.
MD,
			['stoic/web/api#Stoic', 'stoic/web/api#Stoic.registerEndpoint', 'stoic/web/api#Stoic.handle', 'stoic/web/api#Response', 'stoic/web/api#BaseDbApi', 'stoic/web#Stoic.loadFilesByExtension'],
			[
				sample('htaccess', $htaccess, 'web/api/.htaccess'),
				sample('apiindex', $apiIndexLesson2, 'web/api/index.php'),
				sample('notes', $notesLesson2, 'api/Notes.api.php')
			], 10),

		page('learn', 'store-notes-in-a-table', 'Store notes in a table',
			'Point the site at a database, migrate a table, and describe it as a model.',
			<<<'MD'
So far the notes are literals. This lesson gives them a table and a model class. The database is named in `siteSettings.json`, the table comes from a SQL file that `stoic-migrate` runs once, and the model is a class in `inc/classes` that registers its columns in {sym:stoic/pdo#BaseDbModel.__setupModel}.

:::card[You'll leave able to]
- Set the default connection with `stoic-configure`.
- Write and run a database migration.
- Declare a model whose create and read statements are generated.
:::

The `__canCreate()` guard is where a model enforces its own rules and fills values the database should not have to. Returning a {sym:stoic/utilities#ReturnHelper} with a message means the caller and the log both learn why a save was refused.
MD,
			['stoic/pdo#BaseDbModel.__setupModel', 'stoic/pdo#BaseDbModel.setColumn', 'stoic/pdo#BaseDbModel.__canCreate', 'stoic/pdo#StoicDbModel', 'stoic/utilities#ReturnHelper', 'stoic/pdo#BaseDbColumnFlags'],
			[
				sample('sql', $noteSql, 'migrations/db/up/0001-note.sql'),
				sample('model', $noteModel, 'inc/classes/Note.cls.php')
			], 12),

		page('learn', 'create-and-read-notes', 'Create and read notes over HTTP',
			'Wire POST and GET routes to the model and answer with real rows.',
			<<<'MD'
With a model in place the controller becomes short: read the input, hand it to the model, and translate the {sym:stoic/utilities#ReturnHelper} into a status. {sym:stoic/web#Request.getInput} gives the same {sym:stoic/utilities#ParameterHelper} for a JSON body and for a multipart form, so the client can send either; a urlencoded body is the one shape it does not read.

:::card[You'll leave able to]
- Validate required fields before touching the database.
- Turn a bad result into a 4xx response with a message.
- Return a model directly; it serializes its registered columns.
:::

A model implements `JsonSerializable`, so `new Response(HttpStatusCodes::CREATED, $note)` sends the note's columns as an object with `created` formatted as a date-time string.
MD,
			['stoic/utilities#ReturnHelper', 'stoic/web#Request.getInput', 'stoic/utilities#ParameterHelper', 'stoic/utilities#ParameterHelper.hasAll', 'stoic/web/api#Response.setAsError', 'stoic/pdo#BaseDbModel.create', 'stoic/pdo#BaseDbModel.read', 'stoic/pdo#BaseDbModel.jsonSerialize'],
			[
				sample('notes', $notesLesson4, 'api/Notes.api.php')
			], 12),

		page('learn', 'protect-the-write-endpoint', 'Protect the write endpoint',
			'Require a token for POST by linking a node into the authorization chain.',
			<<<'MD'
Registering a route with a fourth argument of `true` tells {sym:stoic/web/api#Stoic} that the request must be authorized. Before the handler runs, the API sends an {sym:stoic/web/resources#ApiAuthorizationDispatch} through its authorization chain. Any node that recognises the caller calls {sym:stoic/web/resources#ApiAuthorizationDispatch.authorize}; if none does, the client gets a 403 and the handler never runs.

:::card[You'll leave able to]
- Write a {sym:stoic/chain#NodeBase} that inspects the request.
- Link it into the API's authorization chain.
- Mark a route as requiring authorization.
:::

The node receives the API singleton as `$sender`, so it can reach the request headers. It consumes the dispatch after deciding, which stops later nodes from overriding it. {sym:stoic/web/api#Stoic.linkAuthorizationNode} takes the node by reference, so it must be a variable rather than a `new` expression in the argument list.
MD,
			['stoic/web/api#Stoic', 'stoic/web/resources#ApiAuthorizationDispatch', 'stoic/web/resources#ApiAuthorizationDispatch.authorize', 'stoic/chain#NodeBase', 'stoic/chain#NodeBase.process', 'stoic/web/api#Stoic.linkAuthorizationNode', 'stoic/web/api#Stoic.registerEndpoint', 'stoic/chain#DispatchBase.consume'],
			[
				sample('authorizer', $authorizer, 'inc/classes/TokenAuthorizer.cls.php'),
				sample('apiindex', $apiIndexLesson5, 'web/api/index.php'),
				sample('notes', $notesLesson5, 'api/Notes.api.php')
			], 10)
	];

	$courses = [
		[
			'slug'      => 'notes-api',
			'title'     => 'Build a notes API',
			'summary'   => 'Five lessons from an empty folder to a token-protected JSON API backed by a table.',
			'sortOrder' => 1,
			'lessons'   => [
				[
					'page'    => 'learn/scaffold-the-site',
					'ordinal' => 1,
					'steps'   => [
						[
							'ordinal'        => 1,
							'prompt'         => 'In an empty folder, require `stoic/web` with Composer.',
							'hint'           => 'One package pulls in the other three.',
							'answer'         => "composer require stoic/web",
							'expectedOutput' => null
						],
						[
							'ordinal'        => 2,
							'prompt'         => 'Generate the site skeleton with the bundled script.',
							'hint'           => 'The script lives in `vendor/bin` and takes `--site`.',
							'answer'         => "vendor/bin/stoic-create --site",
							'expectedOutput' => "Page 'index.php' created.\nCore file created at '~/inc/core.php'.\nSite created."
						],
						[
							'ordinal'        => 3,
							'prompt'         => 'Point a web server at `web/` with `.htaccess` overrides allowed, and open the site.',
							'hint'           => 'The generated pages use `../` to find the project root, and the API lesson relies on a rewrite rule, so Apache with `mod_rewrite` is the assumed server.',
							'answer'         => "<VirtualHost *:80>\n\tServerName notes.local\n\tDocumentRoot /srv/notes/web\n\n\t<Directory /srv/notes/web>\n\t\tAllowOverride All\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>",
							'expectedOutput' => "GET / 200"
						]
					]
				],
				[
					'page'    => 'learn/answer-a-request',
					'ordinal' => 2,
					'steps'   => [
						[
							'ordinal'        => 1,
							'prompt'         => 'Create `web/api/index.php` that boots the API singleton, loads every `*.api.php` file under `~/api`, and calls `handle()`.',
							'hint'           => 'The API class is `Stoic\\Web\\Api\\Stoic`; pass the core path and the shared logger.',
							'answer'         => "\$api = Stoic::getInstance(STOIC_CORE_PATH, null, \$Log);\n\nforeach (\$api->loadFilesByExtension('~/api', '.api.php') as \$file) {\n\t\$class = '\\\\Api\\\\' . basename(\$file, '.api.php');\n\n\tnew \$class(\$api, \$Db, \$Log);\n}\n\n\$api->handle();",
							'expectedOutput' => null
						],
						[
							'ordinal'        => 2,
							'prompt'         => 'In `api/Notes.api.php`, register a `GET` route for `notes` that returns two literal notes.',
							'hint'           => 'Register in the constructor with `[$this, \'all\']` as the callable.',
							'answer'         => "\$api->registerEndpoint('GET', '/^notes\$/i', [\$this, 'all']);",
							'expectedOutput' => null
						],
						[
							'ordinal'        => 3,
							'prompt'         => 'Request `/api/notes` and check the JSON.',
							'hint'           => '`curl -i http://notes.local/api/notes`',
							'answer'         => "curl -i http://notes.local/api/notes",
							'expectedOutput' => "GET /api/notes 200\n[{\"id\":1,\"title\":\"Hello\"},{\"id\":2,\"title\":\"Stoic\"}]"
						]
					]
				],
				[
					'page'    => 'learn/store-notes-in-a-table',
					'ordinal' => 3,
					'steps'   => [
						[
							'ordinal'        => 1,
							'prompt'         => 'Point the default connection at a MySQL database with `stoic-configure`.',
							'hint'           => 'Non-interactive mode takes `-P` flags named after the settings keys.',
							'answer'         => "vendor/bin/stoic-configure -PdbDsns.default=\"mysql:host=127.0.0.1;dbname=notes\" -PdbUsers.default=notes -PdbPasses.default=secret",
							'expectedOutput' => "Attempting to set 'dbDsns.default' value to 'mysql:host=127.0.0.1;dbname=notes'.. DONE\nAttempting to set 'dbUsers.default' value to 'notes'.. DONE\nAttempting to set 'dbPasses.default' value to 'secret'.. DONE\nWriting settings to disk.. DONE"
						],
						[
							'ordinal'        => 2,
							'prompt'         => 'Write `migrations/db/up/0001-note.sql` with the `Note` table and run the migration.',
							'hint'           => 'The command records each file it runs, so it is safe to run again.',
							'answer'         => "vendor/bin/stoic-migrate",
							'expectedOutput' => "Executing migration file 'MIGRATIONS_TABLE'.. DONE\nExecuting migration file '0001-note.sql'.. DONE\nCompleted database migration"
						],
						[
							'ordinal'        => 3,
							'prompt'         => 'Create `inc/classes/Note.cls.php` and register the four columns in `__setupModel()`.',
							'hint'           => 'The identifier is the key and auto-increments; the timestamp is inserted but never updated.',
							'answer'         => "\$this->setTableName('Note');\n\$this->setColumn('id',      'ID',      BaseDbTypes::INTEGER,  BCF::IS_KEY | BCF::AUTO_INCREMENT);\n\$this->setColumn('title',   'Title',   BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);\n\$this->setColumn('body',    'Body',    BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);\n\$this->setColumn('created', 'Created', BaseDbTypes::DATETIME, BCF::SHOULD_INSERT);",
							'expectedOutput' => null
						]
					]
				],
				[
					'page'    => 'learn/create-and-read-notes',
					'ordinal' => 4,
					'steps'   => [
						[
							'ordinal'        => 1,
							'prompt'         => 'Add a `POST notes` route whose handler checks for `title` and `body`, then calls `create()` on a new `Note`.',
							'hint'           => '`$request->getInput()->hasAll(...)` first; a bad `ReturnHelper` becomes a 400 with its first message. Send the body as JSON.',
							'answer'         => "\$note        = new Note(\$this->db, \$this->log);\n\$note->title = \$input->getString('title');\n\$note->body  = \$input->getString('body');\n\n\$result = \$note->create();",
							'expectedOutput' => "POST /api/notes 201\n{\"id\":1,\"title\":\"Hello\",\"body\":\"First note\",\"created\":\"2026-09-30 14:02:11\"}"
						],
						[
							'ordinal'        => 2,
							'prompt'         => 'Add a `GET notes/(\\d+)` route that reads the note by identifier and returns 404 when `read()` is bad.',
							'hint'           => 'The capture is `$matches[1][0]`.',
							'answer'         => "\$note     = new Note(\$this->db, \$this->log);\n\$note->id = intval(\$matches[1][0]);\n\nif (\$note->read()->isBad()) {\n\t\$ret = new Response();\n\t\$ret->setAsError(\"No note with that identifier\", HttpStatusCodes::NOT_FOUND);\n\n\treturn \$ret;\n}",
							'expectedOutput' => "GET /api/notes/1 200\nGET /api/notes/99 404"
						],
						[
							'ordinal'        => 3,
							'prompt'         => 'Replace the literal list in `all()` with a query for every note, newest first.',
							'hint'           => '`$this->db` is the `PdoHelper` the front controller passed in.',
							'answer'         => "\$stmt = \$this->db->query(\"SELECT `ID`, `Title` FROM `Note` ORDER BY `ID` DESC\");\n\nreturn new Response(HttpStatusCodes::OK, \$stmt->fetchAll(\\PDO::FETCH_ASSOC));",
							'expectedOutput' => "GET /api/notes 200\n[{\"ID\":1,\"Title\":\"Hello\"}]"
						]
					]
				],
				[
					'page'    => 'learn/protect-the-write-endpoint',
					'ordinal' => 5,
					'steps'   => [
						[
							'ordinal'        => 1,
							'prompt'         => 'Create `inc/classes/TokenAuthorizer.cls.php`, a node that authorizes the dispatch when the `X-Api-Token` header matches its token.',
							'hint'           => 'Headers arrive in `$_SERVER` as `HTTP_X_API_TOKEN`; `$sender` is the API singleton.',
							'answer'         => "\$sent = \$sender->getRequest()->getServer()->getString('HTTP_X_API_TOKEN', '');\n\nif (hash_equals(\$this->token, \$sent)) {\n\t\$dispatch->authorize();\n}\n\n\$dispatch->consume();",
							'expectedOutput' => null
						],
						[
							'ordinal'        => 2,
							'prompt'         => 'Link the node in `web/api/index.php` and mark the `POST notes` route as requiring authorization.',
							'hint'           => 'Assign the node to a variable first; the link method takes it by reference.',
							'answer'         => "// web/api/index.php\n\$authorizer = new TokenAuthorizer(getenv('NOTES_API_TOKEN') ?: '');\n\$api->linkAuthorizationNode(\$authorizer);\n\n// api/Notes.api.php\n\$api->registerEndpoint('POST', '/^notes\$/i', [\$this, 'create'], true);",
							'expectedOutput' => "POST /api/notes 403\n\"Unauthorized access for auth-only endpoint\""
						],
						[
							'ordinal'        => 3,
							'prompt'         => 'Send the token and create a note.',
							'hint'           => 'Send JSON: a urlencoded `-d` body is not read as input.',
							'answer'         => "curl -H \"X-Api-Token: \$NOTES_API_TOKEN\" -H \"Content-Type: application/json\" -d '{\"title\":\"Hi\",\"body\":\"There\"}' http://notes.local/api/notes",
							'expectedOutput' => "POST /api/notes 201"
						]
					]
				]
			]
		]
	];

	return ['pages' => $pages, 'courses' => $courses];
