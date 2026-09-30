<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * A lesson in a course: one learn-mode page at a position in the course spine.
	 *
	 * @package Zibings
	 */
	class DocLesson extends StoicDbModel {
		/**
		 * Identifier of the course.
		 *
		 * @var int
		 */
		public int $courseId;
		/**
		 * Integer identifier of the lesson.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Position within the course, starting at 1.
		 *
		 * @var int
		 */
		public int $ordinal;
		/**
		 * Identifier of the learn-mode page holding the lesson prose.
		 *
		 * @var int
		 */
		public int $pageId;


		/**
		 * Retrieves a lesson by its integer identifier. Returns a blank lesson if not found.
		 *
		 * @param int $id Integer identifier of the lesson.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocLesson
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocLesson {
			$ret = new DocLesson($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the lesson.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			return $this->id < 1 && $this->courseId > 0 && $this->pageId > 0 && $this->ordinal > 0;
		}

		/**
		 * Determines if the system should attempt to delete the lesson.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the lesson.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the lesson.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			return $this->id > 0 && $this->courseId > 0 && $this->pageId > 0 && $this->ordinal > 0;
		}

		/**
		 * Initializes a new DocLesson object.
		 *
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocLesson');

			$this->setColumn('id',       'ID',       BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('courseId', 'CourseID', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('pageId',   'PageID',   BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('ordinal',  'Ordinal',  BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			$this->id       = 0;
			$this->courseId = 0;
			$this->pageId   = 0;
			$this->ordinal  = 0;

			return;
		}
	}
