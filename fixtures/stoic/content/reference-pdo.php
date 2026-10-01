<?php

	/**
	 * Reference prose for stoic/pdo.
	 */

	return [
		refPage('stoic/pdo#PdoHelper',
			'A PDO subclass that records queries and errors, never throws for an inactive connection, and serves stored queries per driver.',
			<<<'MD'
<<sample:minimal>>

## Inactive connections do not throw

If the connection fails in the constructor, the helper records the `PDOException` and marks itself inactive. Every PDO method then returns a harmless default (`false`, `0`, `''`, `null`, or an empty array) instead of throwing. Check {sym:stoic/pdo#PdoHelper.isActive} once after construction and read {sym:stoic/pdo#PdoHelper.getErrors} to see why a connection is down. Once active, errors behave as PDO's error mode dictates; the site boot switches connections to `ERRMODE_EXCEPTION`.

## History

{sym:stoic/pdo#PdoHelper.exec}, {sym:stoic/pdo#PdoHelper.prepare}, {sym:stoic/pdo#PdoHelper.query}, and the stored variants append a {sym:stoic/pdo#PdoQuery} to the list returned by {sym:stoic/pdo#PdoHelper.getQueries} and increment {sym:stoic/pdo#PdoHelper.getQueryCount}. A `PDOException` thrown by any of them is recorded as a {sym:stoic/pdo#PdoError} first and then rethrown. Statements executed directly on a `PDOStatement` are not seen.

## Stored queries

{sym:stoic/pdo#PdoHelper.storeQuery} and {sym:stoic/pdo#PdoHelper.storeQueries} are static: they register SQL under a key for one driver, with the parameter names and PDO types the SQL expects. {sym:stoic/pdo#PdoHelper.prepareStored} looks the key up for the connection's own driver, binds the arguments you pass, and returns the statement, or `null` if the key is unknown or the argument names do not match exactly. {sym:stoic/pdo#PdoHelper.queryStored} and {sym:stoic/pdo#PdoHelper.execStored} do the same for queries with no arguments. Storing a key twice for the same driver keeps the first and returns `false`.

Drivers are named by the {sym:stoic/pdo#PdoDrivers} constants or by the lowercase DSN prefix (`mysql`, `pgsql`, `sqlite`, `sqlsrv`). `stoic-migrate` keeps its per-driver `Migration` table SQL this way.

## Wrapping an existing PDO

Pass a `PDO` instance as the fifth constructor argument to wrap it; the DSN is ignored and the driver is read from the instance. {sym:stoic/pdo#StoicDbClass} does this when it is handed a plain `PDO`.
MD,
			['stoic/pdo#PdoHelper.isActive', 'stoic/pdo#PdoHelper.getErrors', 'stoic/pdo#PdoHelper.exec', 'stoic/pdo#PdoHelper.prepare', 'stoic/pdo#PdoHelper.query', 'stoic/pdo#PdoQuery', 'stoic/pdo#PdoHelper.getQueries', 'stoic/pdo#PdoHelper.getQueryCount', 'stoic/pdo#PdoError', 'stoic/pdo#PdoHelper.storeQuery', 'stoic/pdo#PdoHelper.storeQueries', 'stoic/pdo#PdoHelper.prepareStored', 'stoic/pdo#PdoHelper.queryStored', 'stoic/pdo#PdoHelper.execStored', 'stoic/pdo#PdoDrivers', 'stoic/pdo#StoicDbClass'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Pdo\PdoDrivers;
use Stoic\Pdo\PdoHelper;

PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, 'note-by-title', "SELECT * FROM `Note` WHERE `Title` = :title", [':title' => \PDO::PARAM_STR]);

$db = new PdoHelper('mysql:host=127.0.0.1;dbname=notes', 'notes', 'secret');

if (!$db->isActive()) {
	throw new \RuntimeException($db->getErrors()[0]->exception->getMessage());
}

$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepareStored('note-by-title', [':title' => 'Hello']);
$stmt->execute();

echo($db->getQueryCount());   // 1
PHP)
			]),

		refPage('stoic/pdo#BaseDbClass',
			'Base for anything that needs a PDO handle, a logger, and its own class name.',
			<<<'MD'
<<sample:minimal>>

## What subclasses get

`$this->db`, `$this->log` (a new {sym:stoic/log#Logger} when none is passed), and the class names from {sym:stoic/pdo#BaseDbModel.getClassName} and {sym:stoic/pdo#BaseDbModel.getShortClassName}. The constructor ends by calling {sym:stoic/pdo#BaseDbClass.__initialize}, the hook to override instead of the constructor.

## Helpers for the common shapes

{sym:stoic/pdo#BaseDbClass.tryPdoExcept} runs a closure, logs any `PDOException` at error level with your prefix, and returns `null` in that case. {sym:stoic/pdo#BaseDbClass.logReturnHelperMessages} writes a {sym:stoic/utilities#ReturnHelper}'s messages to the log, or the default text when it has none. Repositories that return lists of models are the typical subclass; the site template names them `*.rpo.php`.
MD,
			['stoic/log#Logger', 'stoic/pdo#BaseDbModel.getClassName', 'stoic/pdo#BaseDbModel.getShortClassName', 'stoic/pdo#BaseDbClass.__initialize', 'stoic/pdo#BaseDbClass.tryPdoExcept', 'stoic/pdo#BaseDbClass.logReturnHelperMessages', 'stoic/utilities#ReturnHelper', 'stoic/pdo#BaseDbModel.fromArray', 'stoic/pdo#StoicDbClass'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Pdo\StoicDbClass;

class Notes extends StoicDbClass {
	/** @return Note[] */
	public function newest(int $limit = 10) : array {
		return $this->tryPdoExcept(function () use ($limit) {
			$stmt = $this->db->prepare("SELECT `ID`, `Title`, `Body`, `Created` FROM `Note` ORDER BY `ID` DESC LIMIT :limit");
			$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
			$stmt->execute();

			$ret = [];

			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$ret[] = Note::fromArray($row, $this->db, $this->log);
			}

			return $ret;
		}, "Failed to list notes") ?? [];
	}
}
PHP)
			]),

		refPage('stoic/pdo#BaseDbModel',
			'Abstract model for one table row with generated create, read, update, and delete.',
			<<<'MD'
<<sample:minimal>>

## Declaring the model

Override {sym:stoic/pdo#BaseDbModel.__setupModel}, call {sym:stoic/pdo#BaseDbModel.setTableName}, then {sym:stoic/pdo#BaseDbModel.setColumn} for each property. Initialize default values there too; it runs from the constructor, after the connection and logger are set.

## The four statements

| Method | Uses | Needs |
|---|---|---|
| {sym:stoic/pdo#BaseDbModel.create} | `SHOULD_INSERT` columns | at least one insertable column |
| {sym:stoic/pdo#BaseDbModel.read} | all columns, `WHERE` on keys | key columns with values set |
| {sym:stoic/pdo#BaseDbModel.update} | `SHOULD_UPDATE` columns, `WHERE` on keys | key columns |
| {sym:stoic/pdo#BaseDbModel.delete} | `WHERE` on keys | key columns |

Each returns a {sym:stoic/utilities#ReturnHelper}. Before running, each calls its `__can*` guard; a `false` or a bad helper stops it, and a `PDOException` during execution becomes a message. `create()` copies `lastInsertId()` into the `AUTO_INCREMENT` column's property. `read()` on SQL Server trusts `execute()`; on every other driver it checks `rowCount()` and fails with a message when no row matched.

## Coercion

Values are converted on both sides of the database. `DATETIME` columns read back as `DateTimeImmutable` in UTC and write as `Y-m-d H:i:s`; `BOOLEAN` columns read as booleans and write as `1` or `0`; a property already holding an {sym:stoic/utilities#EnumBase} is rebuilt from an `INTEGER` column's value or a `STRING` column's name. For protected properties, {sym:stoic/pdo#BaseDbModel.__set} also wraps `STRING` values in a {sym:stoic/utilities#StringHelper} and {sym:stoic/pdo#BaseDbModel.__get} unwraps them; public properties bypass both.

## Serializing

{sym:stoic/pdo#BaseDbModel.toArray} returns registered properties as they are; {sym:stoic/pdo#BaseDbModel.toSerializableArray} formats dates and reduces enums to values, and is what `json_encode()` uses. Only registered columns are included.

## Reusing the SQL

{sym:stoic/pdo#BaseDbModel.generateClassQuery} returns the statement text for a {sym:stoic/pdo#BaseDbQueryTypes} value, with or without the key `WHERE` clause, so a repository can append its own conditions without repeating the column list.
MD,
			['stoic/pdo#BaseDbModel.__setupModel', 'stoic/pdo#BaseDbModel.setTableName', 'stoic/pdo#BaseDbModel.setColumn', 'stoic/pdo#BaseDbModel.create', 'stoic/pdo#BaseDbModel.read', 'stoic/pdo#BaseDbModel.update', 'stoic/pdo#BaseDbModel.delete', 'stoic/utilities#ReturnHelper', 'stoic/utilities#EnumBase', 'stoic/pdo#BaseDbModel.__set', 'stoic/utilities#StringHelper', 'stoic/pdo#BaseDbModel.__get', 'stoic/pdo#BaseDbModel.toArray', 'stoic/pdo#BaseDbModel.toSerializableArray', 'stoic/pdo#BaseDbModel.generateClassQuery', 'stoic/pdo#BaseDbQueryTypes', 'stoic/pdo#BaseDbModel.__canCreate', 'stoic/pdo#BaseDbColumnFlags', 'stoic/pdo#BaseDbTypes'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Pdo\BaseDbColumnFlags as BCF;
use Stoic\Pdo\BaseDbModel;
use Stoic\Pdo\BaseDbTypes;

class Tag extends BaseDbModel {
	public int $id = 0;
	public string $name = '';


	protected function __setupModel() : void {
		$this->setTableName('Tag');
		$this->setColumn('id',   'ID',   BaseDbTypes::INTEGER, BCF::IS_KEY | BCF::AUTO_INCREMENT);
		$this->setColumn('name', 'Name', BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

		return;
	}

	protected function __canCreate() : bool {
		return $this->id < 1 && trim($this->name) !== '';
	}
}

$tag       = new Tag($db);
$tag->name = 'php';

if ($tag->create()->isGood()) {
	echo($tag->id);
}
PHP)
			]),

		refPage('stoic/pdo#BaseDbModel.setColumn',
			'Register one property as a table column with its type and flags.',
			<<<'MD'
<<sample:minimal>>

## Flags or booleans

The fourth argument is either a composite of {sym:stoic/pdo#BaseDbColumnFlags} values or the `isKey` boolean followed by `shouldInsert`, `shouldUpdate`, `allowsNulls`, and `autoIncrement`. When an integer is given, the boolean arguments are ignored.

## What the flags mean

- `IS_KEY`: part of the `WHERE` clause for read, update, and delete.
- `SHOULD_INSERT`: included in `create()`. Leave it off for auto-increment keys and database defaults.
- `SHOULD_UPDATE`: included in `update()`. Leave it off for creation timestamps.
- `ALLOWS_NULLS`: a null property binds as `PDO::PARAM_NULL` instead of an empty value.
- `AUTO_INCREMENT`: receives `lastInsertId()` after `create()`. One column at most.

## Errors

Registering a property twice throws `InvalidArgumentException`, as does an empty column name or a type that is not a {sym:stoic/pdo#BaseDbTypes} constant. The property itself does not have to exist on the class, but reads and writes will create it dynamically if it does not, which PHP 8.2 and later deprecate; declare it.
MD,
			['stoic/pdo#BaseDbColumnFlags', 'stoic/pdo#BaseDbTypes', 'stoic/pdo#BaseDbModel.__setupModel', 'stoic/pdo#BaseDbModel.create', 'stoic/pdo#BaseDbModel.update', 'stoic/pdo#BaseDbField'],
			[
				sample('minimal', <<<'PHP'
use Stoic\Pdo\BaseDbColumnFlags as BCF;
use Stoic\Pdo\BaseDbTypes;

protected function __setupModel() : void {
	$this->setTableName('User');
	$this->setColumn('id',       'ID',       BaseDbTypes::INTEGER,  BCF::IS_KEY | BCF::AUTO_INCREMENT);
	$this->setColumn('email',    'Email',    BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
	$this->setColumn('active',   'Active',   BaseDbTypes::BOOLEAN,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
	$this->setColumn('joined',   'Joined',   BaseDbTypes::DATETIME, BCF::SHOULD_INSERT);
	$this->setColumn('lastSeen', 'LastSeen', BaseDbTypes::DATETIME, BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);

	return;
}
PHP)
			]),

		refPage('stoic/pdo#BaseDbModel.fromArray',
			'Build a model from an associative array, matching keys to columns or properties.',
			<<<'MD'
<<sample:minimal>>

## Matching rules

Each key is compared case-insensitively first against the registered column names, then against the class's property names. A column match goes through the same coercion as `read()`; a property match assigns the raw value. A key that matches neither throws {sym:stoic/pdo#ClassPropertyNotFoundException}.

## The count must match

The array must have exactly as many entries as the subclass has properties beyond those inherited from `BaseDbModel`, minus any names in `$exclusions`. Otherwise `InvalidArgumentException` is thrown before anything is assigned. Select exactly the columns the model declares, or list the extra properties as exclusions.
MD,
			['stoic/pdo#ClassPropertyNotFoundException', 'stoic/pdo#BaseDbModel.read', 'stoic/pdo#BaseDbClass'],
			[
				sample('minimal', <<<'PHP'
$stmt = $db->query("SELECT `ID`, `Title`, `Body`, `Created` FROM `Note` WHERE `Created` > '2026-01-01'");

while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
	$note = Note::fromArray($row, $db, $log);
}
PHP)
			]),

		refPage('stoic/pdo#StoicDbModel',
			'BaseDbModel that guarantees `$this->db` is a PdoHelper.',
			<<<'MD'
Extend this instead of {sym:stoic/pdo#BaseDbModel} in an application built on `stoic/web`. Its {sym:stoic/pdo#BaseDbClass.__initialize} wraps a plain `PDO` in a {sym:stoic/pdo#PdoHelper}, so model code can rely on the helper's driver detection, query history, and stored queries. When the connection is already a `PdoHelper`, which is what the site boot provides, it is used as is.

{sym:stoic/pdo#StoicDbClass} is the same guarantee for non-model classes such as repositories and API controllers.
MD,
			['stoic/pdo#BaseDbModel', 'stoic/pdo#BaseDbClass.__initialize', 'stoic/pdo#PdoHelper', 'stoic/pdo#StoicDbClass']),

		refPage('stoic/pdo#BaseDbTypes',
			'Column types a model can declare, each mapped to a PDO parameter type.',
			<<<'MD'
| Constant | Binds as | Read back as |
|---|---|---|
| `INTEGER` | `PDO::PARAM_INT` | the driver's value, or an enum rebuilt by value |
| `STRING` | `PDO::PARAM_STR` | string, or an enum rebuilt by name |
| `BOOLEAN` | `PDO::PARAM_BOOL` | `bool` |
| `NILL` | `PDO::PARAM_NULL` | as is |
| `DATETIME` | `PDO::PARAM_STR`, formatted `Y-m-d H:i:s` | `DateTimeImmutable` in UTC |

{sym:stoic/pdo#BaseDbTypes.getDbType} performs the mapping and returns `null` for a blank instance. {sym:stoic/pdo#BaseDbModel.setColumn} rejects a value that is not one of these.
MD,
			['stoic/pdo#BaseDbTypes.getDbType', 'stoic/pdo#BaseDbModel.setColumn'])
	];
