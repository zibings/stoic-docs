<?php

	/**
	 * Reference prose for stoic/stoic and stoic/io: the utilities, chain, and log modules.
	 */

	return [
		refPage('stoic/utilities#ReturnHelper',
			'A status plus messages plus results, bad until you mark it good.',
			<<<'MD'
<<sample:minimal>>

## The status starts bad

The constructor sets `STATUS_BAD`. Only {sym:stoic/utilities#ReturnHelper.makeGood} changes that, so every success path must call it. Adding results or messages never changes the status.

## Messages and results are always lists

{sym:stoic/utilities#ReturnHelper.getMessages} and {sym:stoic/utilities#ReturnHelper.getResults} return arrays even when one item was added, so a single result is `getResults()[0]`. {sym:stoic/utilities#ReturnHelper.addMessages} and {sym:stoic/utilities#ReturnHelper.addResults} throw `InvalidArgumentException` on an empty array; use the singular methods in a loop when the count may be zero.

## Nesting

A method that calls other ReturnHelper-returning methods usually forwards their messages with `addMessages($inner->getMessages())` when the inner result is bad and its messages are non-empty, then returns its own bad helper. {sym:stoic/pdo#BaseDbModel} does exactly this with the `__can*` guards.
MD,
			['stoic/utilities#ReturnHelper.makeGood', 'stoic/utilities#ReturnHelper.getMessages', 'stoic/utilities#ReturnHelper.getResults', 'stoic/utilities#ReturnHelper.addMessages', 'stoic/utilities#ReturnHelper.addResults', 'stoic/pdo#BaseDbModel'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\ReturnHelper;

function parsePort(string $value) : ReturnHelper {
	$ret = new ReturnHelper();

	if (!ctype_digit($value) || intval($value) > 65535) {
		$ret->addMessage("'{$value}' is not a port number");

		return $ret;
	}

	$ret->addResult(intval($value));
	$ret->makeGood();

	return $ret;
}

$port = parsePort('8080');

if ($port->isGood()) {
	listen($port->getResults()[0]);
}
PHP)
			]),

		refPage('stoic/utilities#EnumBase',
			'Base class for integer-backed enums with a name, a value, and a blank state.',
			<<<'MD'
<<sample:minimal>>

## Building instances

`new Priority(2)` and {sym:stoic/utilities#EnumBase.fromString} build from a value or a name. {sym:stoic/utilities#EnumBase.tryGet} accepts an integer, an existing instance, or null and always returns an instance of the called class. {sym:stoic/utilities#EnumBase.tryGetEnum} does the same for a class named at runtime and throws `InvalidArgumentException` if that class does not extend `EnumBase`.

An invalid value or name gives a **blank** instance: `getValue()` and `getName()` are null and {sym:stoic/utilities#EnumBase.is} is false for every constant. Nothing throws. Validate with {sym:stoic/utilities#EnumBase.validValue} or {sym:stoic/utilities#EnumBase.validName} when a bad value should stop the caller.

## Comparing

Compare with {sym:stoic/utilities#EnumBase.is} and {sym:stoic/utilities#EnumBase.isIn}, passing the class constants. Object equality operators compare the wrong things.

## Serialization

`(string)` and `json_encode()` produce the **name** by default. Pass `false` as the second constructor argument (or to `fromString()` and `tryGet()`) to produce the value instead. A blank instance serializes as an empty string.

## Constants must be integers

{sym:stoic/utilities#EnumBase.getConstList} reads every constant on the subclass through reflection and caches the name-to-value and value-to-name maps. Two constants with the same value share a name in the reverse map, so keep values unique.
MD,
			['stoic/utilities#EnumBase.fromString', 'stoic/utilities#EnumBase.tryGet', 'stoic/utilities#EnumBase.tryGetEnum', 'stoic/utilities#EnumBase.is', 'stoic/utilities#EnumBase.isIn', 'stoic/utilities#EnumBase.validValue', 'stoic/utilities#EnumBase.validName', 'stoic/utilities#EnumBase.getConstList'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\EnumBase;

class Priority extends EnumBase {
	const int LOW    = 1;
	const int NORMAL = 2;
	const int HIGH   = 3;
}

$priority = Priority::tryGet($request->getInput()->getInt('priority'));

if ($priority->is(Priority::HIGH)) {
	notifyOnCall();
}

echo(json_encode(['priority' => $priority]));   // {"priority":"HIGH"}
PHP)
			]),

		refPage('stoic/utilities#StringHelper',
			'A mutable string wrapper with searching, replacing, and comparison helpers.',
			<<<'MD'
<<sample:minimal>>

## Mutating versus copying

{sym:stoic/utilities#StringHelper.append}, {sym:stoic/utilities#StringHelper.replace}, {sym:stoic/utilities#StringHelper.toLower}, and {sym:stoic/utilities#StringHelper.toUpper} change the instance and return nothing. {sym:stoic/utilities#StringHelper.subString} and {sym:stoic/utilities#StringHelper.data} return plain strings. Use {sym:stoic/utilities#StringHelper.copy} before mutating a value you were handed, since {sym:stoic/web#PageHelper} and {sym:stoic/pdo#BaseDbModel} hand out copies for the same reason.

## Null is a state

A helper built with no argument holds `null`, not an empty string. {sym:stoic/utilities#StringHelper.isEmptyOrNull} and {sym:stoic/utilities#StringHelper.isEmptyOrNullOrWhitespace} treat both the same; {sym:stoic/utilities#StringHelper.find} and {sym:stoic/utilities#StringHelper.compare} return `false` on a null store, which is also what `strpos()` returns for "not found". Compare with `=== false` and `=== 0`.

## Joining

{sym:stoic/utilities#StringHelper.join} is variadic: the glue first, then the parts. Pass a {sym:stoic/utilities#StringJoinOptions} as the first argument with `guardGlue` true to avoid doubled separators when parts already begin or end with the glue, which is what makes it useful for paths.
MD,
			['stoic/utilities#StringHelper.append', 'stoic/utilities#StringHelper.replace', 'stoic/utilities#StringHelper.toLower', 'stoic/utilities#StringHelper.toUpper', 'stoic/utilities#StringHelper.subString', 'stoic/utilities#StringHelper.data', 'stoic/utilities#StringHelper.copy', 'stoic/web#PageHelper', 'stoic/pdo#BaseDbModel', 'stoic/utilities#StringHelper.isEmptyOrNull', 'stoic/utilities#StringHelper.isEmptyOrNullOrWhitespace', 'stoic/utilities#StringHelper.find', 'stoic/utilities#StringHelper.compare', 'stoic/utilities#StringHelper.join', 'stoic/utilities#StringJoinOptions'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\StringHelper;
use Stoic\Utilities\StringJoinOptions;

$path = StringHelper::join(new StringJoinOptions('/', true), 'assets/', '/css/', 'site.css');
echo($path);                  // assets/css/site.css

$title = new StringHelper('  Hello, world  ');
$title->replace('world', 'Stoic');

if ($title->startsWith('Hello', true)) {
	echo($title->subString(2));
}
PHP)
			]),

		refPage('stoic/utilities#ParameterHelper',
			'Read-only access to an array of request or argument values with typed getters.',
			<<<'MD'
<<sample:minimal>>

## Typed getters coerce, they do not validate

{sym:stoic/utilities#ParameterHelper.getInt}, {sym:stoic/utilities#ParameterHelper.getFloat}, {sym:stoic/utilities#ParameterHelper.getBool}, and {sym:stoic/utilities#ParameterHelper.getString} return their default only when the key is absent. A present value is pushed through the matching sanitizer in {sym:stoic/utilities#SanitationHelper}, whatever it looks like: a non-numeric string becomes its length as an integer, an array becomes its count, and only the string `true` is a true boolean. Check the shape with {sym:stoic/utilities#ParameterHelper.get} first when it matters.

{sym:stoic/utilities#ParameterHelper.getJson} decodes a JSON string held under a key and returns the default when decoding fails.

## Checking keys

{sym:stoic/utilities#ParameterHelper.has}, {sym:stoic/utilities#ParameterHelper.hasAll}, and {sym:stoic/utilities#ParameterHelper.hasAny} test key presence only. A key set to an empty string counts as present.

## Deriving new helpers

The `with*` and `without*` methods return clones; the original never changes. {sym:stoic/utilities#ParameterHelper.getSource} returns the array the instance was built from, before any of those changes.
MD,
			['stoic/utilities#ParameterHelper.getInt', 'stoic/utilities#ParameterHelper.getFloat', 'stoic/utilities#ParameterHelper.getBool', 'stoic/utilities#ParameterHelper.getString', 'stoic/utilities#SanitationHelper', 'stoic/utilities#ParameterHelper.get', 'stoic/utilities#ParameterHelper.getJson', 'stoic/utilities#ParameterHelper.has', 'stoic/utilities#ParameterHelper.hasAll', 'stoic/utilities#ParameterHelper.hasAny', 'stoic/utilities#ParameterHelper.getSource', 'stoic/utilities#ParameterHelper.withParameter'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\ParameterHelper;

$params = new ParameterHelper(['page' => '3', 'q' => 'stoic', 'debug' => 'true']);

$page  = $params->getInt('page', 1);        // 3
$query = $params->getString('q', '');       // "stoic"
$debug = $params->getBool('debug', false);  // true
$size  = $params->getInt('size', 20);       // 20, key absent

$next = $params->withParameter('page', $page + 1);
PHP)
			]),

		refPage('stoic/utilities#SanitationHelper',
			'Registry of named sanitizers that coerce values to a type.',
			<<<'MD'
<<sample:minimal>>

## Defaults

A new helper registers `bool`, `int`, `float`, and `string`, backed by the classes in `stoic/utilities/sanitizers`. The constants on this class name them. `json` is not registered by default even though {sym:stoic/utilities/sanitizers#JsonSanitizer} ships; add it when you want it.

## Adding your own

{sym:stoic/utilities#SanitationHelper.addSanitizer} accepts an instance or a class name that implements {sym:stoic/utilities/sanitizers#SanitizerInterface}. Anything else is ignored silently. A {sym:stoic/utilities#ParameterHelper} built with your helper as its second argument uses the extra sanitizers through its {sym:stoic/utilities#ParameterHelper.get} method's third argument.

## Unknown keys pass through

{sym:stoic/utilities#SanitationHelper.sanitize} returns the input unchanged when the key is not registered. Check {sym:stoic/utilities#SanitationHelper.hasSanitizer} if silence would hide a typo.
MD,
			['stoic/utilities/sanitizers#JsonSanitizer', 'stoic/utilities#SanitationHelper.addSanitizer', 'stoic/utilities/sanitizers#SanitizerInterface', 'stoic/utilities#ParameterHelper', 'stoic/utilities#ParameterHelper.get', 'stoic/utilities#SanitationHelper.sanitize', 'stoic/utilities#SanitationHelper.hasSanitizer'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\ParameterHelper;
use Stoic\Utilities\SanitationHelper;
use Stoic\Utilities\Sanitizers\JsonSanitizer;
use Stoic\Utilities\Sanitizers\SanitizerInterface;

class SlugSanitizer implements SanitizerInterface {
	public function sanitize(mixed $input) : mixed {
		return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $input)), '-');
	}
}

$sanitizer = new SanitationHelper();
$sanitizer->addSanitizer('json', JsonSanitizer::class)->addSanitizer('slug', new SlugSanitizer());

$params = new ParameterHelper($_GET, $sanitizer);
$slug   = $params->get('title', '', 'slug');
PHP)
			]),

		refPage('stoic/utilities#FileHelper',
			'Filesystem operations relative to a root that `~` stands for.',
			<<<'MD'
<<sample:minimal>>

## The `~` root

Every path that begins with `~` has it replaced by the relative path given to the constructor, so `~/logs/app.log` is the same file wherever the current script lives. Paths that do not begin with `~` are used as given. {sym:stoic/utilities#FileHelper.pathJoin} joins segments with `/`, trims doubled slashes, and applies the same replacement.

## Loading PHP files once

{sym:stoic/utilities#FileHelper.load} requires a file and records it in a **static** cache shared by every instance, so the same file is never required twice in one process unless you pass `allowReload`. This is how {sym:stoic/web#Stoic} avoids re-including classes when a second instance boots. Both `load()` and {sym:stoic/utilities#FileHelper.loadGroup} throw `InvalidArgumentException` for a missing file.

## Writing

{sym:stoic/utilities#FileHelper.putContents} throws when the data is empty; use {sym:stoic/utilities#FileHelper.touchFile} to create an empty file. Pass `FILE_APPEND` in the flags to append. {sym:stoic/utilities#FileHelper.copyFile} and {sym:stoic/utilities#FileHelper.copyFolder} refuse to overwrite an existing destination.

## Listing

{sym:stoic/utilities#FileHelper.getFolderFiles} and {sym:stoic/utilities#FileHelper.getFolderFolders} return sorted full paths, or `null` when the folder does not exist. Folder entries end with `/`.
MD,
			['stoic/utilities#FileHelper.pathJoin', 'stoic/utilities#FileHelper.load', 'stoic/web#Stoic', 'stoic/utilities#FileHelper.loadGroup', 'stoic/utilities#FileHelper.putContents', 'stoic/utilities#FileHelper.touchFile', 'stoic/utilities#FileHelper.copyFile', 'stoic/utilities#FileHelper.copyFolder', 'stoic/utilities#FileHelper.getFolderFiles', 'stoic/utilities#FileHelper.getFolderFolders'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\FileHelper;

$fh = new FileHelper('../');

if (!$fh->folderExists('~/cache')) {
	$fh->makeFolder('~/cache', 0775, true);
}

$fh->putContents('~/cache/manifest.json', json_encode(['built' => time()]));

foreach ($fh->getFolderFiles('~/cache') ?? [] as $file) {
	echo($file, "\n");
}
PHP)
			]),

		refPage('stoic/utilities#ConsoleHelper',
			'Parsed command-line arguments plus reading from and writing to the terminal.',
			<<<'MD'
<<sample:minimal>>

## How arguments are parsed

The first element of `$argv` is the script name, available from {sym:stoic/utilities#ConsoleHelper.getSelf}. Everything after it is parsed into an associative array: `-key value`, `--key value`, and `key=value` all produce `key => value`, while a key with no value, or one followed by another `-` argument, produces `key => true`. One and two dashes are equivalent. The original list is still available from {sym:stoic/utilities#ConsoleHelper.parameters} and by index with {sym:stoic/utilities#ConsoleHelper.compareArgAt}.

Two parsed copies are kept, one with lowercased keys. {sym:stoic/utilities#ConsoleHelper.hasArg}, {sym:stoic/utilities#ConsoleHelper.hasShortLongArg}, and {sym:stoic/utilities#ConsoleHelper.getParameterWithDefault} pick between them with their `caseInsensitive` argument, which defaults to case-sensitive.

## Prompting

{sym:stoic/utilities#ConsoleHelper.getQueriedInput} loops until the input passes a validation callback or the attempts run out, then returns a {sym:stoic/utilities#ReturnHelper} whose first result is the sanitized value. An empty answer with a default accepts the default. The validator may return a bool or a `ReturnHelper`; a bad helper's first message is shown after the error message.

## Outside the terminal

{sym:stoic/utilities#ConsoleHelper.isCLI} is true when PHP runs from the command line or when the instance was built with `forceCli`. Output goes through `echo`, so the helper also works for pages that want the same formatting.
MD,
			['stoic/utilities#ConsoleHelper.getSelf', 'stoic/utilities#ConsoleHelper.parameters', 'stoic/utilities#ConsoleHelper.compareArgAt', 'stoic/utilities#ConsoleHelper.hasArg', 'stoic/utilities#ConsoleHelper.hasShortLongArg', 'stoic/utilities#ConsoleHelper.getParameterWithDefault', 'stoic/utilities#ConsoleHelper.getQueriedInput', 'stoic/utilities#ReturnHelper', 'stoic/utilities#ConsoleHelper.isCLI'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\ConsoleHelper;

$ch = new ConsoleHelper($argv);

if ($ch->hasShortLongArg('h', 'help', true)) {
	$ch->putLine("Usage: php rebuild.php --target <dir> [--force]");

	exit;
}

$target = $ch->getParameterWithDefault('t', 'target', './build', true);
$force  = $ch->hasShortLongArg('f', 'force', true);

$ch->put("Rebuilding into {$target}.. ");
$ch->putLine("DONE");
PHP)
			]),

		refPage('stoic/utilities#CliScriptHelper',
			'Declared options for a script, with generated help and required-option checks.',
			<<<'MD'
<<sample:minimal>>

## Lifecycle

{sym:stoic/utilities#CliScriptHelper.startScript} prints the script name underlined, prints the full help and exits when `-h` or `--help` is present, and by default calls {sym:stoic/utilities#CliScriptHelper.checkRequirements}, which prints a message naming the missing options and exits. After it returns, {sym:stoic/utilities#CliScriptHelper.getOptions} gives every declared option under both names with defaults applied.

`--help name` prints the long description of one option instead of everything.

## Declaring options

{sym:stoic/utilities#CliScriptHelper.addOption} takes an identifier used in messages, the short and long names, a one-line description for the option table, a longer description for `--help name`, whether it is required, and a default. Both names are mandatory and `h`/`help` are reserved; violations throw `InvalidArgumentException`. Matching is case-insensitive.

{sym:stoic/utilities#CliScriptHelper.addExample} adds a block to the help output; a heredoc keeps multi-line examples readable.

## Skipping the check

Pass `false` to `startScript()` when required options depend on a mode, then call {sym:stoic/utilities#CliScriptHelper.satisfiesRequirements} yourself where it makes sense.
MD,
			['stoic/utilities#CliScriptHelper.startScript', 'stoic/utilities#CliScriptHelper.checkRequirements', 'stoic/utilities#CliScriptHelper.getOptions', 'stoic/utilities#CliScriptHelper.addOption', 'stoic/utilities#CliScriptHelper.addExample', 'stoic/utilities#CliScriptHelper.satisfiesRequirements', 'stoic/utilities#ConsoleHelper'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\CliScriptHelper;
use Stoic\Utilities\ConsoleHelper;

$ch     = new ConsoleHelper($argv);
$script = new CliScriptHelper('Cache warmer', 'Requests every page once so the cache is hot.', $ch);

$script->addOption('base', 'b', 'base', 'Base URL', 'Origin to request pages from, without a trailing slash.', true)
	->addOption('limit', 'l', 'limit', 'Page limit', 'Stop after this many pages.', false, 100)
	->addExample('php warm.php --base https://example.com --limit 50');

$opts = $script->startScript()->getOptions();

warm($opts['base'], intval($opts['limit']));
PHP)
			]),

		refPage('stoic/utilities#LogFileAppender',
			'Appender that writes each message as a line of text or JSON to a file.',
			<<<'MD'
<<sample:minimal>>

## Output types

`LogFileOutputTypes::PLAIN` writes the {sym:stoic/log#Message} string form: timestamp, level, message. `LogFileOutputTypes::JSON` writes one JSON object per line with `level`, `message`, and `timestamp` keys. An unknown type throws `InvalidArgumentException`.

## Path resolution

The path goes through the {sym:stoic/utilities#FileHelper} you pass, so `~/logs/app.log` is relative to that helper's root. The file is created on construction if it does not exist and its real path is remembered, so a later `chdir()` does not move the log.

## When it writes

Only when {sym:stoic/log#Logger.output} runs, which the site boot does at shutdown. Each output appends the batch in one write.
MD,
			['stoic/log#Message', 'stoic/utilities#FileHelper', 'stoic/log#Logger.output', 'stoic/utilities#LogFileOutputTypes', 'stoic/log#Logger.addAppender'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Utilities\LogFileAppender;
use Stoic\Utilities\LogFileOutputTypes;

global $Log, $Stoic;

$Log->addAppender(new LogFileAppender($Stoic->getFileHelper(), '~/logs/app.log', LogFileOutputTypes::JSON));
PHP)
			]),

		refPage('stoic/chain#ChainHelper',
			'An ordered list of nodes that a dispatch is sent through.',
			<<<'MD'
<<sample:minimal>>

## What traverse() checks

{sym:stoic/chain#ChainHelper.traverse} returns `false` without visiting anything when the chain has no nodes, the dispatch is not valid, or the dispatch is consumable and already consumed. Otherwise it calls {sym:stoic/chain#NodeBase.process} on each node in link order, passing `$sender` (the chain itself when you pass nothing) and the dispatch, and stops early if a node consumes a consumable dispatch. It returns `true` in that case even if no node did anything.

## Linking

{sym:stoic/chain#ChainHelper.linkNode} ignores a node whose {sym:stoic/chain#NodeBase.isValid} is false. In an event chain (`isEvent` true) linking replaces the single node. {sym:stoic/chain#ChainHelper.getNodeList} returns the keys and versions of what is linked, which is the quickest way to see whether a link was ignored.

## Debugging

With `doDebug` on, the chain describes every decision as a string and passes it to the callback registered with {sym:stoic/chain#ChainHelper.hookLogger}. The API front controller hooks its logger's `debug` method this way.
MD,
			['stoic/chain#ChainHelper.traverse', 'stoic/chain#NodeBase.process', 'stoic/chain#ChainHelper.linkNode', 'stoic/chain#NodeBase.isValid', 'stoic/chain#ChainHelper.getNodeList', 'stoic/chain#ChainHelper.hookLogger', 'stoic/chain#DispatchBase', 'stoic/chain#NodeBase'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Chain\ChainHelper;
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;

class GreetingDispatch extends DispatchBase {
	public string $name = '';

	public function initialize(mixed $input) : void {
		$this->name = (string) $input;
		$this->makeValid();
	}
}

class GreetNode extends NodeBase {
	public function __construct() {
		$this->setKey('GreetNode');
		$this->setVersion('1.0.0');
	}

	public function process(mixed $sender, DispatchBase &$dispatch) : void {
		if ($this->isDispatchOfType($dispatch, GreetingDispatch::class)) {
			$dispatch->setResult("Hello, {$dispatch->name}");
		}
	}
}

$chain = new ChainHelper();
$chain->linkNode(new GreetNode());

$dispatch = new GreetingDispatch();
$dispatch->initialize('Stoic');

$chain->traverse($dispatch);
echo($dispatch->getResults()[0]);   // Hello, Stoic
PHP)
			]),

		refPage('stoic/chain#DispatchBase',
			'Base class for the object a chain carries from node to node.',
			<<<'MD'
<<sample:minimal>>

## initialize() decides validity

A subclass implements {sym:stoic/chain#DispatchBase.initialize} and calls {sym:stoic/chain#DispatchBase.makeValid} once the input is acceptable. A dispatch that is never made valid is refused by {sym:stoic/chain#ChainHelper.traverse}. `makeValid()` also records the time, available from {sym:stoic/chain#DispatchBase.getCalledDateTime}.

Call {sym:stoic/chain#DispatchBase.makeConsumable} in `initialize()` when the first node that handles the dispatch should be the last, and {sym:stoic/chain#DispatchBase.makeStateful} when every node's result should be kept.

## Results

{sym:stoic/chain#DispatchBase.setResult} stores one value, replacing any previous value unless the dispatch is stateful. {sym:stoic/chain#DispatchBase.getResults} returns the list, or `null` when nothing was stored; {sym:stoic/chain#DispatchBase.numResults} avoids the null check.

## Consumption

{sym:stoic/chain#DispatchBase.consume} returns `true` the first time it is called on a consumable dispatch and `false` otherwise. Nodes use its return value when they need to know whether they were first.
MD,
			['stoic/chain#DispatchBase.initialize', 'stoic/chain#DispatchBase.makeValid', 'stoic/chain#ChainHelper.traverse', 'stoic/chain#DispatchBase.getCalledDateTime', 'stoic/chain#DispatchBase.makeConsumable', 'stoic/chain#DispatchBase.makeStateful', 'stoic/chain#DispatchBase.setResult', 'stoic/chain#DispatchBase.getResults', 'stoic/chain#DispatchBase.numResults', 'stoic/chain#DispatchBase.consume'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Chain\DispatchBase;

class PriceDispatch extends DispatchBase {
	public float $amount = 0.0;

	public function initialize(mixed $input) : void {
		if (!is_numeric($input) || $input < 0) {
			return;   // stays invalid; the chain will refuse it
		}

		$this->amount = floatval($input);
		$this->makeStateful();
		$this->makeValid();
	}
}
PHP)
			]),

		refPage('stoic/chain#NodeBase',
			'Base class for a handler in a chain, identified by a key and a version.',
			<<<'MD'
<<sample:minimal>>

## Key and version make a node valid

{sym:stoic/chain#NodeBase.isValid} requires both a non-empty key and a non-empty version, and {sym:stoic/chain#ChainHelper.linkNode} silently drops invalid nodes. Set them in the constructor with {sym:stoic/chain#NodeBase.setKey} and {sym:stoic/chain#NodeBase.setVersion}; the values are informational and appear in {sym:stoic/chain#ChainHelper.getNodeList} and debug output.

## process()

{sym:stoic/chain#NodeBase.process} receives whatever the caller passed to `traverse()` as `$sender` and the dispatch by reference. A node should check the dispatch type first with {sym:stoic/chain#NodeBase.isDispatchOfType} and return quietly when it does not apply, because a chain can carry several dispatch types over its lifetime.
MD,
			['stoic/chain#NodeBase.isValid', 'stoic/chain#ChainHelper.linkNode', 'stoic/chain#NodeBase.setKey', 'stoic/chain#NodeBase.setVersion', 'stoic/chain#ChainHelper.getNodeList', 'stoic/chain#NodeBase.process', 'stoic/chain#NodeBase.isDispatchOfType'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;

class TaxNode extends NodeBase {
	public function __construct(protected float $rate) {
		$this->setKey('TaxNode');
		$this->setVersion('1.0.0');
	}

	public function process(mixed $sender, DispatchBase &$dispatch) : void {
		if (!$this->isDispatchOfType($dispatch, PriceDispatch::class)) {
			return;
		}

		$dispatch->setResult(round($dispatch->amount * (1 + $this->rate), 2));
	}
}
PHP)
			]),

		refPage('stoic/log#Logger',
			'PSR-3 logger that buffers messages and sends them to appenders on output().',
			<<<'MD'
<<sample:minimal>>

## Messages buffer until output()

Every PSR-3 method (`debug()`, `info()`, `error()`, and the rest) creates a {sym:stoic/log#Message} and stores it. Nothing reaches an appender until {sym:stoic/log#Logger.output} runs, which filters by minimum level, wraps the survivors in a {sym:stoic/log#MessageDispatch}, traverses the appender chain, and clears the buffer. The site boot registers `output()` as a shutdown function; scripts that run for a long time should call it themselves.

## Minimum level

The constructor's first argument is a `Psr\Log\LogLevel` constant; anything below it is dropped at output time, not at log time. An invalid level throws `InvalidArgumentException`, as does logging with an unknown level.

## Placeholders

`{name}` in a message is replaced from the context array. Scalars and objects with `__toString()` are inserted directly, `null` becomes `null`, exceptions become their class, message, and stack trace, `DateTimeInterface` values become RFC 3339 strings, arrays are `print_r()`-ed, and other objects become `[object ClassName]`.

## Appenders

{sym:stoic/log#Logger.addAppender} links any {sym:stoic/log#AppenderBase}. The constructor's second argument accepts a list of them and ignores anything else in the list. {sym:stoic/utilities#LogFileAppender} and {sym:stoic/utilities#LogConsoleAppender} are the ones that ship; {sym:stoic/log#NullAppender} exists for tests.
MD,
			['stoic/log#Message', 'stoic/log#Logger.output', 'stoic/log#MessageDispatch', 'stoic/log#Logger.addAppender', 'stoic/log#AppenderBase', 'stoic/utilities#LogFileAppender', 'stoic/utilities#LogConsoleAppender', 'stoic/log#NullAppender', 'stoic/log#Logger.log'],
			[
				sample('minimal', <<<'PHP'
use Psr\Log\LogLevel;
use Stoic\Log\Logger;
use Stoic\Utilities\ConsoleHelper;
use Stoic\Utilities\LogConsoleAppender;

$log = new Logger(LogLevel::INFO, [new LogConsoleAppender(new ConsoleHelper($argv))]);

$log->debug("Not shown, below the minimum level");
$log->info("Imported {count} rows from {file}", ['count' => 120, 'file' => 'notes.csv']);

try {
	risky();
} catch (\Exception $ex) {
	$log->error("Import failed: {error}", ['error' => $ex]);
}

$log->output();
PHP)
			]),

		refPage('stoic/log#Message',
			'One log entry: level, text, and a UTC timestamp with microseconds.',
			<<<'MD'
<<sample:minimal>>

## Formats

{sym:stoic/log#Message.__toString} gives `timestamp LEVEL message` with the level padded to nine characters, which is what {sym:stoic/utilities#LogConsoleAppender} prints and what {sym:stoic/utilities#LogFileAppender} writes in plain mode. {sym:stoic/log#Message.jsonSerialize} and {sym:stoic/log#Message.__toJson} give an object with `level` (uppercased), `message`, and `timestamp`. {sym:stoic/log#Message.__toArray} keeps the level as logged.

## Validation

The constructor throws `InvalidArgumentException` for a level that is not one of the eight PSR-3 constants. The timestamp is taken at construction and cannot be changed.
MD,
			['stoic/log#Message.__toString', 'stoic/utilities#LogConsoleAppender', 'stoic/utilities#LogFileAppender', 'stoic/log#Message.jsonSerialize', 'stoic/log#Message.__toJson', 'stoic/log#Message.__toArray'],
			[
				sample('minimal', <<<'PHP'
use Psr\Log\LogLevel;
use Stoic\Log\Message;

$message = new Message(LogLevel::WARNING, "Disk is 91% full");

echo($message);              // 2026-09-30 14:02:11.532118 WARNING   Disk is 91% full
echo($message->__toJson());  // {"level":"WARNING","message":"Disk is 91% full","timestamp":"2026-09-30 14:02:11.532118"}
PHP)
			])
	];
