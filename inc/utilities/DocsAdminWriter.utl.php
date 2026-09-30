<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Raised by the admin writer for client errors; `status` becomes the HTTP status.
	 *
	 * @package Zibings
	 */
	class DocsAdminException extends \RuntimeException {
		public function __construct(string $message, public readonly int $status = 400) {
			parent::__construct($message);

			return;
		}
	}

	/**
	 * Write-side service behind the admin docs API. Every documentation table is a "resource" with the same list,
	 * get, create, update, and delete operations; field lists and coercions live in RESOURCES so the controller and
	 * the authoring UI stay generic. Validation comes from the model guards; failures surface as DocsAdminException.
	 *
	 * @package Zibings
	 */
	class DocsAdminWriter extends StoicDbClass {
		const array RESOURCES = [
			'versions' => [
				'class'   => DocVersion::class,
				'fields'  => ['tag' => 'string', 'label' => 'string', 'sortKey' => 'int', 'releasedAt' => 'date?', 'isLatest' => 'bool', 'isSupported' => 'bool'],
				'order'   => '`SortKey` ASC',
				'filters' => [],
				'reorder' => null,
			],
			'modules' => [
				'class'   => DocModule::class,
				'fields'  => ['path' => 'string', 'summary' => 'string', 'sortOrder' => 'int'],
				'order'   => '`SortOrder` ASC, `Path` ASC',
				'filters' => [],
				'reorder' => 'sortOrder',
			],
			'symbols' => [
				'class'   => DocSymbol::class,
				'fields'  => ['moduleId' => 'int', 'parentSymbolId' => 'int?', 'name' => 'string', 'kind' => 'string', 'sortOrder' => 'int'],
				'order'   => '`ModuleID` ASC, `SortOrder` ASC, `Name` ASC',
				'filters' => ['moduleId' => 'ModuleID', 'parentSymbolId' => 'ParentSymbolID'],
				'reorder' => 'sortOrder',
			],
			'contracts' => [
				'class'   => DocSymbolVersion::class,
				'fields'  => ['symbolId' => 'int', 'introducedVersionId' => 'int', 'removedVersionId' => 'int?', 'signature' => 'string', 'summary' => 'string', 'status' => 'string', 'params' => 'json', 'returns' => 'json', 'throws' => 'json', 'sourcePath' => 'string?', 'sourceLine' => 'int?'],
				'order'   => '`SymbolID` ASC, `IntroducedVersionID` ASC',
				'filters' => ['symbolId' => 'SymbolID'],
				'reorder' => null,
			],
			'pages' => [
				'class'   => DocPage::class,
				'fields'  => ['mode' => 'string', 'slug' => 'string', 'title' => 'string', 'summary' => 'string', 'body' => 'string', 'introducedVersionId' => 'int', 'removedVersionId' => 'int?', 'minutes' => 'int?'],
				'order'   => '`Mode` ASC, `Slug` ASC, `IntroducedVersionID` ASC',
				'filters' => ['mode' => 'Mode'],
				'reorder' => null,
			],
			'samples' => [
				'class'   => DocCodeSample::class,
				'fields'  => ['pageId' => 'int', 'sampleKey' => 'string', 'title' => 'string?', 'language' => 'string', 'variant' => 'string?', 'code' => 'string', 'isTested' => 'bool', 'lastTestPassVersionId' => 'int?'],
				'order'   => '`PageID` ASC, `SampleKey` ASC, `Language` ASC, `Variant` ASC',
				'filters' => ['pageId' => 'PageID'],
				'reorder' => null,
			],
			'changes' => [
				'class'   => DocChange::class,
				'fields'  => ['versionId' => 'int', 'kind' => 'string', 'title' => 'string', 'why' => 'string', 'rfcUrl' => 'string?', 'beforeCode' => 'string?', 'afterCode' => 'string?', 'codemodCmd' => 'string?', 'sortOrder' => 'int'],
				'order'   => '`VersionID` ASC, `Kind` ASC, `SortOrder` ASC, `Title` ASC',
				'filters' => ['versionId' => 'VersionID', 'kind' => 'Kind'],
				'reorder' => 'sortOrder',
			],
			'courses' => [
				'class'   => DocCourse::class,
				'fields'  => ['slug' => 'string', 'title' => 'string', 'summary' => 'string', 'sortOrder' => 'int'],
				'order'   => '`SortOrder` ASC, `Title` ASC',
				'filters' => [],
				'reorder' => 'sortOrder',
			],
			'lessons' => [
				'class'   => DocLesson::class,
				'fields'  => ['courseId' => 'int', 'pageId' => 'int', 'ordinal' => 'int'],
				'order'   => '`CourseID` ASC, `Ordinal` ASC',
				'filters' => ['courseId' => 'CourseID'],
				'reorder' => 'ordinal',
			],
			'steps' => [
				'class'   => DocLessonStep::class,
				'fields'  => ['lessonId' => 'int', 'ordinal' => 'int', 'prompt' => 'string', 'hint' => 'string?', 'answer' => 'string?', 'expectedOutput' => 'string?'],
				'order'   => '`LessonID` ASC, `Ordinal` ASC',
				'filters' => ['lessonId' => 'LessonID'],
				'reorder' => 'ordinal',
			],
			'contextOptions' => [
				'class'   => DocContextOption::class,
				'fields'  => ['kind' => 'string', 'optionKey' => 'string', 'label' => 'string', 'sortOrder' => 'int', 'isDefault' => 'bool'],
				'order'   => '`Kind` ASC, `SortOrder` ASC, `Label` ASC',
				'filters' => ['kind' => 'Kind'],
				'reorder' => 'sortOrder',
			],
		];


		protected DocSymbols $symbols;


		/**
		 * Initializes the repositories.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->symbols = new DocSymbols($this->db, $this->log);

			return;
		}


		/**
		 * Resolves a resource name from the URL (case-insensitive), throwing 404 for unknown ones.
		 *
		 * @param string $name Resource segment.
		 * @return string
		 */
		public function resourceName(string $name) : string {
			foreach (array_keys(self::RESOURCES) as $key) {
				if (strcasecmp($key, $name) === 0) {
					return $key;
				}
			}

			throw new DocsAdminException("Unknown resource '{$name}'", 404);
		}

		/**
		 * Lists every row of a resource, optionally filtered by the resource's filterable fields.
		 *
		 * @param string $resource Resource name.
		 * @param array $filters Field => value pairs (unknown fields ignored).
		 * @return array
		 */
		public function list(string $resource, array $filters = []) : array {
			$def   = self::RESOURCES[$this->resourceName($resource)];
			$model = new ($def['class'])($this->db, $this->log);
			$sql   = $model->generateClassQuery(BaseDbQueryTypes::SELECT, false);
			$where = [];
			$binds = [];

			foreach ($def['filters'] as $field => $column) {
				if (array_key_exists($field, $filters) && $filters[$field] !== null && $filters[$field] !== '') {
					$where[]         = "`{$column}` = :{$field}";
					$binds[":{$field}"] = $filters[$field];
				}
			}

			if (count($where) > 0) {
				$sql .= ' WHERE ' . implode(' AND ', $where);
			}

			$sql .= " ORDER BY {$def['order']}";

			$ret = [];

			$this->tryPdoExcept(function () use (&$ret, $sql, $binds, $def) {
				$stmt = $this->db->prepare($sql);

				foreach ($binds as $param => $value) {
					$stmt->bindValue($param, $value);
				}

				if ($stmt->execute()) {
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						$ret[] = $this->serialize(($def['class'])::fromArray($row, $this->db, $this->log));
					}
				}

				return;
			}, "Failed to list {$resource}");

			return $ret;
		}

		/**
		 * Retrieves one row, throwing 404 when missing.
		 *
		 * @param string $resource Resource name.
		 * @param int $id Row identifier.
		 * @return array
		 */
		public function get(string $resource, int $id) : array {
			return $this->serialize($this->load($resource, $id));
		}

		/**
		 * Creates a row from request data and returns it.
		 *
		 * @param string $resource Resource name.
		 * @param array $data Field values.
		 * @return array
		 */
		public function create(string $resource, array $data) : array {
			$def   = self::RESOURCES[$this->resourceName($resource)];
			$model = new ($def['class'])($this->db, $this->log);

			$this->applyFields($model, $def['fields'], $data);
			$this->checkContractOverlap($model);
			$this->ensure($model->create(), "create {$resource}");

			return $this->serialize($model);
		}

		/**
		 * Updates a row from request data and returns it.
		 *
		 * @param string $resource Resource name.
		 * @param int $id Row identifier.
		 * @param array $data Field values (partial updates allowed).
		 * @return array
		 */
		public function update(string $resource, int $id, array $data) : array {
			$def   = self::RESOURCES[$this->resourceName($resource)];
			$model = $this->load($resource, $id);

			$this->applyFields($model, $def['fields'], $data);
			$this->checkContractOverlap($model);
			$this->ensure($model->update(), "update {$resource} {$id}");

			return $this->serialize($model);
		}

		/**
		 * Deletes a row. Foreign keys cascade to children; rows still referenced (a version with contracts) fail with 400.
		 *
		 * @param string $resource Resource name.
		 * @param int $id Row identifier.
		 * @return void
		 */
		public function delete(string $resource, int $id) : void {
			$model = $this->load($resource, $id);

			$this->ensure($model->delete(), "delete {$resource} {$id}");

			return;
		}

		/**
		 * Marks a version as latest.
		 *
		 * @param int $versionId Version identifier.
		 * @return array
		 */
		public function markLatest(int $versionId) : array {
			$this->load('versions', $versionId);

			if (!(new DocVersions($this->db, $this->log))->markLatest($versionId)) {
				throw new DocsAdminException("Could not mark version {$versionId} as latest", 500);
			}

			return $this->get('versions', $versionId);
		}

		/**
		 * Rewrites the ordering column of the given rows to match the order of `ids` (1-based). Only resources with a
		 * sort order or ordinal support this.
		 *
		 * @param string $resource Resource name.
		 * @param int[] $ids Identifiers in the desired order.
		 * @return array
		 */
		public function reorder(string $resource, array $ids) : array {
			$name  = $this->resourceName($resource);
			$field = self::RESOURCES[$name]['reorder'];

			if ($field === null) {
				throw new DocsAdminException("Resource '{$name}' cannot be reordered");
			}

			$models = array_map(fn ($id) => $this->load($name, intval($id)), array_values($ids));

			// Two passes so unique (parent, ordinal) constraints never collide mid-way; the models require positive
			// values, so park each row far above any real position first.
			foreach ($models as $i => $model) {
				$model->{$field} = 1000000 + $i + 1;
				$this->ensure($model->update(), "reorder {$name}");
			}

			foreach ($models as $i => $model) {
				$model->{$field} = $i + 1;
				$this->ensure($model->update(), "reorder {$name}");
			}

			return array_map(fn ($m) => $this->serialize($m), $models);
		}

		/**
		 * The symbols linked to a page with their roles.
		 *
		 * @param int $pageId Page identifier.
		 * @return array
		 */
		public function getPageSymbols(int $pageId) : array {
			$this->load('pages', $pageId);

			$ret = [];

			foreach ((new DocPages($this->db, $this->log))->getSymbols($pageId) as $link) {
				$ret[] = ['symbolId' => $link['symbol']->id, 'ref' => $this->symbols->buildRef($link['symbol']), 'name' => $link['symbol']->name, 'kind' => $link['symbol']->kind, 'role' => $link['role']];
			}

			return $ret;
		}

		/**
		 * Replaces the symbols linked to a page. Each entry needs `symbolId` or `ref`, and a `role`.
		 *
		 * @param int $pageId Page identifier.
		 * @param array $links Link entries.
		 * @return array
		 */
		public function setPageSymbols(int $pageId, array $links) : array {
			$this->load('pages', $pageId);

			$resolved = [];

			foreach ($links as $entry) {
				$symbolId = $this->resolveSymbolId($entry);
				$role     = strval($entry['role'] ?? DocPageSymbolRoles::MENTIONS);

				if (!DocPageSymbolRoles::isValid($role)) {
					throw new DocsAdminException("Invalid page symbol role '{$role}'");
				}

				$resolved[$symbolId] = $role;
			}

			$this->transaction(function () use ($pageId, $resolved) {
				$this->db->prepare("DELETE FROM `DocPageSymbol` WHERE `PageID` = :id")->execute([':id' => $pageId]);

				foreach ($resolved as $symbolId => $role) {
					$link           = new DocPageSymbol($this->db, $this->log);
					$link->pageId   = $pageId;
					$link->symbolId = $symbolId;
					$link->role     = $role;

					$this->ensure($link->create(), "link symbol {$symbolId} to page {$pageId}");
				}

				return;
			});

			return $this->getPageSymbols($pageId);
		}

		/**
		 * The symbols a change affects.
		 *
		 * @param int $changeId Change identifier.
		 * @return array
		 */
		public function getChangeSymbols(int $changeId) : array {
			$this->load('changes', $changeId);

			$ret = [];

			foreach ((new DocChanges($this->db, $this->log))->getSymbols($changeId) as $symbol) {
				$ret[] = ['symbolId' => $symbol->id, 'ref' => $this->symbols->buildRef($symbol), 'name' => $symbol->name, 'kind' => $symbol->kind];
			}

			return $ret;
		}

		/**
		 * Replaces the symbols a change affects. Entries may be ids, refs, or objects with `symbolId` / `ref`.
		 *
		 * @param int $changeId Change identifier.
		 * @param array $symbols Symbol entries.
		 * @return array
		 */
		public function setChangeSymbols(int $changeId, array $symbols) : array {
			$this->load('changes', $changeId);

			$ids = [];

			foreach ($symbols as $entry) {
				$ids[$this->resolveSymbolId(is_array($entry) ? $entry : (is_numeric($entry) ? ['symbolId' => $entry] : ['ref' => $entry]))] = true;
			}

			$this->transaction(function () use ($changeId, $ids) {
				$this->db->prepare("DELETE FROM `DocChangeSymbol` WHERE `ChangeID` = :id")->execute([':id' => $changeId]);

				foreach (array_keys($ids) as $symbolId) {
					$link           = new DocChangeSymbol($this->db, $this->log);
					$link->changeId = $changeId;
					$link->symbolId = $symbolId;

					$this->ensure($link->create(), "link symbol {$symbolId} to change {$changeId}");
				}

				return;
			});

			return $this->getChangeSymbols($changeId);
		}

		/**
		 * Imports a bundle (upsert) and returns the importer's counts.
		 *
		 * @param array $bundle Decoded bundle.
		 * @return array
		 */
		public function import(array $bundle) : array {
			$importer = new DocBundleImporter($this->db, $this->log);
			$result   = $importer->importBundle($bundle);

			if ($result->isBad()) {
				throw new DocsAdminException($result->hasMessages() ? $result->getMessages()[0] : 'Import failed');
			}

			return $importer->counts;
		}

		/**
		 * Exports every documentation record as a bundle.
		 *
		 * @return array
		 */
		public function export() : array {
			return (new DocBundleExporter($this->db, $this->log))->export();
		}


		/**
		 * Loads a row or throws 404.
		 *
		 * @param string $resource Resource name.
		 * @param int $id Row identifier.
		 * @return StoicDbModel
		 */
		protected function load(string $resource, int $id) : StoicDbModel {
			$def   = self::RESOURCES[$this->resourceName($resource)];
			$model = ($def['class'])::fromId($id, $this->db, $this->log);

			if ($model->id < 1) {
				throw new DocsAdminException("No {$resource} row with id {$id}", 404);
			}

			return $model;
		}

		/**
		 * Assigns request data onto a model with per-field coercion. Unknown keys are ignored.
		 *
		 * @param StoicDbModel $model Target model.
		 * @param array $fields Field => type map from RESOURCES.
		 * @param array $data Request data.
		 * @return void
		 */
		protected function applyFields(StoicDbModel $model, array $fields, array $data) : void {
			foreach ($fields as $field => $type) {
				if (!array_key_exists($field, $data)) {
					continue;
				}

				$value    = $data[$field];
				$nullable = str_ends_with($type, '?');
				$base     = rtrim($type, '?');

				if ($value === null || ($nullable && $value === '')) {
					if (!$nullable) {
						throw new DocsAdminException("Field '{$field}' cannot be null");
					}

					$model->{$field} = null;

					continue;
				}

				switch ($base) {
					case 'int':
						if (!is_numeric($value)) {
							throw new DocsAdminException("Field '{$field}' must be an integer");
						}

						$model->{$field} = intval($value);

						break;
					case 'bool':
						$model->{$field} = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? boolval($value);

						break;
					case 'date':
						try {
							$model->{$field} = new \DateTimeImmutable(strval($value), new \DateTimeZone('UTC'));
						} catch (\Exception) {
							throw new DocsAdminException("Field '{$field}' is not a valid date");
						}

						break;
					case 'json':
						if (!is_array($value)) {
							throw new DocsAdminException("Field '{$field}' must be a JSON array or object");
						}

						match ($field) {
							'params'  => $model->setParams($value),
							'returns' => $model->setReturns($value),
							'throws'  => $model->setThrows($value),
						};

						break;
					default:
						$model->{$field} = strval($value);
				}
			}

			return;
		}

		/**
		 * A symbol may not have two contracts valid at the same version.
		 *
		 * @param StoicDbModel $model Model being saved.
		 * @return void
		 */
		protected function checkContractOverlap(StoicDbModel $model) : void {
			if (!($model instanceof DocSymbolVersion) || $model->symbolId < 1) {
				return;
			}

			$sortKeys = (new DocVersions($this->db, $this->log))->getSortKeyMap();
			$intro    = $sortKeys[$model->introducedVersionId] ?? null;
			$removed  = $model->removedVersionId !== null ? ($sortKeys[$model->removedVersionId] ?? null) : PHP_INT_MAX;

			if ($intro === null) {
				throw new DocsAdminException("introducedVersionId does not name a version");
			}

			if ($model->removedVersionId !== null && $removed === null) {
				throw new DocsAdminException("removedVersionId does not name a version");
			}

			if ($removed <= $intro) {
				throw new DocsAdminException("removedVersionId must be newer than introducedVersionId");
			}

			foreach ($this->symbols->getVersions($model->symbolId) as $other) {
				if ($other->id === $model->id) {
					continue;
				}

				$otherIntro   = $sortKeys[$other->introducedVersionId] ?? 0;
				$otherRemoved = $other->removedVersionId !== null ? ($sortKeys[$other->removedVersionId] ?? PHP_INT_MAX) : PHP_INT_MAX;

				if ($intro < $otherRemoved && $otherIntro < $removed) {
					throw new DocsAdminException("Contract overlaps an existing contract (id {$other->id}) for this symbol");
				}
			}

			return;
		}

		/**
		 * Throws when a model operation failed.
		 *
		 * @param ReturnHelper $result Operation result.
		 * @param string $context Description for the message.
		 * @return void
		 */
		protected function ensure(ReturnHelper $result, string $context) : void {
			if ($result->isBad()) {
				$messages = $result->hasMessages() ? implode('; ', $result->getMessages()) : 'the record was rejected';

				throw new DocsAdminException("Could not {$context}: {$messages}");
			}

			return;
		}

		/**
		 * Resolves `symbolId` or `ref` in a link entry to a symbol id, or throws 400.
		 *
		 * @param array $entry Link entry.
		 * @return int
		 */
		protected function resolveSymbolId(array $entry) : int {
			if (isset($entry['symbolId']) && is_numeric($entry['symbolId'])) {
				$symbol = DocSymbol::fromId(intval($entry['symbolId']), $this->db, $this->log);
			} else if (isset($entry['ref']) && is_string($entry['ref'])) {
				$symbol = $this->symbols->findByRef($entry['ref']);
			} else {
				throw new DocsAdminException("Each symbol link needs a symbolId or a ref");
			}

			if ($symbol->id < 1) {
				throw new DocsAdminException("Unknown symbol " . json_encode($entry['ref'] ?? $entry['symbolId']));
			}

			return $symbol->id;
		}

		/**
		 * Serializes a model, adding the symbol ref for symbols.
		 *
		 * @param StoicDbModel $model Model to serialize.
		 * @return array
		 */
		protected function serialize(StoicDbModel $model) : array {
			$arr = $model->toSerializableArray();

			if ($model instanceof DocSymbol) {
				$arr['ref'] = $this->symbols->buildRef($model);
			}

			return $arr;
		}

		/**
		 * Runs a callable inside a transaction, rolling back and rethrowing on failure.
		 *
		 * @param callable $fn Work to perform.
		 * @return void
		 */
		protected function transaction(callable $fn) : void {
			$this->db->beginTransaction();

			try {
				$fn();
				$this->db->commit();
			} catch (\Throwable $ex) {
				$this->db->rollBack();

				throw $ex;
			}

			return;
		}
	}
