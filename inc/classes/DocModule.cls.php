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
	 * A logical grouping of symbols (namespace, package, file) identified by a path string such as `tessel/query`.
	 *
	 * @package Zibings
	 */
	class DocModule extends StoicDbModel {
		const string SQL_SELBYPATH      = 'docmodule-selectbypath';
		const string SQL_COUNTPATHNOTID = 'docmodule-countpathnotid';


		/**
		 * Integer identifier of the module.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Path string identifying the module, e.g. `tessel/query`.
		 *
		 * @var string
		 */
		public string $path;
		/**
		 * Ordering weight within the browse tree.
		 *
		 * @var int
		 */
		public int $sortOrder;
		/**
		 * One-line summary of the module.
		 *
		 * @var string
		 */
		public string $summary;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Retrieves a module by its integer identifier. Returns a blank module if not found.
		 *
		 * @param int $id Integer identifier of the module.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocModule
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocModule {
			$ret = new DocModule($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}

		/**
		 * Retrieves a module by its path. Returns a blank module if not found.
		 *
		 * @param string $path Module path.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocModule
		 */
		public static function fromPath(string $path, PdoHelper $db, null|Logger $log = null) : DocModule {
			$ret = new DocModule($db, $log);

			if (empty($path)) {
				return $ret;
			}

			$ret->tryPdoExcept(function () use (&$ret, $path) {
				$stmt = $ret->db->prepareStored(self::SQL_SELBYPATH);
				$stmt->bindValue(':path', $path);

				if ($stmt->execute()) {
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);

					if ($row !== false) {
						$ret = DocModule::fromArray($row, $ret->db, $ret->log);
					}
				}

				return;
			}, "Failed to get module by path");

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the module.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || empty($this->path)) {
				$ret->addMessage("Cannot create a DocModule with an id or without a path");

				return $ret;
			}

			$this->checkDuplicatePath($ret, 0);

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the module.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the module.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the module.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || empty($this->path)) {
				$ret->addMessage("Cannot update a DocModule without id and path");

				return $ret;
			}

			$this->checkDuplicatePath($ret, $this->id);

			return $ret;
		}

		/**
		 * Marks the ReturnHelper good unless another module already uses this path.
		 *
		 * @param ReturnHelper $ret ReturnHelper to update.
		 * @param int $excludeId Identifier to exclude from the duplicate check.
		 * @return void
		 */
		protected function checkDuplicatePath(ReturnHelper $ret, int $excludeId) : void {
			$this->tryPdoExcept(function () use (&$ret, $excludeId) {
				$stmt = $this->db->prepareStored(self::SQL_COUNTPATHNOTID);
				$stmt->bindValue(':path', $this->path);
				$stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->fetch()[0] > 0) {
					$ret->addMessage("Found duplicate DocModule by path (Path: {$this->path})");
				} else {
					$ret->makeGood();
				}

				return;
			}, "Failed to check for duplicate modules");

			return;
		}

		/**
		 * Initializes a new DocModule object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocModule');

			$this->setColumn('id',        'ID',        BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('path',      'Path',      BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('summary',   'Summary',   BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sortOrder', 'SortOrder', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			if (!static::$dbInitialized) {
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYPATH,      $this->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `Path` = :path");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTPATHNOTID, "SELECT COUNT(*) FROM {$this->getDbTableName()} WHERE `Path` = :path AND `ID` <> :id");

				static::$dbInitialized = true;
			}

			$this->id        = 0;
			$this->path      = '';
			$this->summary   = '';
			$this->sortOrder = 0;

			return;
		}
	}
