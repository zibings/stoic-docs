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
	 * A released version of the documented library. SortKey orders versions without assuming any version scheme.
	 *
	 * @package Zibings
	 */
	class DocVersion extends StoicDbModel {
		const string SQL_SELBYLABEL      = 'docversion-selectbylabel';
		const string SQL_SELBYTAG        = 'docversion-selectbytag';
		const string SQL_SELBYSORTKEY    = 'docversion-selectbysortkey';
		const string SQL_COUNTDUPNOTID   = 'docversion-countduplicatesnotid';


		/**
		 * Integer identifier of the version.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Whether this is the version the `latest` alias resolves to.
		 *
		 * @var bool
		 */
		public bool $isLatest;
		/**
		 * Whether this version still receives support (unsupported versions remain browsable).
		 *
		 * @var bool
		 */
		public bool $isSupported;
		/**
		 * Short human label used in URLs and the context bar, e.g. `v4.2`.
		 *
		 * @var string
		 */
		public string $label;
		/**
		 * Date and time the version was released, if known.
		 *
		 * @var null|\DateTimeInterface
		 */
		public ?\DateTimeInterface $releasedAt;
		/**
		 * Integer used to order versions; higher is newer.
		 *
		 * @var int
		 */
		public int $sortKey;
		/**
		 * Exact version tag as the library publishes it, e.g. `4.2.0`.
		 *
		 * @var string
		 */
		public string $tag;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Retrieves a version by its integer identifier. Returns a blank version if not found.
		 *
		 * @param int $id Integer identifier of the version.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocVersion
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocVersion {
			$ret = new DocVersion($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}

		/**
		 * Retrieves a version by its label. Returns a blank version if not found.
		 *
		 * @param string $label Label value, e.g. `v4.2`.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocVersion
		 */
		public static function fromLabel(string $label, PdoHelper $db, null|Logger $log = null) : DocVersion {
			return static::fromStoredLookup(self::SQL_SELBYLABEL, ':label', $label, $db, $log);
		}

		/**
		 * Retrieves a version by its tag. Returns a blank version if not found.
		 *
		 * @param string $tag Tag value, e.g. `4.2.0`.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocVersion
		 */
		public static function fromTag(string $tag, PdoHelper $db, null|Logger $log = null) : DocVersion {
			return static::fromStoredLookup(self::SQL_SELBYTAG, ':tag', $tag, $db, $log);
		}

		/**
		 * Runs a single-parameter stored lookup and hydrates the result.
		 *
		 * @param string $query Stored query key.
		 * @param string $param Parameter placeholder name.
		 * @param string $value Parameter value.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocVersion
		 */
		protected static function fromStoredLookup(string $query, string $param, string $value, PdoHelper $db, null|Logger $log = null) : DocVersion {
			$ret = new DocVersion($db, $log);

			if (empty($value)) {
				return $ret;
			}

			$ret->tryPdoExcept(function () use (&$ret, $query, $param, $value) {
				$stmt = $ret->db->prepareStored($query);
				$stmt->bindValue($param, $value);

				if ($stmt->execute()) {
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);

					if ($row !== false) {
						$ret = DocVersion::fromArray($row, $ret->db, $ret->log);
					}
				}

				return;
			}, "Failed to look up version");

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the version.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || empty($this->tag) || empty($this->label)) {
				$ret->addMessage("Cannot create a DocVersion with an id or without tag and label");

				return $ret;
			}

			$this->checkDuplicates($ret, 0);

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the version.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the version.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the version.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || empty($this->tag) || empty($this->label)) {
				$ret->addMessage("Cannot update a DocVersion without id, tag, and label");

				return $ret;
			}

			$this->checkDuplicates($ret, $this->id);

			return $ret;
		}

		/**
		 * Marks the ReturnHelper good unless another version already uses this tag, label, or sort key.
		 *
		 * @param ReturnHelper $ret ReturnHelper to update.
		 * @param int $excludeId Identifier to exclude from the duplicate check.
		 * @return void
		 */
		protected function checkDuplicates(ReturnHelper $ret, int $excludeId) : void {
			$this->tryPdoExcept(function () use (&$ret, $excludeId) {
				$stmt = $this->db->prepareStored(self::SQL_COUNTDUPNOTID);
				$stmt->bindValue(':tag', $this->tag);
				$stmt->bindValue(':label', $this->label);
				$stmt->bindValue(':sortKey', $this->sortKey, \PDO::PARAM_INT);
				$stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->fetch()[0] > 0) {
					$ret->addMessage("Found duplicate DocVersion by tag, label, or sort key (Tag: {$this->tag}, Label: {$this->label}, SortKey: {$this->sortKey})");
				} else {
					$ret->makeGood();
				}

				return;
			}, "Failed to check for duplicate versions");

			return;
		}

		/**
		 * Initializes a new DocVersion object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocVersion');

			$this->setColumn('id',          'ID',          BaseDbTypes::INTEGER,  BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('tag',         'Tag',         BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('label',       'Label',       BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sortKey',     'SortKey',     BaseDbTypes::INTEGER,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('releasedAt',  'ReleasedAt',  BaseDbTypes::DATETIME, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('isLatest',    'IsLatest',    BaseDbTypes::BOOLEAN,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('isSupported', 'IsSupported', BaseDbTypes::BOOLEAN,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			if (!static::$dbInitialized) {
				$select = $this->generateClassQuery(BaseDbQueryTypes::SELECT, false);

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYLABEL,    "{$select} WHERE `Label` = :label");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYTAG,      "{$select} WHERE `Tag` = :tag");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYSORTKEY,  "{$select} WHERE `SortKey` = :sortKey");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTDUPNOTID, "SELECT COUNT(*) FROM {$this->getDbTableName()} WHERE (`Tag` = :tag OR `Label` = :label OR `SortKey` = :sortKey) AND `ID` <> :id");

				static::$dbInitialized = true;
			}

			$this->id          = 0;
			$this->tag         = '';
			$this->label       = '';
			$this->sortKey     = 0;
			$this->releasedAt  = null;
			$this->isLatest    = false;
			$this->isSupported = true;

			return;
		}
	}
