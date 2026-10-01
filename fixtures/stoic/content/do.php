<?php

	return [
		page('do', 'scaffold-a-site', 'Scaffold a new site',
			'Get the folders, the settings file, and a first page in three commands.',
			<<<'MD'
Start in an empty project folder. `stoic/web` requires the other three packages.

<<sample:install>>

`stoic-create --site` writes `siteSettings.json` with defaults, the first configuration migration, and the folders below. `stoic-configure` then walks you through every setting; answer with Enter to keep a default. To set a few values without the prompts, pass `-P` flags:

<<sample:configure>>

## What you get

```
inc/classes/        *.cls.php, loaded first on every request
inc/repositories/   *.rpo.php
inc/utilities/      *.utl.php, loaded last, after the database and session
inc/core.php        boots Stoic; every page requires it
migrations/cfg/     settings migrations (0-1.cfg is the one stoic-create wrote)
migrations/db/up/   SQL files stoic-migrate runs in name order
tpl/index/          Plates templates for the index page
web/                the document root
web/index.php       the first page
siteSettings.json
```

Point the web server's document root at `web/`. Pages find the project root through `STOIC_CORE_PATH`, which the generated `web/index.php` sets to `../`.

## What goes wrong

- `stoic-configure -P` only changes settings that already exist. A new setting needs a line in a configuration migration first; see [Run configuration and database migrations](run-migrations).
- The default database is `sqlite::memory:`, which vanishes with the request. Set `dbDsns.default` before running `stoic-migrate`.
- Quote DSNs in the shell. A bare `;` ends the command.
MD,
			['stoic/web#Stoic', 'stoic/web#PageHelper'],
			[
				sample('install', "composer require stoic/web\nvendor/bin/stoic-create --site", 'terminal', 'composer'),
				sample('configure', "vendor/bin/stoic-configure -PdbDsns.default=\"mysql:host=127.0.0.1;dbname=app\" -PdbUsers.default=app -PdbPasses.default=secret", 'terminal')
			], 5),

		page('do', 'add-a-json-api-endpoint', 'Add a JSON API endpoint',
			'Route a URL to a method that returns a Response, and let the framework encode it.',
			<<<'MD'
An API lives behind one front controller that boots {sym:stoic/web/api#Stoic}, loads every controller file, and calls {sym:stoic/web/api#Stoic.handle}. Each controller registers its own routes in its constructor.

Rewrite every request under `web/api/` to the front controller, passing the path as `url`:

<<sample:htaccess>>

The front controller:

<<sample:index>>

A controller extends {sym:stoic/web/api#BaseDbApi}, which gives it the database and logger, and registers endpoints with {sym:stoic/web/api#Stoic.registerEndpoint}:

<<sample:controller>>

`GET /api/notes/7` now returns `{"id":7}` with a 200 status. The verb, the pattern, and the callable are all the router needs.

## What goes wrong

- The callback must be a real callable. Pass `[$this, 'method']`, not the method name as a string.
- `$matches` comes from `preg_match()` with `PREG_OFFSET_CAPTURE`, so a capture is `$matches[1][0]`, not `$matches[1]`.
- Anything the callback `echo`s is discarded. Only the returned {sym:stoic/web/api#Response} reaches the client, JSON-encoded, with its status code.
- `stoic-create --api Notes` writes a controller template, but the file it writes ends in `.php` rather than `.api.php` and it registers the endpoint with a bare string. Rename the file and change the registration to `[$this, 'get']` before the front controller will load it.
MD,
			['stoic/web/api#Stoic', 'stoic/web/api#Stoic.handle', 'stoic/web/api#BaseDbApi', 'stoic/web/api#Stoic.registerEndpoint', 'stoic/web/api#Response', 'stoic/web#Request', 'stoic/web/resources#HttpStatusCodes', 'stoic/web#Stoic.loadFilesByExtension'],
			[
				sample('htaccess', "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^(.*)$ index.php?url=$1 [QSA,L]", 'web/api/.htaccess'),
				sample('index', <<<'PHP'
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
PHP, 'web/api/index.php'),
				sample('controller', <<<'PHP'
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
			$api->registerEndpoint('GET', '/^notes\/(\d+)$/i', [$this, 'one']);

			return;
		}

		public function all(Request $request, array $matches) : Response {
			return new Response(HttpStatusCodes::OK, ['notes' => []]);
		}

		public function one(Request $request, array $matches) : Response {
			return new Response(HttpStatusCodes::OK, ['id' => intval($matches[1][0])]);
		}
	}
PHP, 'api/Notes.api.php')
			], 10),

		page('do', 'map-a-table-to-a-model', 'Map a table to a class with StoicDbModel',
			'Declare the columns once and get create, read, update, and delete for a row.',
			<<<'MD'
Put the class in `inc/classes` with the `.cls.php` extension so the boot loads it. Extend {sym:stoic/pdo#StoicDbModel} rather than {sym:stoic/pdo#BaseDbModel} when you want `$this->db` to be a {sym:stoic/pdo#PdoHelper}.

<<sample:model>>

The table it expects:

<<sample:table>>

Using it from a page or an endpoint, where `$Db` and `$Log` come from `inc/core.php`:

<<sample:usage>>

## How the pieces fit

- {sym:stoic/pdo#BaseDbModel.setColumn} takes either the flag composite shown above or five booleans in the order key, insert, update, nulls, auto-increment. Mixing the two styles in one model is fine.
- Public typed properties hold plain values. Protected properties go through the model's magic getters and setters, which wrap strings in a {sym:stoic/utilities#StringHelper}; pick one style per model.
- {sym:stoic/pdo#BaseDbModel.__canCreate} runs before the insert. Returning a bad {sym:stoic/utilities#ReturnHelper} with messages puts those messages on the caller's result and in the log.

## What goes wrong

- Registering the same property twice throws `InvalidArgumentException`. Registering no key column makes `read()`, `update()`, and `delete()` fail with a message instead of touching the table.
- A `DATETIME` column that is not `ALLOWS_NULLS` must hold a `DateTimeInterface` before `create()`; a null binds as an empty string.
- `read()` uses the key properties as they are. Set `$note->id` first.
MD,
			['stoic/pdo#StoicDbModel', 'stoic/pdo#BaseDbModel', 'stoic/pdo#PdoHelper', 'stoic/pdo#BaseDbModel.setColumn', 'stoic/pdo#BaseDbModel.setTableName', 'stoic/pdo#BaseDbModel.__setupModel', 'stoic/pdo#BaseDbModel.__canCreate', 'stoic/pdo#BaseDbColumnFlags', 'stoic/pdo#BaseDbTypes', 'stoic/utilities#StringHelper', 'stoic/utilities#ReturnHelper', 'stoic/pdo#BaseDbModel.create', 'stoic/pdo#BaseDbModel.read'],
			[
				sample('model', <<<'PHP'
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

			if ($this->id > 0) {
				$ret->addMessage("Note already has an identifier");

				return $ret;
			}

			if (trim($this->title) === '') {
				$ret->addMessage("Notes need a title");

				return $ret;
			}

			$this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
			$ret->makeGood();

			return $ret;
		}
	}
PHP, 'inc/classes/Note.cls.php'),
				sample('table', <<<'SQL'
CREATE TABLE `Note` (
	`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`Title` VARCHAR(128) NOT NULL,
	`Body` TEXT NOT NULL,
	`Created` DATETIME NOT NULL,
	PRIMARY KEY (`ID`)
);
SQL, 'migrations/db/up/0001-note.sql'),
				sample('usage', <<<'PHP'
$note        = new Note($Db, $Log);
$note->title = 'First';
$note->body  = 'Hello from Stoic';

$result = $note->create();

if ($result->isBad()) {
	$Log->error("Could not save note: {why}", ['why' => implode('; ', $result->getMessages())]);
}

$again     = new Note($Db, $Log);
$again->id = $note->id;

if ($again->read()->isGood()) {
	echo($again->title);
}
PHP)
			], 10),

		page('do', 'run-migrations', 'Run configuration and database migrations',
			'One command applies settings migrations and then the SQL files that have not run yet.',
			<<<'MD'
`stoic-migrate` does two things in order: it brings `siteSettings.json` up to date from `migrations/cfg`, then runs every `.sql` file in `migrations/db/up` that the `Migration` table has not recorded.

<<sample:migrate>>

## Configuration migrations

A file named `1-2.cfg` moves `configVersion` from 1 to 2. Each line is a field, its type in brackets, an operator, and a value:

```
dbDsns.reports[str] + pgsql:host=warehouse;dbname=reports
siteName[str] = Notes
oldSetting[str] -
dbDsn[str] > dbDsns.default
```

`+` adds a field if it is missing (array types append), `=` changes a value, `-` removes a field, and `>` renames one. Types are `str`, `int`, `flt`, `bln`, and their `[]` array forms. `stoic-configure` applies these too, before it prompts.

## Database migrations

Files run in name order, so prefix them with a sequence number. Each file runs as one query call and its name is inserted into `Migration` afterwards, so re-running the command skips it. `-down` runs `migrations/db/down` (or `drop`) in reverse and drops the `Migration` table.

To migrate a database other than the default, name the settings keys that hold its DSN and credentials:

<<sample:other>>

## What goes wrong

- MySQL, PostgreSQL, SQLite, and SQL Server (both `sqlsrv` and `dblib` DSNs) are supported. Anything else stops with a message.
- A file that fails is reported and skipped, and the command continues with the next file. Read the whole output, not just the last line.
- The `Migration` table stores file names, so renaming a migration makes it run again.
MD,
			['stoic/pdo#PdoHelper', 'stoic/pdo#PdoDrivers', 'stoic/web/resources#SettingsStrings'],
			[
				sample('migrate', "vendor/bin/stoic-migrate\nvendor/bin/stoic-migrate -down", 'terminal'),
				sample('other', "vendor/bin/stoic-migrate -dsn=dbDsns.reports -user=dbUsers.reports -pass=dbPasses.reports", 'terminal')
			], 5),

		page('do', 'read-request-input', 'Read request input without trusting it',
			'Get query, JSON, and form input through one ParameterHelper with typed defaults.',
			<<<'MD'
{sym:stoic/web#Request.getInput} returns a {sym:stoic/utilities#ParameterHelper}. For a `GET` it wraps the query string; for a JSON body it decodes the object; for a multipart form, with or without files, it wraps `$_POST`. A urlencoded form body is neither, and yields an empty helper; read those fields with {sym:stoic/web#Request.getPost}, or have clients send JSON.

<<sample:endpoint>>

## Checking and reading

- {sym:stoic/utilities#ParameterHelper.hasAll} and {sym:stoic/utilities#ParameterHelper.hasAny} check keys before you read them.
- The typed getters take a default that is returned when the key is absent. They coerce present values through the {sym:stoic/utilities#SanitationHelper}; they do not validate them.
- {sym:stoic/utilities#ParameterHelper.get} with no key returns the whole array. With a key and no sanitizer name it returns the raw value.

## What goes wrong

- {sym:stoic/utilities#ParameterHelper.getInt} on a non-numeric string returns the string's **length**, and on an array returns its count. Check the shape yourself when the value matters.
- {sym:stoic/utilities#ParameterHelper.getBool} treats only the string `true` (any case) as true. `"1"` and `"yes"` are false; integers go through `boolval()`.
- A `POST` with an empty body and no form fields is not a valid request: {sym:stoic/web#Request.isValid} is false and {sym:stoic/web#Request.getRawInput} throws.
MD,
			['stoic/web#Request.getInput', 'stoic/utilities#ParameterHelper', 'stoic/utilities#ParameterHelper.hasAll', 'stoic/utilities#ParameterHelper.hasAny', 'stoic/utilities#SanitationHelper', 'stoic/utilities#ParameterHelper.get', 'stoic/utilities#ParameterHelper.getInt', 'stoic/utilities#ParameterHelper.getBool', 'stoic/web#Request.isValid', 'stoic/web#Request.getRawInput', 'stoic/web#Request.getPost', 'stoic/web/api#Response.setAsError', 'stoic/utilities#ParameterHelper.getString'],
			[
				sample('endpoint', <<<'PHP'
public function create(Request $request, array $matches) : Response {
	$input = $request->getInput();

	if (!$input->hasAll('title', 'body')) {
		$ret = new Response();
		$ret->setAsError("Both title and body are required", HttpStatusCodes::BAD_REQUEST);

		return $ret;
	}

	$title  = trim($input->getString('title'));
	$body   = $input->getString('body', '');
	$pinned = $input->getBool('pinned', false);

	return new Response(HttpStatusCodes::CREATED, ['title' => $title, 'pinned' => $pinned]);
}
PHP)
			], 5),

		page('do', 'write-a-cli-script', 'Write a command-line script',
			'Declare options once and get parsing, required-argument checks, and --help for free.',
			<<<'MD'
A script requires `inc/core.php` like a page does; in CLI mode the generated file boots Stoic with a fake `GET` request so `$Db` and `$Log` still exist. {sym:stoic/utilities#ConsoleHelper} parses `$argv`, and {sym:stoic/utilities#CliScriptHelper} turns declared options into help text and checks.

<<sample:script>>

Run it with either spelling of an option, and with `-h` to see the generated help:

<<sample:run>>

## How arguments are parsed

`--out notes.json` and `--out=notes.json` both set `out`. An option followed by nothing, or by another option, is a toggle and reads as `true`. {sym:stoic/utilities#CliScriptHelper.getOptions} returns every declared option under both its short and long name, with the default filled in for the ones not given.

{sym:stoic/utilities#CliScriptHelper.startScript} prints the script name, handles `-h`, and exits with a message if a required option is missing. Pass `false` to skip the requirement check when a script has modes that need different options.

## What goes wrong

- `h` and `help` are reserved; declaring them throws.
- A value that begins with `-` is read as the next option. Quote or `=`-join values such as negative numbers.
- Matching is case-insensitive for declared options, so `--Out` works; comparing raw arguments yourself with {sym:stoic/utilities#ConsoleHelper.hasArg} is case-sensitive unless you say otherwise.
MD,
			['stoic/utilities#ConsoleHelper', 'stoic/utilities#CliScriptHelper', 'stoic/utilities#CliScriptHelper.getOptions', 'stoic/utilities#CliScriptHelper.startScript', 'stoic/utilities#CliScriptHelper.addOption', 'stoic/utilities#CliScriptHelper.addExample', 'stoic/utilities#ConsoleHelper.hasArg', 'stoic/utilities#ConsoleHelper.putLine'],
			[
				sample('script', <<<'PHP'
<?php

	const STOIC_CORE_PATH = './';
	require(STOIC_CORE_PATH . 'inc/core.php');

	use Stoic\Utilities\CliScriptHelper;
	use Stoic\Utilities\ConsoleHelper;

	global $Db;

	$ch     = new ConsoleHelper($argv);
	$script = new CliScriptHelper('Note exporter', 'Writes every note to a JSON file.', $ch);

	$script->addOption('out', 'o', 'out', 'Output file', 'Path of the JSON file to write. An existing file is overwritten.', true)
		->addOption('pretty', 'p', 'pretty', 'Pretty-print', 'Indent the JSON output.', false, false)
		->addExample('php scripts/export-notes.php --out notes.json --pretty');

	$opts  = $script->startScript()->getOptions();
	$flags = ($opts['pretty'] === true) ? JSON_PRETTY_PRINT : 0;
	$rows  = $Db->query("SELECT `ID`, `Title`, `Body` FROM `Note` ORDER BY `ID`")->fetchAll(\PDO::FETCH_ASSOC);

	file_put_contents($opts['out'], json_encode($rows, $flags));
	$ch->putLine("Wrote " . count($rows) . " note(s) to {$opts['out']}");
PHP, 'scripts/export-notes.php'),
				sample('run', "php scripts/export-notes.php --out notes.json --pretty\nphp scripts/export-notes.php -h", 'terminal')
			], 8),

		page('do', 'log-to-a-file', 'Log to a file',
			'Attach an appender to the shared logger; messages are written when the request ends.',
			<<<'MD'
`inc/core.php` gives every page a {sym:stoic/log#Logger} as `$Log`, and the boot registers {sym:stoic/log#Logger.output} to run at shutdown. Until then messages sit in memory, so add the appender anywhere before the page ends.

<<sample:appender>>

Placeholders in braces are filled from the context array. Scalars print as they are, exceptions print with their message and stack trace, dates print as RFC 3339, and arrays print through `print_r()`.

## Choosing what gets written

The logger keeps everything and filters when it outputs. To raise the threshold, build the logger yourself and hand it to the boot:

<<sample:threshold>>

{sym:stoic/utilities#LogConsoleAppender} does the same job for scripts, writing each message as a line on standard output.

## What goes wrong

- The path is resolved against the file helper's root, so `~/logs/app.log` means the project's `logs` folder. The appender touches the file on construction and throws `InvalidArgumentException` when it cannot.
- Long-running scripts do not reach shutdown for a while. Call `$Log->output()` yourself after each unit of work.
- The `enableLogging` setting is only a convention that the generated API front controller reads. Nothing in the library checks it.
MD,
			['stoic/log#Logger', 'stoic/log#Logger.output', 'stoic/log#Logger.addAppender', 'stoic/log#Logger.log', 'stoic/utilities#LogFileAppender', 'stoic/utilities#LogFileOutputTypes', 'stoic/utilities#LogConsoleAppender', 'stoic/web#Stoic.getInstance', 'stoic/web/resources#SettingsStrings.ENABLE_LOGGING'],
			[
				sample('appender', <<<'PHP'
use Stoic\Utilities\LogFileAppender;
use Stoic\Utilities\LogFileOutputTypes;

global $Log, $Stoic;

$Log->addAppender(new LogFileAppender($Stoic->getFileHelper(), '~/logs/app.log', LogFileOutputTypes::JSON));
$Log->info("Order {id} shipped to {country}", ['id' => 42, 'country' => 'NZ']);
PHP),
				sample('threshold', <<<'PHP'
use Psr\Log\LogLevel;
use Stoic\Log\Logger;
use Stoic\Web\Resources\PageVariables;
use Stoic\Web\Stoic;

$Stoic = Stoic::getInstance(STOIC_CORE_PATH, PageVariables::fromGlobals(), new Logger(LogLevel::WARNING));
PHP)
			], 5),

		page('do', 'handle-file-uploads', 'Accept file uploads',
			'Read $_FILES through the request, one UploadedFile per file, with the error decoded for you.',
			<<<'MD'
When a request carries uploads, {sym:stoic/web#Request.hasFileUploads} is true and {sym:stoic/web#Request.getInput} returns the ordinary form fields that came with them. The files themselves come from {sym:stoic/web#Request.getFiles}.

<<sample:upload>>

{sym:stoic/web#FileUploadHelper.getFile} always returns an array, so an input named `attachment[]` with three files and an input named `attachment` with one file are handled by the same loop.

## What goes wrong

- {sym:stoic/web/resources#UploadedFile.name} is whatever the browser sent. Never use it as a path on disk; generate your own file name and keep the original only as data.
- {sym:stoic/web#FileUploadHelper.count} counts input names, not files. Three files under one name count as one.
- A request with uploads never has a JSON body. Send metadata as form fields alongside the files.
MD,
			['stoic/web#Request.hasFileUploads', 'stoic/web#Request.getInput', 'stoic/web#Request.getFiles', 'stoic/web#FileUploadHelper', 'stoic/web#FileUploadHelper.getFile', 'stoic/web#FileUploadHelper.count', 'stoic/web/resources#UploadedFile', 'stoic/web/resources#UploadedFile.name', 'stoic/web/resources#UploadedFile.isValid', 'stoic/web/resources#UploadedFile.getError'],
			[
				sample('upload', <<<'PHP'
global $Log, $Stoic;

$request = $Stoic->getRequest();

if ($request->hasFileUploads()) {
	$title = $request->getInput()->getString('title', 'Untitled');

	foreach ($request->getFiles()->getFile('attachment') as $file) {
		if (!$file->isValid()) {
			$Log->warning("Upload of {name} failed: {reason}", ['name' => $file->name, 'reason' => $file->getError()]);

			continue;
		}

		$stored = bin2hex(random_bytes(16)) . '.bin';

		move_uploaded_file($file->tmpName, STOIC_CORE_PATH . "uploads/{$stored}");
	}
}
PHP)
			], 5),

		page('do', 'paginate-a-list', 'Paginate a list',
			'Turn a page number and a row count into an offset and a window of page links.',
			<<<'MD'
{sym:stoic/web#PaginateHelper} does the arithmetic once, in its constructor, and exposes the results as public properties.

<<sample:paginate>>

`currentPage` is clamped to the range 1 to `totalPages`, so a `page=0` or an out-of-range value never produces a negative offset. `nextPage` and `lastPage` are `0` when there is no such page, which makes "disable the link" a plain truthiness check. An empty set has one page.

## What goes wrong

- {sym:stoic/web#PaginateHelper.getPages} returns a window of page numbers centered on the current page. Ask for an odd count when you want the current page in the middle.
- `LIMIT` and `OFFSET` must bind as integers. With emulated prepares PDO quotes strings, and MySQL rejects `LIMIT '20'`.
MD,
			['stoic/web#PaginateHelper', 'stoic/web#PaginateHelper.getPages', 'stoic/web#PaginateHelper.entryOffset', 'stoic/web#PaginateHelper.entriesPerPage', 'stoic/web#PaginateHelper.nextPage', 'stoic/web#PaginateHelper.lastPage', 'stoic/web#PaginateHelper.totalPages', 'stoic/web#PaginateHelper.currentPage'],
			[
				sample('paginate', <<<'PHP'
use Stoic\Web\PaginateHelper;

global $Db, $Stoic;

$wanted = $Stoic->getRequest()->getGet()->getInt('page', 1);
$total  = intval($Db->query("SELECT COUNT(*) FROM `Note`")->fetchColumn());
$pager  = new PaginateHelper($wanted, $total, 20);

$stmt = $Db->prepare("SELECT `ID`, `Title` FROM `Note` ORDER BY `ID` DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $pager->entriesPerPage, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $pager->entryOffset, \PDO::PARAM_INT);
$stmt->execute();

$rows  = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$links = $pager->getPages(5);
PHP)
			], 3),

		page('do', 'connect-to-more-than-one-database', 'Connect to more than one database',
			'Name each connection in siteSettings.json and ask the singleton for it by key.',
			<<<'MD'
The boot opens one {sym:stoic/pdo#PdoHelper} per `dbDsns.<key>` setting, reading the matching `dbUsers.<key>` and `dbPasses.<key>`. The `default` key is what {sym:stoic/web#Stoic.getDb} returns when called without an argument.

Add the settings through a configuration migration, since `stoic-configure -P` only edits keys that exist:

<<sample:cfg>>

Apply it and set the password, then read the connection by key:

<<sample:use>>

## What goes wrong

- A DSN that fails to connect is not registered. Asking for it returns a {sym:stoic/pdo#PdoHelper} whose {sym:stoic/pdo#PdoHelper.isActive} is false and whose query methods return empty defaults rather than throwing. Check `isActive()` after boot when a connection is optional.
- `stoic-migrate` targets the default connection unless you pass `-dsn`, `-user`, and `-pass` with the settings keys.
- The `dbDsn`, `dbUser`, and `dbPass` keys from older sites still work as the default connection, but only when `dbDsns` is absent.
MD,
			['stoic/pdo#PdoHelper', 'stoic/web#Stoic.getDb', 'stoic/pdo#PdoHelper.isActive', 'stoic/web#DatabaseManager', 'stoic/web/resources#SettingsStrings.DB_DSNS'],
			[
				sample('cfg', "dbDsns.reports[str] + pgsql:host=warehouse;dbname=reports\ndbUsers.reports[str] + reader\ndbPasses.reports[str] + <changeme>", 'migrations/cfg/1-2.cfg'),
				sample('use', <<<'PHP'
// vendor/bin/stoic-migrate
// vendor/bin/stoic-configure -PdbPasses.reports=secret

global $Stoic;

$reports = $Stoic->getDb('reports');

if ($reports->isActive()) {
	$count = $reports->query("SELECT COUNT(*) FROM sales")->fetchColumn();
}
PHP)
			], 4),

		page('do', 'build-urls-and-redirects', 'Build URLs and redirects relative to the site root',
			'Write ~/ paths once and let the page helper resolve them wherever the site is mounted.',
			<<<'MD'
{sym:stoic/web#PageHelper.getPage} creates or returns the helper for a page. The name you pass is the page's path below the document root; the helper strips it from `SCRIPT_NAME` to learn the root URL, so a site served from `/` and one served from `/notes/` produce the right links from the same code.

<<sample:page>>

`getAssetPath()` HTML-escapes the result and the query values, so its output can go straight into an attribute. Pass `true` as the third argument to prefix the scheme and host.

## Redirects

<<sample:redirect>>

{sym:stoic/web#PageHelper.redirectTo} sends a 302, or a 301 when `permanent` is true, and exits. It throws {sym:stoic/web/resources#HeadersAlreadySentException} if output has already started; the exception's `headers` property lists what was sent.

## What goes wrong

- The name must match the page's real location. A page at `web/admin/users.php` calls `getPage('admin/users.php')`, or its root becomes `/admin/`.
- The `~` shortcut only applies to the first character. `css/~/site.css` is left alone.
MD,
			['stoic/web#PageHelper', 'stoic/web#PageHelper.getPage', 'stoic/web#PageHelper.getAssetPath', 'stoic/web#PageHelper.redirectTo', 'stoic/web#PageHelper.setTitlePrefix', 'stoic/web#PageHelper.getTitle', 'stoic/web/resources#HeadersAlreadySentException'],
			[
				sample('page', <<<'PHP'
use Stoic\Web\PageHelper;

$page = PageHelper::getPage('notes/index.php');
$page->setTitlePrefix('Notes');
$page->setTitle('All notes');

$css  = $page->getAssetPath('~/css/site.css');
$next = $page->getAssetPath('~/notes/index.php', ['page' => 2]);

echo("<title>{$page->getTitle()}</title>");
echo("<link rel=\"stylesheet\" href=\"{$css}\">");
echo("<a href=\"{$next}\">Next</a>");
PHP),
				sample('redirect', <<<'PHP'
if (!$Stoic->getSession()->has('userId')) {
	$page->redirectTo('~/login.php');
}
PHP)
			], 4),

		page('do', 'see-upgrade-changes-for-your-code', 'See only the upgrade changes that affect your code',
			'Scan a project for the Stoic symbols it uses and filter the upgrade view to them.',
			<<<'MD'
The upgrade view can hide every change that does not touch a symbol your code references. The filter reads a scan report produced by the documentation site's own scanner, run against your project.

From a checkout of the docs site:

<<sample:scan>>

The scanner reads your `composer.lock` for the installed version and the package's namespaces, then walks your PHP files for `use` statements and fully qualified names. Passing `--namespaces Stoic` covers all four packages in one report, since they share the `Stoic` root namespace.

Open the upgrade view, choose the two versions, and load `scan.json`. The list shrinks to the changes whose symbols appear in the report.

## What goes wrong

- The report lists symbol names, not call sites. A class referenced only through a string, such as `new $className()`, is not seen.
- `--package` names the Composer package whose lock entry provides the version. With four packages, pick the one whose version you upgrade first.
MD,
			[],
			[
				sample('scan', "php scripts/scan-usage.php --project /path/to/your/app --package stoic/web --namespaces Stoic --out scan.json", 'terminal')
			], 3, 'v1.4')
	];
