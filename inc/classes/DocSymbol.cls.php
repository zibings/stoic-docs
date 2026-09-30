<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Well-known symbol kinds. The Kind column is a free string so a library can introduce its own kinds; these are the
	 * ones the front-end has display treatment for.
	 *
	 * @package Zibings
	 */
	class DocSymbolKinds {
		const string FUNCTION_ = 'fn';
		const string CLASS_    = 'class';
		const string METHOD    = 'method';
		const string TYPE      = 'type';
		const string OPTION    = 'option';
		const string CONSTANT  = 'const';
		const string ERROR     = 'error';
	}

	/**
	 * A documented symbol. Symbols may nest (options of a type, methods of a class) via ParentSymbolID. The contract for
	 * a symbol at a given version lives in DocSymbolVersion.
	 *
	 * @package Zibings
	 */
	class DocSymbol extends StoicDbModel {
		const string SQL_SELBYNAME        = 'docsymbol-selectbyname';
		const string SQL_SELBYNAMEROOT    = 'docsymbol-selectbynameroot';
		const string SQL_COUNTNAMENOTID   = 'docsymbol-countnamenotid';
		const string SQL_COUNTNAMEROOTNID = 'docsymbol-countnamerootnotid';


		/**
		 * Integer identifier of the symbol.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Kind of symbol, see DocSymbolKinds for the well-known values.
		 *
		 * @var string
		 */
		public string $kind;
		/**
		 * Identifier of the module the symbol belongs to.
		 *
		 * @var int
		 */
		public int $moduleId;
		/**
		 * Symbol name, unique among its siblings.
		 *
		 * @var string
		 */
		public string $name;
		/**
		 * Identifier of the parent symbol, null for top-level symbols.
		 *
		 * @var null|int
		 */
		public ?int $parentSymbolId;
		/**
		 * Ordering weight among siblings.
		 *
		 * @var int
		 */
		public int $sortOrder;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Retrieves a symbol by its integer identifier. Returns a blank symbol if not found.
		 *
		 * @param int $id Integer identifier of the symbol.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocSymbol
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocSymbol {
			$ret = new DocSymbol($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}

		/**
		 * Retrieves a symbol by module, name, and optional parent. Returns a blank symbol if not found.
		 *
		 * @param int $moduleId Identifier of the module.
		 * @param string $name Symbol name.
		 * @param null|int $parentSymbolId Optional identifier of the parent symbol.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocSymbol
		 */
		public static function fromName(int $moduleId, string $name, ?int $parentSymbolId, PdoHelper $db, null|Logger $log = null) : DocSymbol {
			$ret = new DocSymbol($db, $log);

			if ($moduleId < 1 || empty($name)) {
				return $ret;
			}

			$ret->tryPdoExcept(function () use (&$ret, $moduleId, $name, $parentSymbolId) {
				if ($parentSymbolId === null) {
					$stmt = $ret->db->prepareStored(self::SQL_SELBYNAMEROOT);
				} else {
					$stmt = $ret->db->prepareStored(self::SQL_SELBYNAME);
					$stmt->bindValue(':parentId', $parentSymbolId, \PDO::PARAM_INT);
				}

				$stmt->bindValue(':moduleId', $moduleId, \PDO::PARAM_INT);
				$stmt->bindValue(':name', $name);

				if ($stmt->execute()) {
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);

					if ($row !== false) {
						$ret = DocSymbol::fromArray($row, $ret->db, $ret->log);
					}
				}

				return;
			}, "Failed to get symbol by name");

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the symbol.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || $this->moduleId < 1 || empty($this->name) || empty($this->kind)) {
				$ret->addMessage("Cannot create a DocSymbol with an id or without module, name, and kind");

				return $ret;
			}

			$this->checkDuplicateName($ret, 0);

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the symbol.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the symbol.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the symbol.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || $this->moduleId < 1 || empty($this->name) || empty($this->kind)) {
				$ret->addMessage("Cannot update a DocSymbol without id, module, name, and kind");

				return $ret;
			}

			$this->checkDuplicateName($ret, $this->id);

			return $ret;
		}

		/**
		 * Marks the ReturnHelper good unless a sibling symbol already uses this name.
		 *
		 * @param ReturnHelper $ret ReturnHelper to update.
		 * @param int $excludeId Identifier to exclude from the duplicate check.
		 * @return void
		 */
		protected function checkDuplicateName(ReturnHelper $ret, int $excludeId) : void {
			$this->tryPdoExcept(function () use (&$ret, $excludeId) {
				if ($this->parentSymbolId === null) {
					$stmt = $this->db->prepareStored(self::SQL_COUNTNAMEROOTNID);
				} else {
					$stmt = $this->db->prepareStored(self::SQL_COUNTNAMENOTID);
					$stmt->bindValue(':parentId', $this->parentSymbolId, \PDO::PARAM_INT);
				}

				$stmt->bindValue(':moduleId', $this->moduleId, \PDO::PARAM_INT);
				$stmt->bindValue(':name', $this->name);
				$stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->fetch()[0] > 0) {
					$ret->addMessage("Found duplicate DocSymbol by name among siblings (Name: {$this->name})");
				} else {
					$ret->makeGood();
				}

				return;
			}, "Failed to check for duplicate symbols");

			return;
		}

		/**
		 * Initializes a new DocSymbol object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocSymbol');

			$this->setColumn('id',             'ID',             BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('moduleId',       'ModuleID',       BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('parentSymbolId', 'ParentSymbolID', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('name',           'Name',           BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('kind',           'Kind',           BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sortOrder',      'SortOrder',      BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			if (!static::$dbInitialized) {
				$select = $this->generateClassQuery(BaseDbQueryTypes::SELECT, false);
				$table  = $this->getDbTableName();

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYNAME,        "{$select} WHERE `ModuleID` = :moduleId AND `ParentSymbolID` = :parentId AND `Name` = :name");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYNAMEROOT,    "{$select} WHERE `ModuleID` = :moduleId AND `ParentSymbolID` IS NULL AND `Name` = :name");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTNAMENOTID,   "SELECT COUNT(*) FROM {$table} WHERE `ModuleID` = :moduleId AND `ParentSymbolID` = :parentId AND `Name` = :name AND `ID` <> :id");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTNAMEROOTNID, "SELECT COUNT(*) FROM {$table} WHERE `ModuleID` = :moduleId AND `ParentSymbolID` IS NULL AND `Name` = :name AND `ID` <> :id");

				static::$dbInitialized = true;
			}

			$this->id             = 0;
			$this->moduleId       = 0;
			$this->parentSymbolId = null;
			$this->name           = '';
			$this->kind           = '';
			$this->sortOrder      = 0;

			return;
		}
	}
