<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoDrivers;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Repository methods for pages, their linked symbols, and their code samples.
	 *
	 * @package Zibings
	 */
	class DocPages extends StoicDbClass {
		const string SQL_GETATVERSION      = 'docpages-getatversion';
		const string SQL_GETBYMODE         = 'docpages-getbymode';
		const string SQL_GETFORSYMBOL      = 'docpages-getforsymbol';
		const string SQL_GETREFERENCEFOR   = 'docpages-getreferenceforsymbol';
		const string SQL_GETPAGESYMBOLS    = 'docpages-getpagesymbols';
		const string SQL_GETSAMPLES        = 'docpages-getsamples';


		/**
		 * Internal DocPage instance.
		 *
		 * @var DocPage
		 */
		protected DocPage $pgObj;
		/**
		 * Internal DocCodeSample instance.
		 *
		 * @var DocCodeSample
		 */
		protected DocCodeSample $smpObj;


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
			$this->pgObj  = new DocPage($this->db, $this->log);
			$this->smpObj = new DocCodeSample($this->db, $this->log);

			if (!static::$dbInitialized) {
				$pgCols = "`p`.`ID`, `p`.`Mode`, `p`.`Slug`, `p`.`Title`, `p`.`Summary`, `p`.`Body`, `p`.`IntroducedVersionID`, `p`.`RemovedVersionID`, `p`.`Minutes`, `p`.`Created`, `p`.`Updated`";
				$pgFrom = "FROM `DocPage` AS `p` INNER JOIN `DocVersion` AS `vi` ON `vi`.`ID` = `p`.`IntroducedVersionID` LEFT JOIN `DocVersion` AS `vr` ON `vr`.`ID` = `p`.`RemovedVersionID`";
				$valid  = "`vi`.`SortKey` <= :sortKey1 AND (`p`.`RemovedVersionID` IS NULL OR `vr`.`SortKey` > :sortKey2)";

				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETATVERSION,    "SELECT {$pgCols} {$pgFrom} WHERE `p`.`Mode` = :mode AND `p`.`Slug` = :slug AND {$valid} ORDER BY `vi`.`SortKey` DESC LIMIT 1");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETBYMODE,       "SELECT {$pgCols} {$pgFrom} WHERE `p`.`Mode` = :mode AND {$valid} ORDER BY `p`.`Title` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETFORSYMBOL,    "SELECT {$pgCols}, `ps`.`Role` AS `PageRole` {$pgFrom} INNER JOIN `DocPageSymbol` AS `ps` ON `ps`.`PageID` = `p`.`ID` WHERE `ps`.`SymbolID` = :symbolId AND {$valid} ORDER BY `p`.`Mode` ASC, `p`.`Title` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETREFERENCEFOR, "SELECT {$pgCols} {$pgFrom} INNER JOIN `DocPageSymbol` AS `ps` ON `ps`.`PageID` = `p`.`ID` WHERE `ps`.`SymbolID` = :symbolId AND `ps`.`Role` = 'subject' AND `p`.`Mode` = 'reference' AND {$valid} ORDER BY `vi`.`SortKey` DESC LIMIT 1");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETPAGESYMBOLS,  "SELECT `ps`.`SymbolID`, `ps`.`Role` FROM `DocPageSymbol` AS `ps` INNER JOIN `DocSymbol` AS `s` ON `s`.`ID` = `ps`.`SymbolID` WHERE `ps`.`PageID` = :pageId ORDER BY `ps`.`Role` DESC, `s`.`SortOrder` ASC, `s`.`Name` ASC");
				PdoHelper::storeQuery(PdoDrivers::PDO_MYSQL, self::SQL_GETSAMPLES,      $this->smpObj->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " WHERE `PageID` = :pageId ORDER BY `SampleKey` ASC, `Language` ASC, `Variant` ASC");

				static::$dbInitialized = true;
			}

			return;
		}

		/**
		 * Retrieves the page revision valid for a mode and slug at a version, or a blank page if none.
		 *
		 * @param string $mode Reader mode.
		 * @param string $slug Page slug.
		 * @param int $versionId Identifier of the version.
		 * @return DocPage
		 */
		public function getAtVersion(string $mode, string $slug, int $versionId) : DocPage {
			$version = DocVersion::fromId($versionId, $this->db, $this->log);

			if ($version->id < 1) {
				return new DocPage($this->db, $this->log);
			}

			$pages = $this->fetchPages(self::SQL_GETATVERSION, [
				':mode'     => [$mode, \PDO::PARAM_STR],
				':slug'     => [$slug, \PDO::PARAM_STR],
				':sortKey1' => [$version->sortKey, \PDO::PARAM_INT],
				':sortKey2' => [$version->sortKey, \PDO::PARAM_INT]
			]);

			return (count($pages) > 0) ? $pages[0] : new DocPage($this->db, $this->log);
		}

		/**
		 * Retrieves every page of a mode valid at a version.
		 *
		 * @param string $mode Reader mode.
		 * @param int $versionId Identifier of the version.
		 * @return DocPage[]
		 */
		public function getByMode(string $mode, int $versionId) : array {
			$version = DocVersion::fromId($versionId, $this->db, $this->log);

			if ($version->id < 1) {
				return [];
			}

			return $this->fetchPages(self::SQL_GETBYMODE, [
				':mode'     => [$mode, \PDO::PARAM_STR],
				':sortKey1' => [$version->sortKey, \PDO::PARAM_INT],
				':sortKey2' => [$version->sortKey, \PDO::PARAM_INT]
			]);
		}

		/**
		 * Retrieves the pages linked to a symbol that are valid at a version, with the symbol's role on each.
		 *
		 * [
		 *   [ 'page' => (DocPage) {}, 'role' => 'subject' ]
		 * ]
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @param int $versionId Identifier of the version.
		 * @return array
		 */
		public function getPagesForSymbol(int $symbolId, int $versionId) : array {
			$ret     = [];
			$version = DocVersion::fromId($versionId, $this->db, $this->log);

			if ($version->id < 1) {
				return $ret;
			}

			$this->tryPdoExcept(function () use (&$ret, $symbolId, $version) {
				$stmt = $this->db->prepareStored(self::SQL_GETFORSYMBOL);
				$stmt->bindValue(':symbolId', $symbolId, \PDO::PARAM_INT);
				$stmt->bindValue(':sortKey1', $version->sortKey, \PDO::PARAM_INT);
				$stmt->bindValue(':sortKey2', $version->sortKey, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$role = $row['PageRole'];
						unset($row['PageRole']);

						$ret[] = [
							'page' => DocPage::fromArray($row, $this->db, $this->log),
							'role' => $role
						];
					}
				}

				return;
			}, "Failed to retrieve pages for symbol");

			return $ret;
		}

		/**
		 * Retrieves the reference page whose subject is the symbol, valid at a version, or a blank page if none.
		 *
		 * @param int $symbolId Identifier of the symbol.
		 * @param int $versionId Identifier of the version.
		 * @return DocPage
		 */
		public function getReferencePageForSymbol(int $symbolId, int $versionId) : DocPage {
			$version = DocVersion::fromId($versionId, $this->db, $this->log);

			if ($version->id < 1) {
				return new DocPage($this->db, $this->log);
			}

			$pages = $this->fetchPages(self::SQL_GETREFERENCEFOR, [
				':symbolId' => [$symbolId, \PDO::PARAM_INT],
				':sortKey1' => [$version->sortKey, \PDO::PARAM_INT],
				':sortKey2' => [$version->sortKey, \PDO::PARAM_INT]
			]);

			return (count($pages) > 0) ? $pages[0] : new DocPage($this->db, $this->log);
		}

		/**
		 * Retrieves the code sample for a page, key, language, and variant, falling back to the variant-less sample.
		 * Returns a blank sample if none matches.
		 *
		 * @param int $pageId Identifier of the page.
		 * @param string $key Sample key.
		 * @param string $language Language key.
		 * @param null|string $variant Variant key, or null.
		 * @return DocCodeSample
		 */
		public function getSample(int $pageId, string $key, string $language, ?string $variant) : DocCodeSample {
			$fallback = new DocCodeSample($this->db, $this->log);

			foreach ($this->getSamples($pageId) as $sample) {
				if ($sample->sampleKey !== $key || $sample->language !== $language) {
					continue;
				}

				if ($sample->variant === $variant) {
					return $sample;
				}

				if ($sample->variant === null) {
					$fallback = $sample;
				}
			}

			return $fallback;
		}

		/**
		 * Retrieves every code sample on a page.
		 *
		 * @param int $pageId Identifier of the page.
		 * @return DocCodeSample[]
		 */
		public function getSamples(int $pageId) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $pageId) {
				$stmt = $this->db->prepareStored(self::SQL_GETSAMPLES);
				$stmt->bindValue(':pageId', $pageId, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocCodeSample::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve code samples");

			return $ret;
		}

		/**
		 * Retrieves the subject symbol of a page, or a blank symbol if it has none.
		 *
		 * @param int $pageId Identifier of the page.
		 * @return DocSymbol
		 */
		public function getSubjectSymbol(int $pageId) : DocSymbol {
			foreach ($this->getSymbols($pageId) as $link) {
				if ($link['role'] === DocPageSymbolRoles::SUBJECT) {
					return $link['symbol'];
				}
			}

			return new DocSymbol($this->db, $this->log);
		}

		/**
		 * Retrieves the symbols linked to a page with their roles, subject first.
		 *
		 * [
		 *   [ 'symbol' => (DocSymbol) {}, 'role' => 'subject' ]
		 * ]
		 *
		 * @param int $pageId Identifier of the page.
		 * @return array
		 */
		public function getSymbols(int $pageId) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $pageId) {
				$stmt = $this->db->prepareStored(self::SQL_GETPAGESYMBOLS);
				$stmt->bindValue(':pageId', $pageId, \PDO::PARAM_INT);

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = [
							'symbol' => DocSymbol::fromId(intval($row['SymbolID']), $this->db, $this->log),
							'role'   => $row['Role']
						];
					}
				}

				return;
			}, "Failed to retrieve page symbols");

			return $ret;
		}

		/**
		 * Runs a stored query and hydrates DocPage objects.
		 *
		 * @param string $key Stored query key.
		 * @param array $binds Map of placeholder to [value, type].
		 * @return DocPage[]
		 */
		protected function fetchPages(string $key, array $binds) : array {
			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $key, $binds) {
				$stmt = $this->db->prepareStored($key);

				foreach ($binds as $param => $bind) {
					$stmt->bindValue($param, $bind[0], $bind[1]);
				}

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = DocPage::fromArray($row, $this->db, $this->log);
					}
				}

				return;
			}, "Failed to retrieve pages");

			return $ret;
		}
	}
