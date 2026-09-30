<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Repository methods for context-bar options (languages and package managers).
	 *
	 * @package Zibings
	 */
	class DocContextOptions extends StoicDbClass {
		const string SQL_GETALL    = 'doccontextoptions-getall';
		const string SQL_GETBYKIND = 'doccontextoptions-getbykind';


		/**
		 * Internal DocContextOption instance.
		 *
		 * @var DocContextOption
		 */
		protected DocContextOption $optObj;


		/**
		 * Whether the stored queries have been initialized.
		 *
		 * @var bool
		 */
		private static bool $dbInitialized = false;


		/**
		 * Initializes the internal DocContextOption instance and stored queries.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->optObj = new DocContextOption($this->db, $this->log);

			if (!static::$dbInitialized) {
				$select = $this->optObj->generateClassQuery(BaseDbQueryTypes::SELECT, false);

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETALL,    "{$select} ORDER BY `Kind` ASC, `SortOrder` ASC, `Label` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETBYKIND, "{$select} WHERE `Kind` = :kind ORDER BY `SortOrder` ASC, `Label` ASC");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Retrieves all options grouped by kind in display order.
		 *
		 * @return DocContextOption[]
		 */
		public function getAll() : array {
			return $this->fetchList(self::SQL_GETALL, []);
		}

		/**
		 * Retrieves the options of one kind in display order.
		 *
		 * @param string $kind Option kind, see DocContextOptionKinds.
		 * @return DocContextOption[]
		 */
		public function getByKind(string $kind) : array {
			return $this->fetchList(self::SQL_GETBYKIND, [':kind' => [$kind, \PDO::PARAM_STR]]);
		}

		/**
		 * Retrieves the default option of one kind, falling back to the first. Returns a blank option if none exist.
		 *
		 * @param string $kind Option kind, see DocContextOptionKinds.
		 * @return DocContextOption
		 */
		public function getDefault(string $kind) : DocContextOption {
			$options = $this->getByKind($kind);

			foreach ($options as $opt) {
				if ($opt->isDefault) {
					return $opt;
				}
			}

			return (count($options) > 0) ? $options[0] : new DocContextOption($this->db, $this->log);
		}

		/**
		 * Runs a stored query and hydrates DocContextOption objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocContextOption[]
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
						$ret[] = DocContextOption::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve context options");

			return $ret;
		}
	}
