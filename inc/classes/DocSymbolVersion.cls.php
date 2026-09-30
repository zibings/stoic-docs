<?php

	namespace Zibings;

	use Stoic\Log\Logger;
	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Well-known symbol statuses.
	 *
	 * @package Zibings
	 */
	class DocSymbolStatuses {
		const string STABLE       = 'stable';
		const string EXPERIMENTAL = 'experimental';
		const string DEPRECATED   = 'deprecated';
	}

	/**
	 * The contract of a symbol over a version range: valid from IntroducedVersionID (inclusive) until RemovedVersionID
	 * (exclusive, null when still current). A contract change closes one row and opens another.
	 *
	 * Params, returns, and throws are stored as JSON strings; use the typed accessors. Params entries are objects with
	 * name, type, required, default, description, and since keys.
	 *
	 * @package Zibings
	 */
	class DocSymbolVersion extends StoicDbModel {
		/**
		 * Integer identifier of the contract row.
		 *
		 * @var int
		 */
		public int $id;
		/**
		 * Identifier of the version this contract was introduced in.
		 *
		 * @var int
		 */
		public int $introducedVersionId;
		/**
		 * JSON string of parameter descriptors.
		 *
		 * @var string
		 */
		public string $paramsJson;
		/**
		 * Identifier of the version this contract was removed or replaced in, null when current.
		 *
		 * @var null|int
		 */
		public ?int $removedVersionId;
		/**
		 * JSON string describing the return value.
		 *
		 * @var string
		 */
		public string $returnsJson;
		/**
		 * Full signature text as rendered in the contract pane.
		 *
		 * @var string
		 */
		public string $signature;
		/**
		 * Source line for the source link, if known.
		 *
		 * @var null|int
		 */
		public ?int $sourceLine;
		/**
		 * Source path for the source link, if known.
		 *
		 * @var null|string
		 */
		public ?string $sourcePath;
		/**
		 * Symbol status, see DocSymbolStatuses.
		 *
		 * @var string
		 */
		public string $status;
		/**
		 * One-line summary shown under the symbol name.
		 *
		 * @var string
		 */
		public string $summary;
		/**
		 * Identifier of the symbol this contract belongs to.
		 *
		 * @var int
		 */
		public int $symbolId;
		/**
		 * JSON string of throwable descriptors.
		 *
		 * @var string
		 */
		public string $throwsJson;


		/**
		 * Retrieves a contract row by its integer identifier. Returns a blank row if not found.
		 *
		 * @param int $id Integer identifier of the row.
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @throws \Exception
		 * @return DocSymbolVersion
		 */
		public static function fromId(int $id, PdoHelper $db, null|Logger $log = null) : DocSymbolVersion {
			$ret = new DocSymbolVersion($db, $log);
			$ret->id = $id;

			if ($ret->read()->isBad()) {
				$ret->id = 0;
			}

			return $ret;
		}


		/**
		 * Returns the decoded parameter descriptors.
		 *
		 * @return array
		 */
		public function getParams() : array {
			return static::decodeList($this->paramsJson);
		}

		/**
		 * Returns the decoded return descriptor (empty array when none).
		 *
		 * @return array
		 */
		public function getReturns() : array {
			$decoded = json_decode($this->returnsJson, true);

			return is_array($decoded) ? $decoded : [];
		}

		/**
		 * Returns the decoded throwable descriptors.
		 *
		 * @return array
		 */
		public function getThrows() : array {
			return static::decodeList($this->throwsJson);
		}

		/**
		 * Sets the parameter descriptors.
		 *
		 * @param array $params List of parameter descriptors.
		 * @return void
		 */
		public function setParams(array $params) : void {
			$this->paramsJson = json_encode(array_values($params));

			return;
		}

		/**
		 * Sets the return descriptor.
		 *
		 * @param array $returns Return descriptor (type, description).
		 * @return void
		 */
		public function setReturns(array $returns) : void {
			$this->returnsJson = json_encode((object)$returns);

			return;
		}

		/**
		 * Sets the throwable descriptors.
		 *
		 * @param array $throws List of throwable descriptors.
		 * @return void
		 */
		public function setThrows(array $throws) : void {
			$this->throwsJson = json_encode(array_values($throws));

			return;
		}

		/**
		 * Serializes the row with decoded JSON fields for API output.
		 *
		 * @return array
		 */
		public function toSerializableArray() : array {
			$ret = parent::toSerializableArray();

			unset($ret['paramsJson'], $ret['returnsJson'], $ret['throwsJson']);

			$ret['params']  = $this->getParams();
			$ret['returns'] = $this->getReturns();
			$ret['throws']  = $this->getThrows();

			return $ret;
		}

		/**
		 * Decodes a JSON list, returning an empty list on failure.
		 *
		 * @param string $json JSON text.
		 * @return array
		 */
		protected static function decodeList(string $json) : array {
			$decoded = json_decode($json, true);

			return is_array($decoded) ? array_values($decoded) : [];
		}

		/**
		 * Checks that the JSON fields hold valid JSON.
		 *
		 * @return bool
		 */
		protected function hasValidJson() : bool {
			foreach ([$this->paramsJson, $this->returnsJson, $this->throwsJson] as $json) {
				json_decode($json);

				if (json_last_error() !== JSON_ERROR_NONE) {
					return false;
				}
			}

			return true;
		}


		/**
		 * Determines if the system should attempt to create the row.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id > 0 || $this->symbolId < 1 || $this->introducedVersionId < 1) {
				$ret->addMessage("Cannot create a DocSymbolVersion with an id or without symbol and introduced version");

				return $ret;
			}

			if (!$this->hasValidJson()) {
				$ret->addMessage("Cannot create a DocSymbolVersion with invalid JSON fields");

				return $ret;
			}

			$ret->makeGood();

			return $ret;
		}

		/**
		 * Determines if the system should attempt to delete the row.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to read the row.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->id > 0;
		}

		/**
		 * Determines if the system should attempt to update the row.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			$ret = new ReturnHelper();

			if ($this->id < 1 || $this->symbolId < 1 || $this->introducedVersionId < 1) {
				$ret->addMessage("Cannot update a DocSymbolVersion without id, symbol, and introduced version");

				return $ret;
			}

			if (!$this->hasValidJson()) {
				$ret->addMessage("Cannot update a DocSymbolVersion with invalid JSON fields");

				return $ret;
			}

			$ret->makeGood();

			return $ret;
		}

		/**
		 * Initializes a new DocSymbolVersion object.
		 *
		 * @throws \Exception
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocSymbolVersion');

			$this->setColumn('id',                  'ID',                  BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::AUTO_INCREMENT);
			$this->setColumn('symbolId',            'SymbolID',            BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('introducedVersionId', 'IntroducedVersionID', BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('removedVersionId',    'RemovedVersionID',    BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('signature',           'Signature',           BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('summary',             'Summary',             BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('status',              'Status',              BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('paramsJson',          'ParamsJson',          BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('returnsJson',         'ReturnsJson',         BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('throwsJson',          'ThrowsJson',          BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);
			$this->setColumn('sourcePath',          'SourcePath',          BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);
			$this->setColumn('sourceLine',          'SourceLine',          BaseDbTypes::INTEGER, BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE | BCF::ALLOWS_NULLS);

			$this->id                  = 0;
			$this->symbolId            = 0;
			$this->introducedVersionId = 0;
			$this->removedVersionId    = null;
			$this->signature           = '';
			$this->summary             = '';
			$this->status              = DocSymbolStatuses::STABLE;
			$this->paramsJson          = '[]';
			$this->returnsJson         = '{}';
			$this->throwsJson          = '[]';
			$this->sourcePath          = null;
			$this->sourceLine          = null;

			return;
		}
	}
