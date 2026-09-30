<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * A "Try it" step within a lesson, with optional hint, answer, and the output the workspace shows on success.
	 *
	 * @package Zibings
	 */
	class DocLessonStep extends StoicDbModel {
		/**
		 * The answer revealed on request, if any.
		 *
		 * @var null|string
		 */
		public ?string $answer;
		/**
		 * Expected output shown by the workspace when the step passes, if any.
		 *
		 * @var null|string
		 */
		public ?string $expectedOutput;
		/**
		 * A hint revealed on request, if any.
		 *
		 * @var null|string
		 */
		public ?string $hint;
		/**
		 * Integer identifier of the step.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Identifier of the lesson.
		 *
		 * @var int
		 */
		public int $lessonId;
		/**
		 * Position within the lesson, starting at 1.
		 *
		 * @var int
		 */
		public int $ordinal;
		/**
		 * What the reader is asked to do.
		 *
		 * @var string
		 */
		public string $prompt;


		/**
		 * Retrieves a step by its integer identifier. Returns a blank step if not found.
		 *
		 * @param int $id Integer identifier of the step.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocLessonStep
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocLessonStep {
			$ret = new DocLessonStep($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the step.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			return $this->id < 1 && $this->lessonId > 0 && $this->ordinal > 0 && !empty($this->prompt);
		}

		/**
		 * Determines if the system should attempt to delete the step.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the step.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the step.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			return $this->id > 0 && $this->lessonId > 0 && $this->ordinal > 0 && !empty($this->prompt);
		}

		/**
		 * Initializes a new DocLessonStep object.
		 *
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocLessonStep');

			$this->setColumn('id',             'ID',             BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('lessonId',       'LessonID',       BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('ordinal',        'Ordinal',        BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('prompt',         'Prompt',         BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('hint',           'Hint',           BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('answer',         'Answer',         BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('expectedOutput', 'ExpectedOutput', BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);

			$this->id             = 0;
			$this->lessonId       = 0;
			$this->ordinal        = 0;
			$this->prompt         = '';
			$this->hint           = null;
			$this->answer         = null;
			$this->expectedOutput = null;

			return;
		}
	}
