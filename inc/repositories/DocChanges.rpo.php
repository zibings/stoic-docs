<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * The difference between a symbol's contract at two versions, computed from DocSymbolVersion rows.
	 *
	 * @package Zibings
	 */
	class DocContractDiff {
		/**
		 * Parameters present after but not before, as descriptor arrays.
		 *
		 * @var array
		 */
		public array $paramsAdded = [];
		/**
		 * Parameters whose descriptor differs, as [ 'name' => string, 'before' => array, 'after' => array ].
		 *
		 * @var array
		 */
		public array $paramsChanged = [];
		/**
		 * Parameters present before but not after, as descriptor arrays.
		 *
		 * @var array
		 */
		public array $paramsRemoved = [];
		/**
		 * Whether the signature text differs.
		 *
		 * @var bool
		 */
		public bool $signatureChanged = false;


		/**
		 * Instantiates a new DocContractDiff object and computes the parameter differences.
		 *
		 * @param DocSymbolVersion $before Contract at the earlier version (blank when absent there).
		 * @param DocSymbolVersion $after Contract at the later version (blank when absent there).
		 */
		public function __construct(
			public DocSymbolVersion $before,
			public DocSymbolVersion $after) {
			$beforeParams = [];
			$afterParams  = [];

			foreach ($before->getParams() as $param) {
				if (isset($param['name'])) {
					$beforeParams[$param['name']] = $param;
				}
			}

			foreach ($after->getParams() as $param) {
				if (isset($param['name'])) {
					$afterParams[$param['name']] = $param;
				}
			}

			foreach ($afterParams as $name => $param) {
				if (!array_key_exists($name, $beforeParams)) {
					$this->paramsAdded[] = $param;
				} else if ($beforeParams[$name] != $param) {
					$this->paramsChanged[] = ['name' => $name, 'before' => $beforeParams[$name], 'after' => $param];
				}
			}

			foreach ($beforeParams as $name => $param) {
				if (!array_key_exists($name, $afterParams)) {
					$this->paramsRemoved[] = $param;
				}
			}

			$this->signatureChanged = $before->signature !== $after->signature;

			return;
		}

		/**
		 * Whether anything differs between the two contracts.
		 *
		 * @return bool
		 */
		public function hasChanges() : bool {
			return $this->signatureChanged || count($this->paramsAdded) > 0 || count($this->paramsChanged) > 0 || count($this->paramsRemoved) > 0 || $this->before->id !== $this->after->id;
		}

		/**
		 * Serializes the diff for API output.
		 *
		 * @return array
		 */
		public function toArray() : array {
			return [
				'before'           => ($this->before->id > 0) ? $this->before->toSerializableArray() : null,
				'after'            => ($this->after->id > 0) ? $this->after->toSerializableArray() : null,
				'signatureChanged' => $this->signatureChanged,
				'paramsAdded'      => $this->paramsAdded,
				'paramsChanged'    => $this->paramsChanged,
				'paramsRemoved'    => $this->paramsRemoved
			];
		}
	}

	/**
	 * Repository methods for changelog entries and contract diffs.
	 *
	 * @package Zibings
	 */
	class DocChanges extends StoicDbClass {
		const string SQL_GETBETWEEN     = 'docchanges-getbetween';
		const string SQL_GETFORVERSION  = 'docchanges-getforversion';
		const string SQL_GETFORSYMBOL   = 'docchanges-getforsymbol';
		const string SQL_GETSYMBOLS     = 'docchanges-getsymbols';


		/**
		 * Internal DocChange instance.
		 *
		 * @var DocChange
		 */
		protected DocChange $chgObj;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Initializes the internal instance and stored queries.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->chgObj = new DocChange($this->db, $this->log);

			if (!static::$dbInitialized) {
				$cols  = "`c`.`ID`, `c`.`VersionID`, `c`.`Kind`, `c`.`Title`, `c`.`Why`, `c`.`RfcUrl`, `c`.`BeforeCode`, `c`.`AfterCode`, `c`.`CodemodCmd`, `c`.`SortOrder`";
				$from  = "FROM `DocChange` AS `c` INNER JOIN `DocVersion` AS `v` ON `v`.`ID` = `c`.`VersionID`";
				$order = "ORDER BY `v`.`SortKey` ASC, FIELD(`c`.`Kind`, 'breaking', 'behavior', 'deprecated', 'added', 'removed') ASC, `c`.`SortOrder` ASC, `c`.`Title` ASC";

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETBETWEEN,    "SELECT {$cols} {$from} WHERE `v`.`SortKey` > :fromKey AND `v`.`SortKey` <= :toKey {$order}");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETFORVERSION, "SELECT {$cols} {$from} WHERE `c`.`VersionID` = :versionId {$order}");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETFORSYMBOL,  "SELECT {$cols} {$from} INNER JOIN `DocChangeSymbol` AS `cs` ON `cs`.`ChangeID` = `c`.`ID` WHERE `cs`.`SymbolID` = :symbolId {$order}");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETSYMBOLS,    "SELECT `cs`.`SymbolID` FROM `DocChangeSymbol` AS `cs` INNER JOIN `DocSymbol` AS `s` ON `s`.`ID` = `cs`.`SymbolID` WHERE `cs`.`ChangeID` = :changeId ORDER BY `s`.`SortOrder` ASC, `s`.`Name` ASC");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Retrieves the changes shipped after `from` up to and including `to`, grouped by version then kind order.
		 *
		 * @param int $fromVersionId Identifier of the starting version (exclusive).
		 * @param int $toVersionId Identifier of the ending version (inclusive).
		 * @return DocChange[]
		 */
		public function getBetween(int $fromVersionId, int $toVersionId) : array {
			$from = DocVersion::fromId($fromVersionId, $this->db, $this->log);
			$to   = DocVersion::fromId($toVersionId, $this->db, $this->log);

			if ($from->id < 1 || $to->id < 1) {
				return [];
			}

			return $this->fetchChanges(self::SQL_GETBETWEEN, [':fromKey' => [$from->sortKey, \PDO::PARAM_INT], ':toKey' => [$to->sortKey, \PDO::PARAM_INT]]);
		}

		/**
		 * Retrieves the changes that reference a symbol, oldest first.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @return DocChange[]
		 */
		public function getChangesForSymbol(int $symbolId) : array {
			return $this->fetchChanges(self::SQL_GETFORSYMBOL, [':symbolId' => [$symbolId, \PDO::PARAM_INT]]);
		}

		/**
		 * Computes the contract diff for a symbol between two versions.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @param int $fromVersionId Identifier of the earlier version.
		 * @param int $toVersionId Identifier of the later version.
		 * @return DocContractDiff
		 */
		public function getContractDiff(int $symbolId, int $fromVersionId, int $toVersionId) : DocContractDiff {
			$symbols = new DocSymbols($this->db, $this->log);

			return new DocContractDiff(
				$symbols->getContractAtVersion($symbolId, $fromVersionId),
				$symbols->getContractAtVersion($symbolId, $toVersionId)
			);
		}

		/**
		 * Counts the changes in a range by kind, in display order, including zero counts.
		 *
		 * @param int $fromVersionId Identifier of the starting version (exclusive).
		 * @param int $toVersionId Identifier of the ending version (inclusive).
		 * @return array
		 */
		public function getCountsByKind(int $fromVersionId, int $toVersionId) : array {
			$ret = array_fill_keys(DocChangeKinds::all(), 0);

			foreach ($this->getBetween($fromVersionId, $toVersionId) as $change) {
				$ret[$change->kind] = ($ret[$change->kind] ?? 0) + 1;
			}

			return $ret;
		}

		/**
		 * Retrieves the changes shipped in one version.
		 *
		 * @param int $versionId Identifier of the version.
		 * @return DocChange[]
		 */
		public function getForVersion(int $versionId) : array {
			return $this->fetchChanges(self::SQL_GETFORVERSION, [':versionId' => [$versionId, \PDO::PARAM_INT]]);
		}

		/**
		 * Retrieves the symbols a change affects.
		 *
		 * @param int $changeId Identifier of the change.
		 * @return DocSymbol[]
		 */
		public function getSymbols(int $changeId) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $changeId) {
				$stmt = $this->db->prepareStored(self::SQL_GETSYMBOLS);
				$stmt->bindValue(':changeId', $changeId, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocSymbol::fromId(intval($row['SymbolID']), $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve change symbols");

			return $ret;
		}

		/**
		 * Runs a stored query and hydrates DocChange objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocChange[]
		 */
		protected function fetchChanges(string $key, array $binds) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $key, $binds) {
				$stmt = $this->db->prepareStored($key);

				foreach ($binds as $param => $bind) {
					$stmt->bindValue($param, $bind[0], $bind[1]);
				}

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocChange::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve changes");

			return $ret;
		}
	}
