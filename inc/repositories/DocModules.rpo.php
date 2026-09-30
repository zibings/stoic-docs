<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Repository methods for modules.
	 *
	 * @package Zibings
	 */
	class DocModules extends StoicDbClass {
		const string SQL_GETALL       = 'docmodules-getall';
		const string SQL_GETATVERSION = 'docmodules-getatversion';


		/**
		 * Internal DocModule instance.
		 *
		 * @var DocModule
		 */
		protected DocModule $modObj;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Initializes the internal DocModule instance and stored queries.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->modObj = new DocModule($this->db, $this->log);

			if (!static::$dbInitialized) {
				$select = $this->modObj->generateClassQuery(BaseDbQueryTypes::SELECT, false);

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETALL, "{$select} ORDER BY `SortOrder` ASC, `Path` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETATVERSION,
					"SELECT DISTINCT `m`.`ID`, `m`.`Path`, `m`.`Summary`, `m`.`SortOrder` FROM `DocModule` AS `m` " .
					"INNER JOIN `DocSymbol` AS `s` ON `s`.`ModuleID` = `m`.`ID` " .
					"INNER JOIN `DocSymbolVersion` AS `sv` ON `sv`.`SymbolID` = `s`.`ID` " .
					"INNER JOIN `DocVersion` AS `vi` ON `vi`.`ID` = `sv`.`IntroducedVersionID` " .
					"WHERE `vi`.`SortKey` <= :sortKey ORDER BY `m`.`SortOrder` ASC, `m`.`Path` ASC");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Retrieves all modules in display order.
		 *
		 * @return DocModule[]
		 */
		public function getAll() : array {
			return $this->fetchList(self::SQL_GETALL, []);
		}

		/**
		 * Retrieves modules that have at least one symbol introduced at or before the version (removed symbols still
		 * count, so the browse tree can show them struck through).
		 *
		 * @param int $versionId Identifier of the version.
		 * @return DocModule[]
		 */
		public function getAtVersion(int $versionId) : array {
			$version = DocVersion::fromId($versionId, $this->db, $this->log);

			if ($version->id < 1) {
				return [];
			}

			return $this->fetchList(self::SQL_GETATVERSION, [':sortKey' => [$version->sortKey, \PDO::PARAM_INT]]);
		}

		/**
		 * Runs a stored query and hydrates DocModule objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocModule[]
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
						$ret[] = DocModule::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve modules");

			return $ret;
		}
	}
