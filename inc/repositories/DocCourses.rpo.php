<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Repository methods for courses, lessons, and lesson steps.
	 *
	 * @package Zibings
	 */
	class DocCourses extends StoicDbClass {
		const string SQL_GETALL         = 'doccourses-getall';
		const string SQL_GETLESSONS     = 'doccourses-getlessons';
		const string SQL_GETLESSONPAGE  = 'doccourses-getlessonforpage';
		const string SQL_GETSTEPS       = 'doccourses-getsteps';


		/**
		 * Internal DocCourse instance.
		 *
		 * @var DocCourse
		 */
		protected DocCourse $crsObj;
		/**
		 * Internal DocLesson instance.
		 *
		 * @var DocLesson
		 */
		protected DocLesson $lsnObj;
		/**
		 * Internal DocLessonStep instance.
		 *
		 * @var DocLessonStep
		 */
		protected DocLessonStep $stpObj;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Initializes the internal instances and stored queries.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->crsObj = new DocCourse($this->db, $this->log);
			$this->lsnObj = new DocLesson($this->db, $this->log);
			$this->stpObj = new DocLessonStep($this->db, $this->log);

			if (!static::$dbInitialized) {
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETALL,        $this->crsObj->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " ORDER BY `SortOrder` ASC, `Title` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETLESSONS,    $this->lsnObj->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `CourseID` = :courseId ORDER BY `Ordinal` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETLESSONPAGE, $this->lsnObj->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `PageID` = :pageId LIMIT 1");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETSTEPS,      $this->stpObj->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `LessonID` = :lessonId ORDER BY `Ordinal` ASC");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Retrieves all courses in display order.
		 *
		 * @return DocCourse[]
		 */
		public function getAll() : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret) {
				$stmt = $this->db->queryStored(self::SQL_GETALL);

				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$ret[] = DocCourse::fromArray($row, $this->db, $this->log);
				}

				return;
			}, "Failed to retrieve courses");

			return $ret;
		}

		/**
		 * Retrieves the lesson that uses a page, or a blank lesson if none does.
		 *
		 * @param int $pageId Identifier of the page.
		 * @return DocLesson
		 */
		public function getLessonForPage(int $pageId) : DocLesson {
			$ret = new DocLesson($this->db, $this->log);

			$this->tryPdoExcept(function () use (&$ret, $pageId) {
				$stmt = $this->db->prepareStored(self::SQL_GETLESSONPAGE);
				$stmt->bindValue(':pageId', $pageId, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);

					if ($row !== false) {
						$ret = DocLesson::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve lesson for page");

			return $ret;
		}

		/**
		 * Retrieves a course's lessons in order, each paired with its page.
		 *
		 * [
		 *   [ 'lesson' => (DocLesson) {}, 'page' => (DocPage) {} ]
		 * ]
		 *
		 * @param int $courseId Identifier of the course.
		 * @return array
		 */
		public function getLessons(int $courseId) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $courseId) {
				$stmt = $this->db->prepareStored(self::SQL_GETLESSONS);
				$stmt->bindValue(':courseId', $courseId, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$lesson = DocLesson::fromArray($row, $this->db, $this->log);

						$ret[] = [
							'lesson' => $lesson,
							'page'   => DocPage::fromId($lesson->pageId, $this->db, $this->log)
						];
					}
				}

				return;
			}, "Failed to retrieve lessons");

			return $ret;
		}

		/**
		 * Retrieves a lesson's steps in order.
		 *
		 * @param int $lessonId Identifier of the lesson.
		 * @return DocLessonStep[]
		 */
		public function getSteps(int $lessonId) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $lessonId) {
				$stmt = $this->db->prepareStored(self::SQL_GETSTEPS);
				$stmt->bindValue(':lessonId', $lessonId, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocLessonStep::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve lesson steps");

			return $ret;
		}
	}
