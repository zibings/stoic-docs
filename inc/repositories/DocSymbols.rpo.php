<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * States a symbol can be in when viewed at a version.
	 *
	 * @package Zibings
	 */
	class DocSymbolStates {
		const string CURRENT = 'current';
		const string REMOVED = 'removed';
	}

	/**
	 * A symbol resolved at a version: the contract valid there (or the last contract before removal), plus the
	 * version identifiers the UI needs for since / changed / removed badges.
	 *
	 * @package Zibings
	 */
	class DocSymbolNode {
		/**
		 * Child nodes (options of a type, methods of a class), resolved at the same version.
		 *
		 * @var DocSymbolNode[]
		 */
		public array $children = [];


		/**
		 * Instantiates a new DocSymbolNode object.
		 *
		 * @param DocSymbol $symbol The symbol.
		 * @param DocSymbolVersion $version Contract valid at the version, or the last one before removal.
		 * @param string $state Either DocSymbolStates::CURRENT or DocSymbolStates::REMOVED.
		 * @param int $sinceVersionId Identifier of the version the symbol first appeared in.
		 * @param null|int $changedVersionId Identifier of the version the current contract began in, when that is later than since.
		 * @param null|int $removedVersionId Identifier of the version the symbol was removed in, when removed.
		 */
		public function __construct(
			public DocSymbol        $symbol,
			public DocSymbolVersion $version,
			public string           $state,
			public int              $sinceVersionId,
			public ?int             $changedVersionId,
			public ?int             $removedVersionId) {
			return;
		}

		/**
		 * Serializes the node for API output.
		 *
		 * @return array
		 */
		public function toArray() : array {
			return [
				'symbol'           => $this->symbol->toSerializableArray(),
				'version'          => $this->version->toSerializableArray(),
				'state'            => $this->state,
				'sinceVersionId'   => $this->sinceVersionId,
				'changedVersionId' => $this->changedVersionId,
				'removedVersionId' => $this->removedVersionId,
				'children'         => array_map(fn (DocSymbolNode $c) => $c->toArray(), $this->children)
			];
		}
	}

	/**
	 * Repository methods for symbols and their versioned contracts.
	 *
	 * @package Zibings
	 */
	class DocSymbols extends StoicDbClass {
		const string SQL_GETALL           = 'docsymbols-getall';
		const string SQL_GETBYMODULE      = 'docsymbols-getbymodule';
		const string SQL_GETCHILDREN      = 'docsymbols-getchildren';
		const string SQL_GETSIBLINGS      = 'docsymbols-getsiblings';
		const string SQL_GETSIBLINGSROOT  = 'docsymbols-getsiblingsroot';
		const string SQL_GETALLVERSIONS   = 'docsymbols-getallversions';
		const string SQL_GETVERSIONS      = 'docsymbols-getversions';
		const string SQL_GETCONTRACTAT    = 'docsymbols-getcontractat';


		/**
		 * Internal DocSymbol instance.
		 *
		 * @var DocSymbol
		 */
		protected DocSymbol $symObj;
		/**
		 * Internal DocSymbolVersion instance.
		 *
		 * @var DocSymbolVersion
		 */
		protected DocSymbolVersion $svObj;


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
			$this->symObj = new DocSymbol($this->db, $this->log);
			$this->svObj  = new DocSymbolVersion($this->db, $this->log);

			if (!static::$dbInitialized) {
				$symSelect = $this->symObj->generateClassQuery(BaseDbQueryTypes::SELECT, false);
				$svCols    = "`sv`.`ID`, `sv`.`SymbolID`, `sv`.`IntroducedVersionID`, `sv`.`RemovedVersionID`, `sv`.`Signature`, `sv`.`Summary`, `sv`.`Status`, `sv`.`ParamsJson`, `sv`.`ReturnsJson`, `sv`.`ThrowsJson`, `sv`.`SourcePath`, `sv`.`SourceLine`";
				$svFrom    = "FROM `DocSymbolVersion` AS `sv` INNER JOIN `DocVersion` AS `vi` ON `vi`.`ID` = `sv`.`IntroducedVersionID` LEFT JOIN `DocVersion` AS `vr` ON `vr`.`ID` = `sv`.`RemovedVersionID`";

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETALL,          "{$symSelect} ORDER BY `ModuleID` ASC, `SortOrder` ASC, `Name` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETBYMODULE,     "{$symSelect} WHERE `ModuleID` = :moduleId ORDER BY `SortOrder` ASC, `Name` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETCHILDREN,     "{$symSelect} WHERE `ParentSymbolID` = :parentId ORDER BY `SortOrder` ASC, `Name` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETSIBLINGS,     "{$symSelect} WHERE `ModuleID` = :moduleId AND `ParentSymbolID` = :parentId ORDER BY `SortOrder` ASC, `Name` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETSIBLINGSROOT, "{$symSelect} WHERE `ModuleID` = :moduleId AND `ParentSymbolID` IS NULL ORDER BY `SortOrder` ASC, `Name` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETALLVERSIONS,  "SELECT {$svCols} {$svFrom} ORDER BY `sv`.`SymbolID` ASC, `vi`.`SortKey` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETVERSIONS,     "SELECT {$svCols} {$svFrom} WHERE `sv`.`SymbolID` = :symbolId ORDER BY `vi`.`SortKey` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETCONTRACTAT,   "SELECT {$svCols} {$svFrom} WHERE `sv`.`SymbolID` = :symbolId AND `vi`.`SortKey` <= :sortKey1 AND (`sv`.`RemovedVersionID` IS NULL OR `vr`.`SortKey` > :sortKey2) ORDER BY `vi`.`SortKey` DESC LIMIT 1");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Builds the reference string for a symbol: `module/path#Name` or `module/path#Parent.Child`.
		 *
		 * @param DocSymbol $symbol The symbol.
		 * @return string
		 */
		public function buildRef(DocSymbol $symbol) : string {
			$names  = [$symbol->name];
			$cursor = $symbol;

			while ($cursor->parentSymbolId !== null && $cursor->parentSymbolId > 0) {
				$cursor = DocSymbol::fromId($cursor->parentSymbolId, $this->db, $this->log);

				if ($cursor->id < 1) {
					break;
				}

				array_unshift($names, $cursor->name);
			}

			$module = DocModule::fromId($symbol->moduleId, $this->db, $this->log);

			return "{$module->path}#" . implode('.', $names);
		}

		/**
		 * Resolves a reference string (`module/path#Name` or `module/path#Parent.Child`) to a symbol. Returns a blank
		 * symbol if any part is missing.
		 *
		 * @param string $ref Reference string.
		 * @return DocSymbol
		 */
		public function findByRef(string $ref) : DocSymbol {
			$blank = new DocSymbol($this->db, $this->log);
			$parts = explode('#', $ref, 2);

			if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
				return $blank;
			}

			$module = DocModule::fromPath($parts[0], $this->db, $this->log);

			if ($module->id < 1) {
				return $blank;
			}

			$parentId = null;
			$symbol   = $blank;

			foreach (explode('.', $parts[1]) as $name) {
				$symbol = DocSymbol::fromName($module->id, $name, $parentId, $this->db, $this->log);

				if ($symbol->id < 1) {
					return $blank;
				}

				$parentId = $symbol->id;
			}

			return $symbol;
		}

		/**
		 * Retrieves every symbol, ordered by module then display order.
		 *
		 * @return DocSymbol[]
		 */
		public function getAll() : array {
			return $this->fetchSymbols(self::SQL_GETALL, []);
		}

		/**
		 * Retrieves the symbols in a module (all nesting levels).
		 *
		 * @param int $moduleId Identifier of the module.
		 * @return DocSymbol[]
		 */
		public function getByModule(int $moduleId) : array {
			return $this->fetchSymbols(self::SQL_GETBYMODULE, [':moduleId' => [$moduleId, \PDO::PARAM_INT]]);
		}

		/**
		 * Retrieves the direct children of a symbol.
		 *
		 * @param int $symbolId Identifier of the parent symbol.
		 * @return DocSymbol[]
		 */
		public function getChildren(int $symbolId) : array {
			return $this->fetchSymbols(self::SQL_GETCHILDREN, [':parentId' => [$symbolId, \PDO::PARAM_INT]]);
		}

		/**
		 * Retrieves the contract valid for a symbol at a version, or a blank contract when the symbol is absent or
		 * removed there.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @param int $versionId Identifier of the version.
		 * @return DocSymbolVersion
		 */
		public function getContractAtVersion(int $symbolId, int $versionId) : DocSymbolVersion {
			$version = DocVersion::fromId($versionId, $this->db, $this->log);

			if ($version->id < 1 || $symbolId < 1) {
				return new DocSymbolVersion($this->db, $this->log);
			}

			$rows = $this->fetchVersions(self::SQL_GETCONTRACTAT, [
				':symbolId' => [$symbolId, \PDO::PARAM_INT],
				':sortKey1' => [$version->sortKey, \PDO::PARAM_INT],
				':sortKey2' => [$version->sortKey, \PDO::PARAM_INT]
			]);

			return (count($rows) > 0) ? $rows[0] : new DocSymbolVersion($this->db, $this->log);
		}

		/**
		 * Resolves one symbol at a version, with its children resolved too. Returns null when the symbol had not yet
		 * been introduced at that version.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @param int $versionId Identifier of the version.
		 * @return null|DocSymbolNode
		 */
		public function getNodeAtVersion(int $symbolId, int $versionId) : ?DocSymbolNode {
			$symbol   = DocSymbol::fromId($symbolId, $this->db, $this->log);
			$sortKeys = (new DocVersions($this->db, $this->log))->getSortKeyMap();

			if ($symbol->id < 1 || !array_key_exists($versionId, $sortKeys)) {
				return null;
			}

			$node = $this->resolveNode($symbol, $this->getVersions($symbol->id), $sortKeys[$versionId], $sortKeys);

			if ($node === null) {
				return null;
			}

			foreach ($this->getChildren($symbol->id) as $child) {
				$childNode = $this->resolveNode($child, $this->getVersions($child->id), $sortKeys[$versionId], $sortKeys);

				if ($childNode !== null) {
					$node->children[] = $childNode;
				}
			}

			return $node;
		}

		/**
		 * Resolves the siblings of a symbol (same module and parent, including the symbol itself) at a version.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @param int $versionId Identifier of the version.
		 * @param bool $includeRemoved Whether to include symbols removed by that version.
		 * @return DocSymbolNode[]
		 */
		public function getSiblingsAtVersion(int $symbolId, int $versionId, bool $includeRemoved = true) : array {
			$ret      = [];
			$symbol   = DocSymbol::fromId($symbolId, $this->db, $this->log);
			$sortKeys = (new DocVersions($this->db, $this->log))->getSortKeyMap();

			if ($symbol->id < 1 || !array_key_exists($versionId, $sortKeys)) {
				return $ret;
			}

			if ($symbol->parentSymbolId === null) {
				$siblings = $this->fetchSymbols(self::SQL_GETSIBLINGSROOT, [':moduleId' => [$symbol->moduleId, \PDO::PARAM_INT]]);
			} else {
				$siblings = $this->fetchSymbols(self::SQL_GETSIBLINGS, [':moduleId' => [$symbol->moduleId, \PDO::PARAM_INT], ':parentId' => [$symbol->parentSymbolId, \PDO::PARAM_INT]]);
			}

			foreach ($siblings as $sibling) {
				$node = $this->resolveNode($sibling, $this->getVersions($sibling->id), $sortKeys[$versionId], $sortKeys);

				if ($node === null || (!$includeRemoved && $node->state === DocSymbolStates::REMOVED)) {
					continue;
				}

				$ret[] = $node;
			}

			return $ret;
		}

		/**
		 * Builds the full browse tree at a version: modules, each with its top-level symbol nodes and their children.
		 * Modules with no symbols introduced by that version are omitted.
		 *
		 * [
		 *   [ 'module' => (DocModule) {}, 'symbols' => (DocSymbolNode[]) [] ]
		 * ]
		 *
		 * @param int $versionId Identifier of the version.
		 * @param bool $includeRemoved Whether to include symbols removed by that version.
		 * @return array
		 */
		public function getTreeAtVersion(int $versionId, bool $includeRemoved = true) : array {
			$ret      = [];
			$sortKeys = (new DocVersions($this->db, $this->log))->getSortKeyMap();

			if (!array_key_exists($versionId, $sortKeys)) {
				return $ret;
			}

			$target     = $sortKeys[$versionId];
			$versionsBy = [];

			foreach ($this->fetchVersions(self::SQL_GETALLVERSIONS, []) as $sv) {
				$versionsBy[$sv->symbolId][] = $sv;
			}

			$nodes    = [];
			$byModule = [];

			foreach ($this->getAll() as $symbol) {
				$node = $this->resolveNode($symbol, $versionsBy[$symbol->id] ?? [], $target, $sortKeys);

				if ($node === null || (!$includeRemoved && $node->state === DocSymbolStates::REMOVED)) {
					continue;
				}

				$nodes[$symbol->id] = $node;
			}

			foreach ($nodes as $id => $node) {
				$parentId = $node->symbol->parentSymbolId;

				if ($parentId === null) {
					$byModule[$node->symbol->moduleId][] = $node;
				} else if (array_key_exists($parentId, $nodes)) {
					$nodes[$parentId]->children[] = $node;
				}
			}

			foreach ((new DocModules($this->db, $this->log))->getAll() as $module) {
				if (!array_key_exists($module->id, $byModule)) {
					continue;
				}

				$ret[] = [
					'module'  => $module,
					'symbols' => $byModule[$module->id]
				];
			}

			return $ret;
		}

		/**
		 * Retrieves every contract row for a symbol, oldest first.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @return DocSymbolVersion[]
		 */
		public function getVersions(int $symbolId) : array {
			return $this->fetchVersions(self::SQL_GETVERSIONS, [':symbolId' => [$symbolId, \PDO::PARAM_INT]]);
		}

		/**
		 * Resolves a symbol at a target sort key given its contract rows (oldest first). Returns null when no row had
		 * been introduced by then.
		 *
		 * @param DocSymbol $symbol The symbol.
		 * @param DocSymbolVersion[] $rows Contract rows for the symbol, oldest first.
		 * @param int $targetSortKey Sort key of the version being viewed.
		 * @param array $sortKeys Map of version identifier to sort key.
		 * @return null|DocSymbolNode
		 */
		protected function resolveNode(DocSymbol $symbol, array $rows, int $targetSortKey, array $sortKeys) : ?DocSymbolNode {
			$eligible = [];

			foreach ($rows as $row) {
				if (($sortKeys[$row->introducedVersionId] ?? PHP_INT_MAX) <= $targetSortKey) {
					$eligible[] = $row;
				}
			}

			if (count($eligible) < 1) {
				return null;
			}

			$since = $eligible[0]->introducedVersionId;
			$valid = null;

			foreach ($eligible as $row) {
				if ($row->removedVersionId === null || ($sortKeys[$row->removedVersionId] ?? PHP_INT_MAX) > $targetSortKey) {
					$valid = $row;
				}
			}

			if ($valid !== null) {
				$changed = ($valid->introducedVersionId !== $since) ? $valid->introducedVersionId : null;

				return new DocSymbolNode($symbol, $valid, DocSymbolStates::CURRENT, $since, $changed, null);
			}

			$last = $eligible[count($eligible) - 1];

			return new DocSymbolNode($symbol, $last, DocSymbolStates::REMOVED, $since, null, $last->removedVersionId);
		}

		/**
		 * Runs a stored query and hydrates DocSymbol objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocSymbol[]
		 */
		protected function fetchSymbols(string $key, array $binds) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $key, $binds) {
				$stmt = $this->db->prepareStored($key);

				foreach ($binds as $param => $bind) {
					$stmt->bindValue($param, $bind[0], $bind[1]);
				}

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocSymbol::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve symbols");

			return $ret;
		}

		/**
		 * Runs a stored query and hydrates DocSymbolVersion objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocSymbolVersion[]
		 */
		protected function fetchVersions(string $key, array $binds) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $key, $binds) {
				$stmt = $this->db->prepareStored($key);

				foreach ($binds as $param => $bind) {
					$stmt->bindValue($param, $bind[0], $bind[1]);
				}

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocSymbolVersion::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve symbol versions");

			return $ret;
		}
	}
