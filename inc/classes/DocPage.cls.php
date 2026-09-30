<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * The four reader modes (Diátaxis) plus the reserved `home` mode. The reader modes are fixed by the product
	 * design, not per-library data. `home` holds a single page (slug `index`) whose summary is the landing page
	 * tagline and whose body is the optional prose under the hero; it never appears as a header tab or in search.
	 *
	 * @package Zibings
	 */
	class DocPageModes {
		const string LEARN     = 'learn';
		const string DO        = 'do';
		const string REFERENCE = 'reference';
		const string EXPLAIN   = 'explain';
		const string HOME      = 'home';

		/** Slug of the one page in the `home` mode. */
		const string HOME_SLUG = 'index';


		/**
		 * Returns all valid modes, including the reserved `home` mode.
		 *
		 * @return string[]
		 */
		public static function all() : array {
			return [self::LEARN, self::DO, self::REFERENCE, self::EXPLAIN, self::HOME];
		}

		/**
		 * Returns the four reader modes shown as header tabs.
		 *
		 * @return string[]
		 */
		public static function readerModes() : array {
			return [self::LEARN, self::DO, self::REFERENCE, self::EXPLAIN];
		}

		/**
		 * Whether the value is a valid mode.
		 *
		 * @param string $mode Mode value to check.
		 * @return bool
		 */
		public static function isValid(string $mode) : bool {
			return in_array($mode, self::all(), true);
		}
	}

	/**
	 * A prose page in one reader mode, valid over a version range. Body is Markdown with directives; reference-mode
	 * pages are the prose for a symbol and link to it through DocPageSymbol with the `subject` role.
	 *
	 * @package Zibings
	 */
	class DocPage extends StoicDbModel {
		const string SQL_COUNTDUPNOTID = 'docpage-countduplicatenotid';


		/**
		 * Markdown body.
		 *
		 * @var string
		 */
		public string $body;
		/**
		 * Date and time the page was created.
		 *
		 * @var \DateTimeInterface
		 */
		public \DateTimeInterface $created;
		/**
		 * Integer identifier of the page.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Identifier of the version this page revision was introduced in.
		 *
		 * @var int
		 */
		public int $introducedVersionId;
		/**
		 * Estimated reading or completion time in minutes, if any.
		 *
		 * @var null|int
		 */
		public ?int $minutes;
		/**
		 * Reader mode, see DocPageModes.
		 *
		 * @var string
		 */
		public string $mode;
		/**
		 * Identifier of the version this page revision was removed or superseded in, null when current.
		 *
		 * @var null|int
		 */
		public ?int $removedVersionId;
		/**
		 * URL slug, unique within a mode and version.
		 *
		 * @var string
		 */
		public string $slug;
		/**
		 * One-line summary.
		 *
		 * @var string
		 */
		public string $summary;
		/**
		 * Page title.
		 *
		 * @var string
		 */
		public string $title;
		/**
		 * Date and time the page was last updated.
		 *
		 * @var \DateTimeInterface
		 */
		public \DateTimeInterface $updated;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Retrieves a page by its integer identifier. Returns a blank page if not found.
		 *
		 * @param int $id Integer identifier of the page.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocPage
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocPage {
			$ret = new DocPage($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}


		/**
		 * Determines if the system should attempt to create the page.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || !$this->hasRequiredFields()) {
				$ret->addMessage("Cannot create a DocPage with an id or without a valid mode, slug, title, and introduced version");

				return $ret;
			}

			$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
			$this->created = $now;
			$this->updated = $now;

			$this->checkDuplicates($ret, 0);

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the page.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the page.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the page.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || !$this->hasRequiredFields()) {
				$ret->addMessage("Cannot update a DocPage without id and a valid mode, slug, title, and introduced version");

				return $ret;
			}

			$this->updated = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

			$this->checkDuplicates($ret, $this->id);

			return $ret;
		}

		/**
		 * Marks the ReturnHelper good unless another page uses the same mode, slug, and introduced version.
		 *
		 * @param ReturnHelper $ret ReturnHelper to update.
		 * @param int $excludeId Identifier to exclude from the duplicate check.
		 * @return void
		 */
		protected function checkDuplicates(ReturnHelper $ret, int $excludeId) : void {
			$this->tryPdoExcept(function () use (&$ret, $excludeId) {
				$stmt = $this->db->prepareStored(self::SQL_COUNTDUPNOTID);
				$stmt->bindValue(':mode', $this->mode);
				$stmt->bindValue(':slug', $this->slug);
				$stmt->bindValue(':versionId', $this->introducedVersionId, \PDO::PARAM_INT);
				$stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->fetch()[0] > 0) {
					$ret->addMessage("Found duplicate DocPage by mode, slug, and introduced version (Mode: {$this->mode}, Slug: {$this->slug})");
				} else {
					$ret->makeGood();
				}

				return;
			}, "Failed to check for duplicate pages");

			return;
		}

		/**
		 * Whether the page has the fields every page needs.
		 *
		 * @return bool
		 */
		protected function hasRequiredFields() : bool {
			return DocPageModes::isValid($this->mode) && !empty($this->slug) && !empty($this->title) && $this->introducedVersionId > 0;
		}

		/**
		 * Initializes a new DocPage object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocPage');

			$this->setColumn('id',                  'ID',                  BaseDbTypes::INTEGER,  BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('mode',                'Mode',                BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('slug',                'Slug',                BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('title',               'Title',               BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('summary',             'Summary',             BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('body',                'Body',                BaseDbTypes::STRING,   BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('introducedVersionId', 'IntroducedVersionID', BaseDbTypes::INTEGER,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('removedVersionId',    'RemovedVersionID',    BaseDbTypes::INTEGER,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('minutes',             'Minutes',             BaseDbTypes::INTEGER,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('created',             'Created',             BaseDbTypes::DATETIME, BCF::SHOULD_INSERT);
			$this->setColumn('updated',             'Updated',             BaseDbTypes::DATETIME, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			if (!static::$dbInitialized) {
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_COUNTDUPNOTID, "SELECT COUNT(*) FROM {$this->getDbTableName()} WHERE `Mode` = :mode AND `Slug` = :slug AND `IntroducedVersionID` = :versionId AND `ID` <> :id");

				static::$dbInitialized = true;
			}

			$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

			$this->id                  = 0;
			$this->mode                = '';
			$this->slug                = '';
			$this->title               = '';
			$this->summary             = '';
			$this->body                = '';
			$this->introducedVersionId = 0;
			$this->removedVersionId    = null;
			$this->minutes             = null;
			$this->created             = $now;
			$this->updated             = $now;

			return;
		}
	}
