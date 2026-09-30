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
	 * Kinds of context option. The context bar offers one picker per kind.
	 *
	 * @package Zibings
	 */
	class DocContextOptionKinds {
		const string LANGUAGE        = 'language';
		const string PACKAGE_MANAGER = 'packageManager';


		/**
		 * Whether the value is a valid kind.
		 *
		 * @param string $kind Kind value to check.
		 * @return bool
		 */
		public static function isValid(string $kind) : bool {
			return $kind === self::LANGUAGE || $kind === self::PACKAGE_MANAGER;
		}
	}

	/**
	 * An option offered in the context bar (a language or a package manager). Code samples reference options by key,
	 * so the set of languages and package managers is data rather than code.
	 *
	 * @package Zibings
	 */
	class DocContextOption extends StoicDbModel {
		const string SQL_SELBYKEY       = 'doccontextoption-selectbykey';
		const string SQL_COUNTKEYNOTID  = 'doccontextoption-countkeynotid';


		/**
		 * Integer identifier of the option.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Whether this option is the default for its kind.
		 *
		 * @var bool
		 */
		public bool $isDefault;
		/**
		 * Kind of option, see DocContextOptionKinds.
		 *
		 * @var string
		 */
		public string $kind;
		/**
		 * Display label, e.g. `TypeScript`.
		 *
		 * @var string
		 */
		public string $label;
		/**
		 * Key used by code samples and client preferences, e.g. `ts`.
		 *
		 * @var string
		 */
		public string $optionKey;
		/**
		 * Ordering weight within the kind.
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
		 * Retrieves an option by its integer identifier. Returns a blank option if not found.
		 *
		 * @param int $id Integer identifier of the option.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocContextOption
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocContextOption {
			$ret = new DocContextOption($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}

		/**
		 * Retrieves an option by kind and key. Returns a blank option if not found.
		 *
		 * @param string $kind Option kind.
		 * @param string $key Option key.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocContextOption
		 */
		public static function fromKey(string $kind, string $key, PdoHelper $db, null|Logger $log = null) : DocContextOption {
			$ret = new DocContextOption($db, $log);

			if (empty($kind) || empty($key)) {
				return $ret;
			}

			$ret->tryPdoExcept(function () use (&$ret, $kind, $key) {
				$stmt = $ret->db->prepareStored(self::SQL_SELBYKEY);
				$stmt->bindValue(':kind', $kind);
				$stmt->bindValue(':key', $key);

				if ($stmt->execute()) {
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);

					if ($row !== false) {
						$ret = DocContextOption::fromArray($row, $ret->db, $ret->log);
					}
				}

				return;
			}, "Failed to get context option by key");

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the option.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || !DocContextOptionKinds::isValid($this->kind) || empty($this->optionKey) || empty($this->label)) {
				$ret->addMessage("Cannot create a DocContextOption with an id or without a valid kind, key, and label");

				return $ret;
			}

			$this->checkDuplicateKey($ret, 0);

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the option.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the option.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the option.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || !DocContextOptionKinds::isValid($this->kind) || empty($this->optionKey) || empty($this->label)) {
				$ret->addMessage("Cannot update a DocContextOption without id and a valid kind, key, and label");

				return $ret;
			}

			$this->checkDuplicateKey($ret, $this->id);

			return $ret;
		}

		/**
		 * Marks the ReturnHelper good unless another option of the same kind already uses this key.
		 *
		 * @param ReturnHelper $ret ReturnHelper to update.
		 * @param int $excludeId Identifier to exclude from the duplicate check.
		 * @return void
		 */
		protected function checkDuplicateKey(ReturnHelper $ret, int $excludeId) : void {
			$this->tryPdoExcept(function () use (&$ret, $excludeId) {
				$stmt = $this->db->prepareStored(self::SQL_COUNTKEYNOTID);
				$stmt->bindValue(':kind', $this->kind);
				$stmt->bindValue(':key', $this->optionKey);
				$stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->fetch()[0] > 0) {
					$ret->addMessage("Found duplicate DocContextOption by kind and key (Kind: {$this->kind}, Key: {$this->optionKey})");
				} else {
					$ret->makeGood();
				}

				return;
			}, "Failed to check for duplicate context options");

			return;
		}

		/**
		 * Initializes a new DocContextOption object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocContextOption');

			$this->setColumn('id',        'ID',        BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('kind',      'Kind',      BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('optionKey', 'OptionKey', BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('label',     'Label',     BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sortOrder', 'SortOrder', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('isDefault', 'IsDefault', BaseDbTypes::BOOLEAN, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			if (!static::$dbInitialized) {
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYKEY,      $this->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `Kind` = :kind AND `OptionKey` = :key");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTKEYNOTID, "SELECT COUNT(*) FROM {$this->getDbTableName()} WHERE `Kind` = :kind AND `OptionKey` = :key AND `ID` <> :id");

				static::$dbInitialized = true;
			}

			$this->id        = 0;
			$this->kind      = '';
			$this->optionKey = '';
			$this->label     = '';
			$this->sortOrder = 0;
			$this->isDefault = false;

			return;
		}
	}
