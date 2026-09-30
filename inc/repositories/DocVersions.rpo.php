<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Repository methods for documented library versions.
	 *
	 * @package Zibings
	 */
	class DocVersions extends StoicDbClass {
		const string SQL_GETALL      = 'docversions-getall';
		const string SQL_GETLATEST   = 'docversions-getlatest';
		const string SQL_GETHIGHEST  = 'docversions-gethighest';
		const string SQL_GETRANGE    = 'docversions-getrange';
		const string SQL_CLEARLATEST = 'docversions-clearlatest';
		const string SQL_SETLATEST   = 'docversions-setlatest';


		/**
		 * Internal DocVersion instance.
		 *
		 * @var DocVersion
		 */
		protected DocVersion $verObj;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Initializes the internal DocVersion instance and stored queries.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->verObj = new DocVersion($this->db, $this->log);

			if (!static::$dbInitialized) {
				$select = $this->verObj->generateClassQuery(BaseDbQueryTypes::SELECT, false);
				$table  = $this->verObj->getDbTableName();

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETALL,      "{$select} ORDER BY `SortKey` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETLATEST,   "{$select} WHERE `IsLatest` = 1 ORDER BY `SortKey` DESC LIMIT 1");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETHIGHEST,  "{$select} ORDER BY `SortKey` DESC LIMIT 1");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETRANGE,    "{$select} WHERE `SortKey` > :fromKey AND `SortKey` <= :toKey ORDER BY `SortKey` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_CLEARLATEST, "UPDATE {$table} SET `IsLatest` = 0");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_SETLATEST,   "UPDATE {$table} SET `IsLatest` = 1 WHERE `ID` = :id");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Retrieves all versions ordered oldest to newest.
		 *
		 * @return DocVersion[]
		 */
		public function getAll() : array {
			return $this->fetchList(self::SQL_GETALL, []);
		}

		/**
		 * Retrieves the version marked latest, falling back to the highest sort key. Returns a blank version if none.
		 *
		 * @return DocVersion
		 */
		public function getLatest() : DocVersion {
			$latest = $this->fetchList(self::SQL_GETLATEST, []);

			if (count($latest) < 1) {
				$latest = $this->fetchList(self::SQL_GETHIGHEST, []);
			}

			return (count($latest) > 0) ? $latest[0] : new DocVersion($this->db, $this->log);
		}

		/**
		 * Retrieves the versions after `from` up to and including `to`, ordered oldest to newest.
		 *
		 * @param int $fromVersionId Identifier of the starting version (exclusive).
		 * @param int $toVersionId Identifier of the ending version (inclusive).
		 * @return DocVersion[]
		 */
		public function getRange(int $fromVersionId, int $toVersionId) : array {
			$from = DocVersion::fromId($fromVersionId, $this->db, $this->log);
			$to   = DocVersion::fromId($toVersionId, $this->db, $this->log);

			if ($from->id < 1 || $to->id < 1) {
				return [];
			}

			return $this->fetchList(self::SQL_GETRANGE, [':fromKey' => [$from->sortKey, \PDO::PARAM_INT], ':toKey' => [$to->sortKey, \PDO::PARAM_INT]]);
		}

		/**
		 * Returns a map of version identifier to sort key for every version.
		 *
		 * @return array
		 */
		public function getSortKeyMap() : array {
			$ret = [];

			foreach ($this->getAll() as $ver) {
				$ret[$ver->id] = $ver->sortKey;
			}

			return $ret;
		}

		/**
		 * Retrieves the supported versions ordered oldest to newest.
		 *
		 * @return DocVersion[]
		 */
		public function getSupported() : array {
			return array_values(array_filter($this->getAll(), fn (DocVersion $v) => $v->isSupported));
		}

		/**
		 * Marks one version as latest and clears the flag on every other version.
		 *
		 * @param int $versionId Identifier of the version to mark.
		 * @return bool
		 */
		public function markLatest(int $versionId) : bool {
			$ret = false;

			if ($versionId < 1) {
				return $ret;
			}

			$this->tryPdoExcept(function () use (&$ret, $versionId) {
				$this->db->queryStored(self::SQL_CLEARLATEST);

				$stmt = $this->db->prepareStored(self::SQL_SETLATEST);
				$stmt->bindValue(':id', $versionId, \PDO::PARAM_INT);
				$ret = $stmt->execute() && $stmt->rowCount() > 0;

				return;
			}, "Failed to mark version as latest");

			return $ret;
		}

		/**
		 * Runs a stored query and hydrates DocVersion objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocVersion[]
		 */
		protected function fetchList(string $key, array $binds) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $key, $binds) {
				$stmt = $this->db->prepareStored($key);

				foreach ($binds as $param => $bind) {
					$stmt->bindValue($param, $bind[0], $bind[1]);
				}

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocVersion::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve versions");

			return $ret;
		}
	}
