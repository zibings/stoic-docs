<?php

	/**
	 * Reference prose for stoic/web, stoic/web/api, and stoic/web/resources.
	 */

	return [
		refPage('stoic/web#Stoic',
			'The per-request singleton that reads settings, loads your classes, opens databases, and holds the request.',
			<<<'MD'
<<sample:minimal>>

## getInstance() rules

The first call must pass a core path, the directory that holds `siteSettings.json` and `inc/`; it constructs the instance. Later calls with no arguments return it. A call that passes a core path **and** page variables **and** a logger constructs another instance and pushes it onto the stack; {sym:stoic/web#Stoic.getInstanceStack} lists them. The stack is per class, so {sym:stoic/web/api#Stoic} has its own.

## What the constructor does

Builds the {sym:stoic/web#Request}, reads `~/siteSettings.json`, loads `inc/classes` and `inc/repositories`, registers {sym:stoic/log#Logger.output} for shutdown, opens every `dbDsns.*` connection, starts the session, and loads `inc/utilities`. The explanation page on booting walks through the order and the constants that skip steps.

## Databases

{sym:stoic/web#Stoic.getDb} returns the connection registered under a key, `default` when omitted. A key with no working connection yields an inactive {sym:stoic/pdo#PdoHelper}; test {sym:stoic/pdo#PdoHelper.isActive} rather than catching an exception. There is no setter; connections come from settings.

## Headers

{sym:stoic/web#Stoic.setHeader} and {sym:stoic/web#Stoic.setRawHeader} log what they send and log a warning instead of failing when output has already begun. Prefer them to `header()` for that reason.
MD,
			['stoic/web#Stoic.getInstance', 'stoic/web#Stoic.getInstanceStack', 'stoic/web/api#Stoic', 'stoic/web#Request', 'stoic/log#Logger.output', 'stoic/web#Stoic.getDb', 'stoic/pdo#PdoHelper', 'stoic/pdo#PdoHelper.isActive', 'stoic/web#Stoic.setHeader', 'stoic/web#Stoic.setRawHeader', 'stoic/web#Stoic.getConfig', 'stoic/web#Stoic.getSession', 'stoic/web#Stoic.getRequest'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\Stoic;

$stoic = Stoic::getInstance('../');

$settings = $stoic->getConfig();
$request  = $stoic->getRequest();
$session  = $stoic->getSession();

if ($request->getGet()->getString('format') === 'json') {
	$stoic->setHeader('Content-Type', 'application/json');
}
PHP)
			]),

		refPage('stoic/web#Request',
			'The current request: verb, input, and each superglobal as a ParameterHelper.',
			<<<'MD'
<<sample:minimal>>

## Where getInput() reads from

| Request | `getInput()` returns |
|---|---|
| `GET` | the query string |
| any other verb, files uploaded | the `$_POST` fields |
| any other verb, empty body but `$_POST` set (a multipart form without files) | the `$_POST` fields |
| any other verb, JSON body | the decoded object; a scalar is wrapped in a one-element array |
| any other verb, other body (`application/x-www-form-urlencoded` included) | an empty helper |

The last row is the one that surprises people: an ordinary urlencoded form post has a body, so the request never falls back to `$_POST`, and the body is not JSON. Read {sym:stoic/web#Request.getPost} for those, or send JSON or multipart. {sym:stoic/web#Request.getRawInput} always has the body as read.

## Validity

The constructor throws {sym:stoic/web/resources#InvalidRequestException} when `REQUEST_METHOD` is missing or not one of the {sym:stoic/web/resources#RequestType} names. A non-`GET` request with no body and no `$_POST` is constructed but {sym:stoic/web#Request.isValid} is false, and `getRawInput()` throws.

## Testing

Pass a {sym:stoic/web/resources#PageVariables} built from arrays instead of the globals, and a string or array as the second argument instead of reading `php://input`. The framework's own tests are written this way.
MD,
			['stoic/web#Request.getInput', 'stoic/web#Request.getPost', 'stoic/web#Request.getRawInput', 'stoic/web/resources#InvalidRequestException', 'stoic/web/resources#RequestType', 'stoic/web#Request.isValid', 'stoic/web/resources#PageVariables', 'stoic/web#Request.getRequestType', 'stoic/web#Request.getServer', 'stoic/web#Request.getFiles', 'stoic/web#Request.hasFileUploads', 'stoic/web#Request.getContentType'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\Resources\RequestType;

$request = $Stoic->getRequest();

if ($request->getRequestType()->is(RequestType::POST) && $request->getContentType() === 'application/json') {
	$title = $request->getInput()->getString('title', '');
}

$agent = $request->getServer()->getString('HTTP_USER_AGENT', 'unknown');
PHP)
			]),

		refPage('stoic/web#PageHelper',
			'Per-page state: title, meta tags, and URL building relative to the site root.',
			<<<'MD'
<<sample:minimal>>

## One helper per page name

{sym:stoic/web#PageHelper.getPage} keeps a static registry keyed by the name you pass, so a template and the page that renders it share one helper. The first call captures `$_GET`, `$_POST`, and `$_REQUEST`; later calls can replace them.

## Root detection

The name is removed from `SCRIPT_NAME` to find the root URL path, so it must be the page's path below the document root: `getPage('admin/users.php')` for `web/admin/users.php`. {sym:stoic/web#PageHelper.setRoot} overrides the result.

## Asset paths

{sym:stoic/web#PageHelper.getAssetPath} resolves a leading `~` to the root path, appends query variables, and HTML-escapes the whole thing, so the result is safe in an attribute and wrong in a `header()` call. Use {sym:stoic/web#PageHelper.getRootUrlPath} when you need the unescaped root, with the scheme and host if you pass `true`.

## Titles and meta tags

{sym:stoic/web#PageHelper.setTitlePrefix} and {sym:stoic/web#PageHelper.setTitle} combine in {sym:stoic/web#PageHelper.getTitle} with the separator you chose. {sym:stoic/web#PageHelper.addMetaTag} collects {sym:stoic/web#HtmlElementHelper} elements for the template to render.

## Redirects

{sym:stoic/web#PageHelper.redirectTo} resolves `~`, sends a 302 (301 if `permanent`), and exits. If output already started it throws {sym:stoic/web/resources#HeadersAlreadySentException} instead.
MD,
			['stoic/web#PageHelper.getPage', 'stoic/web#PageHelper.setRoot', 'stoic/web#PageHelper.getAssetPath', 'stoic/web#PageHelper.getRootUrlPath', 'stoic/web#PageHelper.setTitlePrefix', 'stoic/web#PageHelper.setTitle', 'stoic/web#PageHelper.getTitle', 'stoic/web#PageHelper.addMetaTag', 'stoic/web#HtmlElementHelper', 'stoic/web#PageHelper.redirectTo', 'stoic/web/resources#HeadersAlreadySentException'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\PageHelper;

$page = PageHelper::getPage('notes/index.php');
$page->setTitlePrefix('Notes');
$page->setTitle('Recent');
$page->addMetaTag('description', 'The ten most recent notes');

$css = $page->getAssetPath('~/css/site.css');

foreach ($page->getMetaTags() as $tag) {
	$tag->render();
}
PHP)
			]),

		refPage('stoic/web#PaginateHelper',
			'Pagination arithmetic from a page number, a total, and a page size.',
			<<<'MD'
<<sample:minimal>>

## Clamping

`currentPage` below 1 becomes 1 and above `totalPages` becomes `totalPages`. An empty set has one page, a zero offset, and no next or previous page. `nextPage` and `lastPage` are `0` when there is no such page.

## Page windows

{sym:stoic/web#PaginateHelper.getPages} returns up to `numPages` consecutive page numbers around the current page, shifted so the window stays inside 1 to `totalPages`. With fewer pages than requested you get all of them.
MD,
			['stoic/web#PaginateHelper.getPages', 'stoic/web#PaginateHelper.entryOffset', 'stoic/web#PaginateHelper.nextPage', 'stoic/web#PaginateHelper.lastPage'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\PaginateHelper;

$pager = new PaginateHelper(3, 100, 20);

$pager->totalPages;     // 5
$pager->entryOffset;    // 40
$pager->lastPage;       // 2
$pager->nextPage;       // 4
$pager->getPages(3);    // [2, 3, 4]
PHP)
			]),

		refPage('stoic/web#FileUploadHelper',
			'Normalized view of $_FILES: every input name maps to a list of UploadedFile.',
			<<<'MD'
<<sample:minimal>>

PHP shapes `$_FILES` differently for `<input name="doc">` and `<input name="doc[]">`. The helper flattens both into a list of {sym:stoic/web/resources#UploadedFile} per input name, so {sym:stoic/web#FileUploadHelper.getFile} always returns an array, empty for an unknown name. {sym:stoic/web#FileUploadHelper.count} is the number of input names, not of files.

Construct it with no argument to read `$_FILES`, or let {sym:stoic/web#Request.getFiles} build it from the request's variables.
MD,
			['stoic/web/resources#UploadedFile', 'stoic/web#FileUploadHelper.getFile', 'stoic/web#FileUploadHelper.count', 'stoic/web#Request.getFiles'],
			[
				sample('minimal', <<<'PHP'
foreach ($Stoic->getRequest()->getFiles()->getFile('doc') as $file) {
	if ($file->isValid()) {
		move_uploaded_file($file->tmpName, '../uploads/' . bin2hex(random_bytes(8)) . '.pdf');
	}
}
PHP)
			]),

		refPage('stoic/web#HtmlElementHelper',
			'Build one HTML element from a tag, attributes, and contents.',
			<<<'MD'
<<sample:minimal>>

{sym:stoic/web#HtmlElementHelper.render} echoes the element by default and returns an empty helper; pass `true` to get the markup back instead. Attribute values have `"`, `<`, and `>` replaced with entities; contents are written as given, so escape user text before {sym:stoic/web#HtmlElementHelper.setContents} or {sym:stoic/web#HtmlElementHelper.appendContents}. An element with no contents renders self-closing.
MD,
			['stoic/web#HtmlElementHelper.render', 'stoic/web#HtmlElementHelper.setContents', 'stoic/web#HtmlElementHelper.appendContents', 'stoic/web#HtmlElementHelper.addAttribute'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\HtmlElementHelper;

$link = new HtmlElementHelper('a');
$link->addAttribute('href', $page->getAssetPath('~/notes/index.php'));
$link->setContents(htmlspecialchars($note->title));

$html = $link->render(true);   // <a href="/notes/index.php">First</a>
PHP)
			]),

		refPage('stoic/web#DatabaseManager',
			'Registry of named PdoHelper connections held by the Stoic singleton.',
			<<<'MD'
The singleton fills it from the `dbDsns.*` settings during boot and {sym:stoic/web#Stoic.getDb} reads from it. {sym:stoic/web#DatabaseManager.getDatabase} returns a new, inactive {sym:stoic/pdo#PdoHelper} for an unknown key rather than throwing. You normally do not construct one; the class is public so tests can register connections with {sym:stoic/web#DatabaseManager.setDatabase}.
MD,
			['stoic/web#Stoic.getDb', 'stoic/web#DatabaseManager.getDatabase', 'stoic/pdo#PdoHelper', 'stoic/web#DatabaseManager.setDatabase']),

		refPage('stoic/web/api#Stoic',
			'The singleton for a JSON API: routes by verb and pattern, authorizes, and encodes responses.',
			<<<'MD'
<<sample:minimal>>

## Routing

{sym:stoic/web/api#Stoic.handle} reads the path from the `url` query parameter, which a rewrite rule supplies, strips the front controller's directory from it, and tests the patterns registered for the request's verb in registration order. The first match wins. With no match, or no `url` at all, the default endpoint runs if one was registered with a `null` pattern; otherwise the response is a 404 with a JSON string.

## Authorization

An endpoint registered with a truthy fourth argument sends an {sym:stoic/web/resources#ApiAuthorizationDispatch} through the chain built from {sym:stoic/web/api#Stoic.linkAuthorizationNode}. A chain with no nodes fails the traverse and the client gets a 403 reading "Unable to perform authorization"; a chain whose nodes never call `authorize()` gets a 403 reading "Unauthorized access for auth-only endpoint". The dispatch carries the request input and the roles value from the registration, so a node can make either decision.

## CORS and preflight

`handle()` always sends `Access-Control-Allow-Credentials`, `Cache-Control`, and `Content-Type` from the `api.*` settings. If the request has an `Origin` header that appears in `cors.origins` (or `*` does), it is echoed back in `Access-Control-Allow-Origin`; otherwise `*` is sent. An `OPTIONS` request with `Access-Control-Request-Method` is answered with the `cors.headers` and `cors.methods` settings and ends there.

## Output

The callback runs inside an output buffer that is discarded, so nothing it prints reaches the client. A returned {sym:stoic/web/api#Response} is JSON-encoded from its data with its status code; a returned string is printed as is; `null` produces no body.
MD,
			['stoic/web/api#Stoic.handle', 'stoic/web/resources#ApiAuthorizationDispatch', 'stoic/web/api#Stoic.linkAuthorizationNode', 'stoic/web/api#Response', 'stoic/web/api#Stoic.registerEndpoint', 'stoic/web/api#Stoic.getInstance', 'stoic/web/resources#SettingsStrings', 'stoic/web#Stoic'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\Api\Response;
use Stoic\Web\Api\Stoic;
use Stoic\Web\Request;
use Stoic\Web\Resources\HttpStatusCodes;

$api = Stoic::getInstance('../../');

$api->registerEndpoint('GET', '/^health$/i', function (Request $request, array $matches) : Response {
	return new Response(HttpStatusCodes::OK, ['ok' => true]);
});

$api->registerEndpoint(null, null, function (Request $request, array $matches) : Response {
	$ret = new Response();
	$ret->setAsError("Unknown route", HttpStatusCodes::NOT_FOUND);

	return $ret;
});

$api->handle();
PHP)
			]),

		refPage('stoic/web/api#Stoic.registerEndpoint',
			'Attach a callable to a verb and a URL pattern, optionally requiring authorization.',
			<<<'MD'
<<sample:minimal>>

## Verbs

`verbs` is a single verb name, several joined with `|`, or `*` for all of them. The names are the {sym:stoic/web/resources#RequestType} constants: `DELETE`, `GET`, `HEAD`, `OPTIONS`, `PATCH`, `POST`, `PUT`.

## Patterns and matches

`pattern` is a complete regular expression, delimiters and flags included, matched with `PREG_OFFSET_CAPTURE`. The callback's second argument is therefore an array where each entry is `[text, offset]`; the first capture group is `$matches[1][0]`. Registering the same pattern twice for a verb keeps the first callback. A `null` pattern makes the endpoint the default for unmatched requests, regardless of verb.

## The callable

Anything `call_user_func()` accepts: a closure, `[$object, 'method']`, or a static method array. A method name on its own is not callable and is rejected. The callback receives the {sym:stoic/web#Request} and the matches, and should return a {sym:stoic/web/api#Response}.

## authRoles

`null` or `false` means public. Any other value, typically `true` or a list of role names, requires the authorization chain to authorize the request, and the value is passed to nodes through {sym:stoic/web/resources#ApiAuthorizationDispatch.getRequiredRoles}.
MD,
			['stoic/web/resources#RequestType', 'stoic/web#Request', 'stoic/web/api#Response', 'stoic/web/resources#ApiAuthorizationDispatch.getRequiredRoles', 'stoic/web/resources#ApiEndpoint'],
			[
				sample('minimal', <<<'PHP'
$api->registerEndpoint('GET|HEAD', '/^notes\/(\d+)$/i', [$this, 'one']);
$api->registerEndpoint('POST', '/^notes$/i', [$this, 'create'], true);
$api->registerEndpoint('DELETE', '/^notes\/(\d+)$/i', [$this, 'remove'], ['admin']);
PHP)
			]),

		refPage('stoic/web/api#Response',
			'What an endpoint returns: data to encode and an HTTP status.',
			<<<'MD'
<<sample:minimal>>

The data can be anything `json_encode()` accepts, including a model, since {sym:stoic/pdo#BaseDbModel} implements `JsonSerializable`. {sym:stoic/web/api#Response.getStatus} returns a blank {sym:stoic/web/resources#HttpStatusCodes} when none was set, which the front controller sends as no status change, so set one. {sym:stoic/web/api#Response.setAsError} sets a message as the data and a status in one call and throws `InvalidArgumentException` for an unknown status number.
MD,
			['stoic/pdo#BaseDbModel', 'stoic/web/api#Response.getStatus', 'stoic/web/resources#HttpStatusCodes', 'stoic/web/api#Response.setAsError', 'stoic/web/api#Response.setData', 'stoic/web/api#Response.setStatus'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\Api\Response;
use Stoic\Web\Resources\HttpStatusCodes;

$ret = new Response(HttpStatusCodes::OK, ['count' => 3]);

if ($count < 0) {
	$ret->setAsError("Count cannot be negative", HttpStatusCodes::BAD_REQUEST);
}

return $ret;
PHP)
			]),

		refPage('stoic/web/api#BaseDbApi',
			'Base for API controllers: a database, a logger, and two small helpers.',
			<<<'MD'
Extend it, register your routes in the constructor after calling the parent, and use `$this->db` and `$this->log` in the handlers. {sym:stoic/web/api#BaseDbApi.newResponse} returns a {sym:stoic/web/api#Response} already marked 200. {sym:stoic/web/api#BaseDbApi.requestHasInputVars} checks that every listed key is present in {sym:stoic/web#Request.getInput}; it is the same test as {sym:stoic/utilities#ParameterHelper.hasAll} on the input helper.

The class extends {sym:stoic/pdo#BaseDbClass}, so `$this->db` is whatever `PDO` you pass. Extend {sym:stoic/pdo#StoicDbClass} instead when you want a {sym:stoic/pdo#PdoHelper} guaranteed.
MD,
			['stoic/web/api#BaseDbApi.newResponse', 'stoic/web/api#Response', 'stoic/web/api#BaseDbApi.requestHasInputVars', 'stoic/web#Request.getInput', 'stoic/utilities#ParameterHelper.hasAll', 'stoic/pdo#BaseDbClass', 'stoic/pdo#StoicDbClass', 'stoic/pdo#PdoHelper']),

		refPage('stoic/web/resources#ApiAuthorizationDispatch',
			'The dispatch the API sends through its authorization chain for protected endpoints.',
			<<<'MD'
<<sample:minimal>>

The API builds and initializes it: {sym:stoic/web/resources#ApiAuthorizationDispatch.getInput} is the request's input helper and {sym:stoic/web/resources#ApiAuthorizationDispatch.getRequiredRoles} is the value given when the endpoint was registered. A node grants access by calling {sym:stoic/web/resources#ApiAuthorizationDispatch.authorize}; there is no deny, so a node that wants the final word should call {sym:stoic/chain#DispatchBase.consume} after deciding.

Note that the input helper does not include headers or cookies. A node that authenticates from those reads them from `$sender`, which is the API singleton, through its request.
MD,
			['stoic/web/resources#ApiAuthorizationDispatch.getInput', 'stoic/web/resources#ApiAuthorizationDispatch.getRequiredRoles', 'stoic/web/resources#ApiAuthorizationDispatch.authorize', 'stoic/chain#DispatchBase.consume', 'stoic/chain#NodeBase', 'stoic/web/api#Stoic'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;
use Stoic\Web\Resources\ApiAuthorizationDispatch;

class SessionAuthorizer extends NodeBase {
	public function __construct() {
		$this->setKey('SessionAuthorizer');
		$this->setVersion('1.0.0');
	}

	public function process(mixed $sender, DispatchBase &$dispatch) : void {
		if (!($dispatch instanceof ApiAuthorizationDispatch)) {
			return;
		}

		$session = $sender->getSession();
		$roles   = $dispatch->getRequiredRoles();

		if ($session->has('userId') && ($roles === true || in_array($session->getString('role'), (array) $roles, true))) {
			$dispatch->authorize();
		}

		$dispatch->consume();
	}
}
PHP)
			]),

		refPage('stoic/web/resources#PageVariables',
			'A struct of the eight request superglobals, so code can run against fakes.',
			<<<'MD'
<<sample:minimal>>

{sym:stoic/web/resources#PageVariables.fromGlobals} snapshots `$_COOKIE`, `$_ENV`, `$_FILES`, `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, and `$_SESSION`. The constructor takes the same eight arrays in that order. {sym:stoic/web#Request} and therefore {sym:stoic/web#Stoic} read only from this struct, which is how the generated `core.php` boots a command-line script with a fake `GET` request and how the framework's tests drive requests without a web server.
MD,
			['stoic/web/resources#PageVariables.fromGlobals', 'stoic/web#Request', 'stoic/web#Stoic'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Web\Request;
use Stoic\Web\Resources\PageVariables;

$vars    = new PageVariables([], [], [], ['page' => '2'], [], [], ['REQUEST_METHOD' => 'GET'], []);
$request = new Request($vars);

$request->getInput()->getInt('page');   // 2
PHP)
			]),

		refPage('stoic/web/resources#HttpStatusCodes',
			'Enum of HTTP status codes with their standard reason phrases.',
			<<<'MD'
Constants are named after the reason phrase (`OK`, `NOT_FOUND`, `UNPROCESSABLE_ENTITY`) and valued with the status number, so `new HttpStatusCodes(404)` and `HttpStatusCodes::NOT_FOUND` describe the same status. {sym:stoic/web/resources#HttpStatusCodes.getDescription} returns the phrase, or "Unknown Status Code" for a blank instance. {sym:stoic/web/api#Response} accepts either an integer or an instance wherever a status is expected.
MD,
			['stoic/web/resources#HttpStatusCodes.getDescription', 'stoic/web/api#Response', 'stoic/utilities#EnumBase']),

		refPage('stoic/web/resources#SettingsStrings',
			'The keys the framework reads from siteSettings.json, with the defaults used when they are absent.',
			<<<'MD'
| Constant | Key | Default | Read by |
|---|---|---|---|
| `INCLUDE_PATH` | `includePath` | `~/inc` | boot |
| `CLASSES_PATH` / `CLASSES_EXTENSION` | `classesPath` / `classesExt` | `classes` / `.cls.php` | boot |
| `REPOS_PATH` / `REPOS_EXTENSION` | `reposPath` / `reposExt` | `repositories` / `.rpo.php` | boot |
| `UTILITIES_PATH` / `UTILITIES_EXT` | `utilitiesPath` / `utilitiesExt` | `utilities` / `.utl.php` | boot |
| `DB_DSNS`, `DB_USERS`, `DB_PASSES` | `dbDsns.<key>` and friends | none; `sqlite::memory:` is what `stoic-create` writes | boot |
| `API_CACHE_CONTROL` | `api.cacheControl` | `max-age=500` | API `handle()` |
| `API_CONTENT_TYPE` | `api.contentType` | `application/json` | API `handle()` |
| `CORS_ORIGINS` | `cors.origins` | `[]` (every origin gets `*`) | API `handle()` |
| `CORS_HEADERS` | `cors.headers` | `Accept, Authorization, Content-Type, X-CSRF-Token, App-Token, Token` | API preflight |
| `CORS_METHODS` | `cors.methods` | `POST, GET, OPTIONS` | API preflight |
| `MIGRATE_CFG_PATH` / `MIGRATE_DB_PATH` | `migrateCfg` / `migrateDb` | `~/migrations/cfg` / `~/migrations/db` | `stoic-migrate`, `stoic-configure` |
| `ENABLE_LOGGING` | `enableLogging` | `false` | the generated API front controller |

`stoic-create` writes every key with its default into a new `siteSettings.json`, so an existing site only lacks keys added after it was created; add those with a configuration migration.
MD,
			['stoic/web#Stoic', 'stoic/web/api#Stoic.handle', 'stoic/web/resources#StoicStrings']),

		refPage('stoic/web/resources#UploadedFile',
			'One uploaded file with its error decoded.',
			<<<'MD'
Fields mirror one entry of `$_FILES`: `name` as the browser sent it, `type` as the browser claimed it, `size` in bytes, `tmpName` on the server, and the `error` code. {sym:stoic/web/resources#UploadedFile.isValid} is true for `UPLOAD_ERR_OK` and {sym:stoic/web/resources#UploadedFile.getError} turns the code into a sentence for a log or a response. Move the file with `move_uploaded_file()` before the request ends; PHP deletes the temporary file afterwards.
MD,
			['stoic/web/resources#UploadedFile.isValid', 'stoic/web/resources#UploadedFile.getError', 'stoic/web#FileUploadHelper'])
	];
