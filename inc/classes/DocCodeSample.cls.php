<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * A code sample for a page, keyed by sample key plus language plus optional variant (package manager). The page
	 * body embeds it by key; the reader's context picks which language and variant row renders.
	 *
	 * @package Zibings
	 */
	class DocCodeSample extends StoicDbModel {
		/**
		 * Source code text.
		 *
		 * @var string
		 */
		public string $code;
		/**
		 * Integer identifier of the sample.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Whether this sample is part of the tested-examples pipeline.
		 *
		 * @var bool
		 */
		public bool $isTested;
		/**
		 * Language key matching a DocContextOption of kind language.
		 *
		 * @var string
		 */
		public string $language;
		/**
		 * Identifier of the last version this sample passed its test on, if any.
		 *
		 * @var null|int
		 */
		public ?int $lastTestPassVersionId;
		/**
		 * Identifier of the page the sample belongs to.
		 *
		 * @var int
		 */
		public int $pageId;
		/**
		 * Key the page body uses to embed this sample.
		 *
		 * @var string
		 */
		public string $sampleKey;
		/**
		 * Optional display title, typically a file name such as `user.ts`.
		 *
		 * @var null|string
		 */
		public ?string $title;
		/**
		 * Variant key (package manager) matching a DocContextOption, or null when the sample applies to every variant.
		 *
		 * @var null|string
		 */
		public ?string $variant;


		/**
		 * Retrieves a sample by its integer identifier. Returns a blank sample if not found.
		 *
		 * @param int $id Integer identifier of the sample.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocCodeSample
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocCodeSample {
			$ret = new DocCodeSample($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the sample.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			return $this->id < 1 && $this->pageId > 0 && !empty($this->sampleKey) && !empty($this->language);
		}

		/**
		 * Determines if the system should attempt to delete the sample.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the sample.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the sample.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			return $this->id > 0 && $this->pageId > 0 && !empty($this->sampleKey) && !empty($this->language);
		}

		/**
		 * Initializes a new DocCodeSample object.
		 *
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocCodeSample');

			$this->setColumn('id',                    'ID',                    BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('pageId',                'PageID',                BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sampleKey',             'SampleKey',             BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('title',                 'Title',                 BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('language',              'Language',              BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('variant',               'Variant',               BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('code',                  'Code',                  BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('isTested',              'IsTested',              BaseDbTypes::BOOLEAN, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('lastTestPassVersionId', 'LastTestPassVersionID', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);

			$this->id                    = 0;
			$this->pageId                = 0;
			$this->sampleKey             = '';
			$this->title                 = null;
			$this->language              = '';
			$this->variant               = null;
			$this->code                  = '';
			$this->isTested              = false;
			$this->lastTestPassVersionId = null;

			return;
		}
	}
