<?php

	return [
		page('explain', 'return-helper-instead-of-false', 'Why methods return a ReturnHelper instead of false',
			'A result object that starts out bad, carries messages, and only counts as success when you say so.',
			<<<'MD'
A function that returns `false` on failure tells you one bit. It cannot say whether the input was missing, the row already existed, or the database was down. Exceptions carry that detail, but they also unwind the stack and force a `try` around every call that can fail in an ordinary way. Stoic's answer is {sym:stoic/utilities#ReturnHelper}: a small object with a status, a list of messages, and a list of results.

## Bad until proven good

A new helper is bad. Nothing you add to it changes that; only {sym:stoic/utilities#ReturnHelper.makeGood} flips the status. So a method that forgets to mark success fails closed, and a caller who checks {sym:stoic/utilities#ReturnHelper.isGood} never mistakes "returned early" for "worked". The cost is that every success path must call `makeGood()`, which is easy to forget the first few times.

```php
function rename(Note $note, string $title) : ReturnHelper {
	$ret = new ReturnHelper();

	if (trim($title) === '') {
		$ret->addMessage("Title cannot be empty");

		return $ret;
	}

	$note->title = $title;
	$ret->addResult($note->update());
	$ret->makeGood();

	return $ret;
}
```

## Messages are for humans, results are for code

Messages explain a status. Results carry whatever the caller needs next: an identifier, a model, a nested helper. Both are arrays, even when there is one of them, so {sym:stoic/utilities#ReturnHelper.getResults} always needs an index. That is deliberate: a helper can accumulate several results from several steps without changing shape.

## Where the framework hands you one

Every generated statement on {sym:stoic/pdo#BaseDbModel} returns one: {sym:stoic/pdo#BaseDbModel.create}, {sym:stoic/pdo#BaseDbModel.read}, {sym:stoic/pdo#BaseDbModel.update}, and {sym:stoic/pdo#BaseDbModel.delete}. The `__can*` guards on a model may return one instead of a boolean, and their messages flow into the outer result and the log. {sym:stoic/utilities#ConsoleHelper.getQueriedInput} returns one so an interactive prompt can report why input was rejected. {sym:stoic/pdo#BaseDbClass.logReturnHelperMessages} exists because unrolling the messages into the logger is the most common thing to do with a bad result.

## The tradeoff

You give up types on results and you give up the automatic propagation that exceptions provide. You get failure paths that read top to bottom and results that can explain themselves. Stoic still throws for programmer errors, such as an invalid log level or a column registered twice; the helper is for outcomes a caller is expected to handle.
MD,
			['stoic/utilities#ReturnHelper', 'stoic/utilities#ReturnHelper.makeGood', 'stoic/utilities#ReturnHelper.isGood', 'stoic/utilities#ReturnHelper.getResults', 'stoic/pdo#BaseDbModel', 'stoic/pdo#BaseDbModel.create', 'stoic/pdo#BaseDbModel.read', 'stoic/pdo#BaseDbModel.update', 'stoic/pdo#BaseDbModel.delete', 'stoic/utilities#ConsoleHelper.getQueriedInput', 'stoic/pdo#BaseDbClass.logReturnHelperMessages'],
			[], 4),

		page('explain', 'chains-dispatches-nodes', 'Chains, dispatches, and nodes',
			'The one event mechanism in Stoic, and why the logger and the API authorizer are both built on it.',
			<<<'MD'
A {sym:stoic/chain#ChainHelper} is an ordered list of {sym:stoic/chain#NodeBase} objects. You send a {sym:stoic/chain#DispatchBase} through it with {sym:stoic/chain#ChainHelper.traverse}, and each node's {sym:stoic/chain#NodeBase.process} sees the same dispatch in link order. That is the whole model. Everything else is a rule about when the walk stops.

## Two gates before anything runs

A chain refuses a dispatch that is not valid, and refuses to link a node that is not valid. A dispatch becomes valid when its own {sym:stoic/chain#DispatchBase.initialize} calls {sym:stoic/chain#DispatchBase.makeValid}; a node becomes valid when it has both a key and a version. Both gates fail quietly: `traverse()` returns `false` and `linkNode()` returns the chain unchanged. Turn on debug output with {sym:stoic/chain#ChainHelper.toggleDebug} and {sym:stoic/chain#ChainHelper.hookLogger} when a chain seems to do nothing.

## Consumable and stateful

Two flags on the dispatch decide how the walk behaves. A **consumable** dispatch stops the walk as soon as a node calls {sym:stoic/chain#DispatchBase.consume}, and a consumed dispatch cannot be traversed again. A **stateful** dispatch keeps every value passed to {sym:stoic/chain#DispatchBase.setResult}; otherwise the last one wins. Neither flag is set by default, so a plain dispatch visits every node and remembers one result.

An **event chain** is a chain built with `isEvent` true. It holds one node, and linking another replaces it. Use it when exactly one handler should own an event.

## Results travel in the dispatch

`traverse()` returns only whether the walk happened. Anything a node wants to say goes into the dispatch, and the caller reads it back with {sym:stoic/chain#DispatchBase.getResults}. This keeps nodes independent of each other and of the caller; a node knows the dispatch type it handles and nothing else. {sym:stoic/chain#NodeBase.isDispatchOfType} is the idiomatic first line of a `process()` method.

## Where the framework uses it

{sym:stoic/log#Logger} is a chain of appenders. When it outputs, it wraps the buffered messages in a {sym:stoic/log#MessageDispatch} and traverses; each appender writes them somewhere. {sym:stoic/web/api#Stoic} keeps an authorization chain. For an endpoint that requires authorization it traverses an {sym:stoic/web/resources#ApiAuthorizationDispatch}, and a node that recognizes the request calls {sym:stoic/web/resources#ApiAuthorizationDispatch.authorize}. If nothing is linked, the traverse fails and the request gets a 403.

## The tradeoff

There are no priorities, no return values from nodes, and no way to short-circuit except consumption. In exchange a chain is a plain array walk you can read in one screen, and a node is a class with one method. When ordering matters, link in that order.
MD,
			['stoic/chain#ChainHelper', 'stoic/chain#NodeBase', 'stoic/chain#DispatchBase', 'stoic/chain#ChainHelper.traverse', 'stoic/chain#NodeBase.process', 'stoic/chain#DispatchBase.initialize', 'stoic/chain#DispatchBase.makeValid', 'stoic/chain#ChainHelper.toggleDebug', 'stoic/chain#ChainHelper.hookLogger', 'stoic/chain#DispatchBase.consume', 'stoic/chain#DispatchBase.setResult', 'stoic/chain#DispatchBase.getResults', 'stoic/chain#NodeBase.isDispatchOfType', 'stoic/log#Logger', 'stoic/log#MessageDispatch', 'stoic/web/api#Stoic', 'stoic/web/resources#ApiAuthorizationDispatch', 'stoic/web/resources#ApiAuthorizationDispatch.authorize'],
			[], 5),

		page('explain', 'how-a-request-boots', 'How a request boots: the Stoic singleton and load order',
			'What happens between requiring inc/core.php and your first line of page code.',
			<<<'MD'
A Stoic page is a plain PHP file. It defines `STOIC_CORE_PATH`, requires `inc/core.php`, and then does its work. The interesting part is what `core.php` sets in motion when it calls {sym:stoic/web#Stoic.getInstance}.

## The constructor, in order

1. A {sym:stoic/web#Request} is built from the superglobals, or from the {sym:stoic/web/resources#PageVariables} you passed in. An unknown request method throws {sym:stoic/web/resources#InvalidRequestException} here, before anything else runs.
2. A {sym:stoic/utilities#FileHelper} is rooted at the core path, so `~/` means the project root from now on.
3. `~/siteSettings.json` is read into the config container. A missing file leaves every setting at its default.
4. Every `*.cls.php` in `inc/classes` is required, then every `*.rpo.php` in `inc/repositories`. Paths and extensions come from the settings named in {sym:stoic/web/resources#SettingsStrings}.
5. A shutdown function is registered that calls {sym:stoic/log#Logger.output}, so buffered log messages are written after the page finishes.
6. Database connections open. Every `dbDsns.<key>` setting becomes a {sym:stoic/pdo#PdoHelper} registered under that key; a legacy `dbDsn` setting becomes the `default` connection. Connections that fail are dropped silently, and connections that succeed switch to `PDO::ERRMODE_EXCEPTION` unless `STOIC_DISABLE_DB_EXCEPTIONS` is true. `STOIC_DISABLE_DATABASE` skips this step.
7. The session starts unless `STOIC_DISABLE_SESSION` is true or output has already begun, and `$_SESSION` is wrapped in a {sym:stoic/utilities#ParameterHelper}.
8. Every `*.utl.php` in `inc/utilities` is required.

## What the order means for your code

Class and repository files load before the database exists, so they should declare classes and nothing else. Utility files load last, after the database and session, so they can safely run code at file scope: the generated `core.php` relies on this when it exposes `$Db`, `$Log`, `$Session`, and `$Settings` as globals.

Files load once per process. {sym:stoic/utilities#FileHelper.load} remembers what it has required, so a second `Stoic::getInstance()` with the same core path reuses the classes without re-including them.

## One class, one instance stack

`getInstance()` returns the most recent instance for the *called class*. {sym:stoic/web/api#Stoic} is a subclass, so a page and an API front controller in the same process hold separate instances with separate settings objects. The first call must pass a core path; later calls without arguments return the existing instance, and a call that passes a core path, page variables, and a logger together pushes a new instance onto the stack.

## Constants the boot reads

| Constant | Effect when true |
|---|---|
| `STOIC_DISABLE_DATABASE` | Skip opening connections. |
| `STOIC_DISABLE_DB_EXCEPTIONS` | Leave PDO in its default silent error mode. |
| `STOIC_DISABLE_SESSION` | Do not start a session or wrap `$_SESSION`. |
| `STOIC_ENABLE_DEBUG` | The generated `core.php` turns on `display_errors`. |

Define them before requiring `core.php`; the generated file only defines the ones you have not.

## The tradeoff

There is no container and no lazy loading. Every class in `inc/classes` is parsed on every request whether the page uses it or not, and wiring happens at file scope where you can see it. For the size of application Stoic targets, that is a fair trade for being able to answer "where does `$Db` come from" by reading one file.
MD,
			['stoic/web#Stoic.getInstance', 'stoic/web#Request', 'stoic/web/resources#PageVariables', 'stoic/web/resources#InvalidRequestException', 'stoic/utilities#FileHelper', 'stoic/web/resources#SettingsStrings', 'stoic/log#Logger.output', 'stoic/pdo#PdoHelper', 'stoic/utilities#ParameterHelper', 'stoic/utilities#FileHelper.load', 'stoic/web/api#Stoic'],
			[], 6),

		page('explain', 'class-based-enums', 'Class-based enums: what EnumBase gives you that constants do not',
			'Why Stoic enums are objects with a name, a value, and a blank state.',
			<<<'MD'
{sym:stoic/utilities#EnumBase} predates native PHP enums, and Stoic keeps it because its instances do things native cases cannot: they can be blank, they carry both a name and an integer value, and they decide for themselves how to serialize.

## A subclass is a list of integer constants

```php
class Priority extends EnumBase {
	const int LOW    = 1;
	const int NORMAL = 2;
	const int HIGH   = 3;
}
```

The constant list is read once per class through reflection and cached. Constructing `new Priority(2)` yields an instance whose {sym:stoic/utilities#EnumBase.getName} is `NORMAL` and whose {sym:stoic/utilities#EnumBase.getValue} is `2`.

## Invalid input gives a blank instance

`new Priority(9)` does not throw. It returns an instance with a null name and a null value, and {sym:stoic/utilities#EnumBase.is} returns false for everything. The same holds for {sym:stoic/utilities#EnumBase.fromString} with an unknown name and for {sym:stoic/utilities#EnumBase.tryGet} with anything it cannot use. This is the "unset" state: a model field that has not been read from the database yet, a request type that could not be parsed. Check {sym:stoic/utilities#EnumBase.validValue} first when a bad value should be an error.

## Comparing

Two instances are objects, so `==` compares properties and `===` compares identity. Neither is what you mean. Use `is()` and {sym:stoic/utilities#EnumBase.isIn} with the constants, or compare `getValue()` results.

## Serialization

By default an enum prints and JSON-encodes as its **name**. Pass `false` as the second constructor argument to serialize as the value instead. {sym:stoic/pdo#BaseDbModel} uses the two forms directly: an enum property registered as an `INTEGER` column is stored by value, and one registered as a `STRING` column is stored by name.

## The tradeoff

Reflection has a cost, paid once per class. Values must be integers. In exchange you get a type that can be nullable without being `null`, that survives a round trip through a database column, and that validates input from the outside world in one call.
MD,
			['stoic/utilities#EnumBase', 'stoic/utilities#EnumBase.getName', 'stoic/utilities#EnumBase.getValue', 'stoic/utilities#EnumBase.is', 'stoic/utilities#EnumBase.fromString', 'stoic/utilities#EnumBase.tryGet', 'stoic/utilities#EnumBase.validValue', 'stoic/utilities#EnumBase.isIn', 'stoic/pdo#BaseDbModel'],
			[], 4),

		page('explain', 'what-basedbmodel-does', 'What BaseDbModel does, and what it leaves to you',
			'A model is one table row with generated statements for it. Everything else is plain SQL.',
			<<<'MD'
{sym:stoic/pdo#BaseDbModel} is not an ORM in the sense of relations, lazy collections, and query builders. It is a class that knows one table and its columns, and can generate the four single-row statements for them.

## What you declare

A model subclass overrides {sym:stoic/pdo#BaseDbModel.__setupModel} and, inside it, calls {sym:stoic/pdo#BaseDbModel.setTableName} once and {sym:stoic/pdo#BaseDbModel.setColumn} once per property. Each column records the property name, the column name, a {sym:stoic/pdo#BaseDbTypes} value, and flags: whether it is part of the key, whether it is inserted, whether it is updated, whether it accepts null, and whether the database assigns it on insert.

## What is generated

{sym:stoic/pdo#BaseDbModel.create} inserts every `SHOULD_INSERT` column and copies `lastInsertId()` into the `AUTO_INCREMENT` property. {sym:stoic/pdo#BaseDbModel.read} selects every column where every key column equals the property's current value. {sym:stoic/pdo#BaseDbModel.update} sets every `SHOULD_UPDATE` column by key, and {sym:stoic/pdo#BaseDbModel.delete} deletes by key. Values bind with the PDO type the column type maps to, and identifiers are quoted the way the connection's driver expects. {sym:stoic/pdo#BaseDbModel.generateClassQuery} returns the same SQL as text so a repository can extend it with its own `WHERE` clause.

Each of the four returns a {sym:stoic/utilities#ReturnHelper}. A `PDOException` becomes a message on it, not a thrown error.

## Guards run first

Before a statement runs, the matching `__can*` method decides whether it may. {sym:stoic/pdo#BaseDbModel.__canCreate} is where a model checks for duplicates or fills a timestamp; {sym:stoic/pdo#BaseDbModel.__canUpdate} is where it refuses to update a row without an identifier. Return `false` for a plain refusal, or a bad `ReturnHelper` whose messages should reach the caller.

## Values are coerced on the way in and out

When a row is read, `DATETIME` columns become `DateTimeImmutable` objects in UTC, `BOOLEAN` columns become booleans, and properties that already hold an {sym:stoic/utilities#EnumBase} are rebuilt from the stored value or name. When a value is written, the reverse happens. Properties that are protected go through {sym:stoic/pdo#BaseDbModel.__set} and {sym:stoic/pdo#BaseDbModel.__get}, which also wrap `STRING` columns in a {sym:stoic/utilities#StringHelper}; public typed properties skip the magic methods and hold plain values.

## What is left to you

Lists, joins, searches, and pagination are prepared statements you write, typically in a repository class that returns models built with {sym:stoic/pdo#BaseDbModel.fromArray}. Migrations are SQL files run by `stoic-migrate`. Transactions are {sym:stoic/pdo#PdoHelper.beginTransaction} and friends. The framework's own site template follows this split: `*.cls.php` files hold models, `*.rpo.php` files hold the queries that find them.

## The tradeoff

You write more SQL than with a full ORM and you never wonder what SQL ran. The generated statements are logged with their parameters at `info` level, so a failing model explains itself in the log.
MD,
			['stoic/pdo#BaseDbModel', 'stoic/pdo#BaseDbModel.__setupModel', 'stoic/pdo#BaseDbModel.setTableName', 'stoic/pdo#BaseDbModel.setColumn', 'stoic/pdo#BaseDbTypes', 'stoic/pdo#BaseDbModel.create', 'stoic/pdo#BaseDbModel.read', 'stoic/pdo#BaseDbModel.update', 'stoic/pdo#BaseDbModel.delete', 'stoic/pdo#BaseDbModel.generateClassQuery', 'stoic/utilities#ReturnHelper', 'stoic/pdo#BaseDbModel.__canCreate', 'stoic/pdo#BaseDbModel.__canUpdate', 'stoic/utilities#EnumBase', 'stoic/pdo#BaseDbModel.__set', 'stoic/pdo#BaseDbModel.__get', 'stoic/utilities#StringHelper', 'stoic/pdo#BaseDbModel.fromArray', 'stoic/pdo#PdoHelper.beginTransaction'],
			[], 5)
	];
