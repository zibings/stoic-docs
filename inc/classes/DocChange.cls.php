<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Kinds of change, in the order the upgrade view groups them.
	 *
	 * @package Zibings
	 */
	class DocChangeKinds {
		const string BREAKING   = 'breaking';
		const string BEHAVIOR   = 'behavior';
		const string DEPRECATED = 'deprecated';
		const string ADDED      = 'added';
		const string REMOVED    = 'removed';


		/**
		 * Returns all valid kinds in display order.
		 *
		 * @return string[]
		 */
		public static function all() : array {
			return [self::BREAKING, self::BEHAVIOR, self::DEPRECATED, self::ADDED, self::REMOVED];
		}

		/**
		 * Whether the value is a valid kind.
		 *
		 * @param string $kind Kind value to check.
		 * @return bool
		 */
		public static function isValid(string $kind) : bool {
			return in_array($kind, self::all(), true);
		}
	}

	/**
	 * A changelog entry for a version, with the reasoning, before/after code, and an optional codemod command.
	 *
	 * @package Zibings
	 */
	class DocChange extends StoicDbModel {
		/**
		 * Code showing the call after the change, if any.
		 *
		 * @var null|string
		 */
		public ?string $afterCode;
		/**
		 * Code showing the call before the change, if any.
		 *
		 * @var null|string
		 */
		public ?string $beforeCode;
		/**
		 * Command that migrates code automatically, if any.
		 *
		 * @var null|string
		 */
		public ?string $codemodCmd;
		/**
		 * Integer identifier of the change.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Kind of change, see DocChangeKinds.
		 *
		 * @var string
		 */
		public string $kind;
		/**
		 * URL of the RFC or discussion behind the change, if any.
		 *
		 * @var null|string
		 */
		public ?string $rfcUrl;
		/**
		 * Ordering weight within the version and kind.
		 *
		 * @var int
		 */
		public int $sortOrder;
		/**
		 * Short title of the change.
		 *
		 * @var string
		 */
		public string $title;
		/**
		 * Identifier of the version the change shipped in.
		 *
		 * @var int
		 */
		public int $versionId;
		/**
		 * Explanation of why the change was made.
		 *
		 * @var string
		 */
		public string $why;


		/**
		 * Retrieves a change by its integer identifier. Returns a blank change if not found.
		 *
		 * @param int $id Integer identifier of the change.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocChange
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocChange {
			$ret = new DocChange($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the change.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			return $this->id < 1 && $this->versionId > 0 && DocChangeKinds::isValid($this->kind) && !empty($this->title);
		}

		/**
		 * Determines if the system should attempt to delete the change.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the change.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the change.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			return $this->id > 0 && $this->versionId > 0 && DocChangeKinds::isValid($this->kind) && !empty($this->title);
		}

		/**
		 * Initializes a new DocChange object.
		 *
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocChange');

			$this->setColumn('id',         'ID',         BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('versionId',  'VersionID',  BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('kind',       'Kind',       BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('title',      'Title',      BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('why',        'Why',        BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('rfcUrl',     'RfcUrl',     BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('beforeCode', 'BeforeCode', BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('afterCode',  'AfterCode',  BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('codemodCmd', 'CodemodCmd', BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('sortOrder',  'SortOrder',  BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			$this->id         = 0;
			$this->versionId  = 0;
			$this->kind       = '';
			$this->title      = '';
			$this->why        = '';
			$this->rfcUrl     = null;
			$this->beforeCode = null;
			$this->afterCode  = null;
			$this->codemodCmd = null;
			$this->sortOrder  = 0;

			return;
		}
	}
