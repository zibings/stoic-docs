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
	 * A Learn-mode course: an ordered set of lessons.
	 *
	 * @package Zibings
	 */
	class DocCourse extends StoicDbModel {
		const string SQL_SELBYSLUG      = 'doccourse-selectbyslug';
		const string SQL_COUNTSLUGNOTID = 'doccourse-countslugnotid';


		/**
		 * Integer identifier of the course.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * URL slug, unique among courses.
		 *
		 * @var string
		 */
		public string $slug;
		/**
		 * Ordering weight among courses.
		 *
		 * @var int
		 */
		public int $sortOrder;
		/**
		 * One-line summary.
		 *
		 * @var string
		 */
		public string $summary;
		/**
		 * Course title.
		 *
		 * @var string
		 */
		public string $title;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Retrieves a course by its integer identifier. Returns a blank course if not found.
		 *
		 * @param int $id Integer identifier of the course.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocCourse
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocCourse {
			$ret = new DocCourse($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}

		/**
		 * Retrieves a course by its slug. Returns a blank course if not found.
		 *
		 * @param string $slug Course slug.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @return DocCourse
		 */
		public static function fromSlug(string $slug, PdoHelper $db, null|Logger $log = null) : DocCourse {
			$ret = new DocCourse($db, $log);

			if (empty($slug)) {
				return $ret;
			}

			$ret->tryPdoExcept(function () use (&$ret, $slug) {
				$stmt = $ret->db->prepareStored(self::SQL_SELBYSLUG);
				$stmt->bindValue(':slug', $slug);

				if ($stmt->execute()) {
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);

					if ($row !== false) {
						$ret = DocCourse::fromArray($row, $ret->db, $ret->log);
					}
				}

				return;
			}, "Failed to get course by slug");

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the course.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || empty($this->slug) || empty($this->title)) {
				$ret->addMessage("Cannot create a DocCourse with an id or without slug and title");

				return $ret;
			}

			$this->checkDuplicateSlug($ret, 0);

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the course.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the course.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the course.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || empty($this->slug) || empty($this->title)) {
				$ret->addMessage("Cannot update a DocCourse without id, slug, and title");

				return $ret;
			}

			$this->checkDuplicateSlug($ret, $this->id);

			return $ret;
		}

		/**
		 * Marks the ReturnHelper good unless another course already uses this slug.
		 *
		 * @param ReturnHelper $ret ReturnHelper to update.
		 * @param int $excludeId Identifier to exclude from the duplicate check.
		 * @return void
		 */
		protected function checkDuplicateSlug(ReturnHelper $ret, int $excludeId) : void {
			$this->tryPdoExcept(function () use (&$ret, $excludeId) {
				$stmt = $this->db->prepareStored(self::SQL_COUNTSLUGNOTID);
				$stmt->bindValue(':slug', $this->slug);
				$stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->fetch()[0] > 0) {
					$ret->addMessage("Found duplicate DocCourse by slug (Slug: {$this->slug})");
				} else {
					$ret->makeGood();
				}

				return;
			}, "Failed to check for duplicate courses");

			return;
		}

		/**
		 * Initializes a new DocCourse object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocCourse');

			$this->setColumn('id',        'ID',        BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('slug',      'Slug',      BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('title',     'Title',     BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('summary',   'Summary',   BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sortOrder', 'SortOrder', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			if (!static::$dbInitialized) {
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SELBYSLUG,      $this->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `Slug` = :slug");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTSLUGNOTID, "SELECT COUNT(*) FROM {$this->getDbTableName()} WHERE `Slug` = :slug AND `ID` <> :id");

				static::$dbInitialized = true;
			}

			$this->id        = 0;
			$this->slug      = '';
			$this->title     = '';
			$this->summary   = '';
			$this->sortOrder = 0;

			return;
		}
	}
