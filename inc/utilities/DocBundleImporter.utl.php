<?php

	namespace Zibings;

	use Stoic\Pdo\StoicDbClass;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Imports a documentation bundle (decoded JSON) into the Doc* tables. The bundle is the interchange format every
	 * importer targets (fixtures, OpenAPI, source extractors), so it references records by natural keys, never by
	 * database identifiers:
	 *
	 *   versions       by label            ("v4.2")
	 *   modules        by path             ("tessel/query")
	 *   symbols        by ref              ("tessel/query#createQuery", "tessel/query#QueryOptions.staleTime")
	 *   pages          by mode/slug        ("do/prefetch-on-hover")
	 *   context options by kind + key      ("language" / "ts")
	 *
	 * Top-level keys: contextOptions, versions, modules (each with nested symbols, each with nested children and
	 * versions), pages (with symbols and samples), changes (with symbols), courses (with lessons and steps).
	 *
	 * Importing is an upsert on those natural keys, so re-importing a bundle updates rather than duplicates.
	 *
	 * @package Zibings
	 */
	class DocBundleImporter extends StoicDbClass {
		/**
		 * Counts of records created or updated during the last import, keyed by record type.
		 *
		 * @var array
		 */
		public array $counts = [];


		/**
		 * Map of version label to identifier, built during import.
		 *
		 * @var array
		 */
		protected array $versionIds = [];
		/**
		 * Map of `mode/slug` to page identifier, built during import.
		 *
		 * @var array
		 */
		protected array $pageIds = [];
		/**
		 * Internal DocSymbols repository.
		 *
		 * @var DocSymbols
		 */
		protected DocSymbols $symbols;


		/**
		 * Initializes the internal repositories.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->symbols = new DocSymbols($this->db, $this->log);

			return;
		}

		/**
		 * Imports a bundle from a JSON file.
		 *
		 * @param string $path Path to the JSON file.
		 * @return ReturnHelper
		 */
		public function importFile(string $path) : ReturnHelper {
			$ret = new ReturnHelper();
			$ret->makeBad();

			if (!file_exists($path)) {
				$ret->addMessage("Bundle file not found: {$path}");

				return $ret;
			}

			$bundle = json_decode(file_get_contents($path), true);

			if (!is_array($bundle)) {
				$ret->addMessage("Bundle file is not valid JSON: {$path} (" . json_last_error_msg() . ")");

				return $ret;
			}

			return $this->importBundle($bundle);
		}

		/**
		 * Imports a decoded bundle inside a transaction. Any failure rolls the whole import back.
		 *
		 * @param array $bundle Decoded bundle.
		 * @return ReturnHelper
		 */
		public function importBundle(array $bundle) : ReturnHelper {
			$ret = new ReturnHelper();
			$ret->makeBad();

			$this->counts     = [];
			$this->versionIds = [];
			$this->pageIds    = [];

			try {
				$this->db->beginTransaction();

				foreach ($bundle['contextOptions'] ?? [] as $data) {
					$this->importContextOption($data);
				}

				foreach ($bundle['versions'] ?? [] as $data) {
					$this->importVersion($data);
				}

				foreach ($this->getExistingVersionIds() as $label => $id) {
					$this->versionIds[$label] = $id;
				}

				foreach ($bundle['modules'] ?? [] as $data) {
					$this->importModule($data);
				}

				foreach ($bundle['pages'] ?? [] as $data) {
					$this->importPage($data);
				}

				foreach ($bundle['changes'] ?? [] as $data) {
					$this->importChange($data);
				}

				foreach ($bundle['courses'] ?? [] as $data) {
					$this->importCourse($data);
				}

				$this->db->commit();
				$ret->makeGood();
				$ret->addResult($this->counts);
			} catch (\Throwable $ex) {
				$this->db->rollBack();
				$ret->addMessage($ex->getMessage());
				$this->log->error("Bundle import failed: {ERROR}", ['ERROR' => $ex]);
			}

			return $ret;
		}

		/**
		 * Deletes every documentation record, in foreign-key order.
		 *
		 * @return ReturnHelper
		 */
		public function reset() : ReturnHelper {
			$ret = new ReturnHelper();
			$ret->makeBad();

			$tables = [
				'DocLessonStep', 'DocLesson', 'DocCourse',
				'DocChangeSymbol', 'DocChange',
				'DocCodeSample', 'DocPageSymbol', 'DocPage',
				'DocSymbolVersion', 'DocSymbol', 'DocModule',
				'DocVersion', 'DocContextOption'
			];

			try {
				$this->db->beginTransaction();

				foreach ($tables as $table) {
					$this->db->exec("DELETE FROM `{$table}`");
				}

				$this->db->commit();
				$ret->makeGood();
			} catch (\Throwable $ex) {
				$this->db->rollBack();
				$ret->addMessage($ex->getMessage());
			}

			return $ret;
		}


		/**
		 * Increments the count for a record type.
		 *
		 * @param string $type Record type.
		 * @return void
		 */
		protected function count(string $type) : void {
			$this->counts[$type] = ($this->counts[$type] ?? 0) + 1;

			return;
		}

		/**
		 * Throws unless the ReturnHelper is good.
		 *
		 * @param ReturnHelper $result Result to check.
		 * @param string $context What was being saved.
		 * @throws \RuntimeException
		 * @return void
		 */
		protected function ensure(ReturnHelper $result, string $context) : void {
			if ($result->isBad()) {
				$messages = $result->hasMessages() ? implode('; ', $result->getMessages()) : 'unknown error';

				throw new \RuntimeException("Failed to save {$context}: {$messages}");
			}

			return;
		}

		/**
		 * Returns a map of every existing version label to identifier.
		 *
		 * @return array
		 */
		protected function getExistingVersionIds() : array {
			$ret = [];

			foreach ((new DocVersions($this->db, $this->log))->getAll() as $ver) {
				$ret[$ver->label] = $ver->id;
			}

			return $ret;
		}

		/**
		 * Resolves a version label to an identifier, throwing when unknown.
		 *
		 * @param mixed $label Version label, or null.
		 * @param string $context What referenced the version.
		 * @throws \RuntimeException
		 * @return null|int
		 */
		protected function versionId(mixed $label, string $context) : ?int {
			if ($label === null || $label === '') {
				return null;
			}

			if (!array_key_exists($label, $this->versionIds)) {
				throw new \RuntimeException("Unknown version '{$label}' referenced by {$context}");
			}

			return $this->versionIds[$label];
		}

		/**
		 * Resolves a symbol reference to an identifier, throwing when unknown.
		 *
		 * @param string $ref Symbol reference.
		 * @param string $context What referenced the symbol.
		 * @throws \RuntimeException
		 * @return int
		 */
		protected function symbolId(string $ref, string $context) : int {
			$symbol = $this->symbols->findByRef($ref);

			if ($symbol->id < 1) {
				throw new \RuntimeException("Unknown symbol '{$ref}' referenced by {$context}");
			}

			return $symbol->id;
		}

		/**
		 * Resolves a `mode/slug` page reference to an identifier, throwing when unknown.
		 *
		 * @param string $ref Page reference.
		 * @param string $context What referenced the page.
		 * @throws \RuntimeException
		 * @return int
		 */
		protected function pageId(string $ref, string $context) : int {
			if (!array_key_exists($ref, $this->pageIds)) {
				throw new \RuntimeException("Unknown page '{$ref}' referenced by {$context}");
			}

			return $this->pageIds[$ref];
		}

		/**
		 * Upserts a context option.
		 *
		 * @param array $data Option data: kind, key, label, sortOrder, default.
		 * @return void
		 */
		protected function importContextOption(array $data) : void {
			$opt = DocContextOption::fromKey($data['kind'] ?? '', $data['key'] ?? '', $this->db, $this->log);
			$isNew = $opt->id < 1;

			$opt->kind      = $data['kind'] ?? '';
			$opt->optionKey = $data['key'] ?? '';
			$opt->label     = $data['label'] ?? $opt->optionKey;
			$opt->sortOrder = intval($data['sortOrder'] ?? 0);
			$opt->isDefault = boolval($data['default'] ?? false);

			$this->ensure($isNew ? $opt->create() : $opt->update(), "context option {$opt->kind}/{$opt->optionKey}");
			$this->count('contextOptions');

			return;
		}

		/**
		 * Upserts a version.
		 *
		 * @param array $data Version data: tag, label, sortKey, releasedAt, latest, supported.
		 * @return void
		 */
		protected function importVersion(array $data) : void {
			$ver   = DocVersion::fromLabel($data['label'] ?? '', $this->db, $this->log);
			$isNew = $ver->id < 1;

			$ver->tag         = $data['tag'] ?? '';
			$ver->label       = $data['label'] ?? '';
			$ver->sortKey     = intval($data['sortKey'] ?? 0);
			$ver->releasedAt  = !empty($data['releasedAt']) ? new \DateTimeImmutable($data['releasedAt'], new \DateTimeZone('UTC')) : null;
			$ver->isLatest    = boolval($data['latest'] ?? false);
			$ver->isSupported = boolval($data['supported'] ?? true);

			$this->ensure($isNew ? $ver->create() : $ver->update(), "version {$ver->label}");
			$this->count('versions');

			return;
		}

		/**
		 * Upserts a module and its symbols.
		 *
		 * @param array $data Module data: path, summary, sortOrder, symbols.
		 * @return void
		 */
		protected function importModule(array $data) : void {
			$mod   = DocModule::fromPath($data['path'] ?? '', $this->db, $this->log);
			$isNew = $mod->id < 1;

			$mod->path      = $data['path'] ?? '';
			$mod->summary   = $data['summary'] ?? '';
			$mod->sortOrder = intval($data['sortOrder'] ?? 0);

			$this->ensure($isNew ? $mod->create() : $mod->update(), "module {$mod->path}");
			$this->count('modules');

			$order = 0;

			foreach ($data['symbols'] ?? [] as $symData) {
				$this->importSymbol($mod, null, $symData, ++$order);
			}

			return;
		}

		/**
		 * Upserts a symbol, its contract rows, and its children.
		 *
		 * @param DocModule $module Owning module.
		 * @param null|int $parentId Parent symbol identifier, or null.
		 * @param array $data Symbol data: name, kind, sortOrder, versions, children.
		 * @param int $order Position among siblings when sortOrder is not given.
		 * @return void
		 */
		protected function importSymbol(DocModule $module, ?int $parentId, array $data, int $order) : void {
			$sym   = DocSymbol::fromName($module->id, $data['name'] ?? '', $parentId, $this->db, $this->log);
			$isNew = $sym->id < 1;

			$sym->moduleId       = $module->id;
			$sym->parentSymbolId = $parentId;
			$sym->name           = $data['name'] ?? '';
			$sym->kind           = $data['kind'] ?? '';
			$sym->sortOrder      = intval($data['sortOrder'] ?? $order);

			$this->ensure($isNew ? $sym->create() : $sym->update(), "symbol {$module->path}#{$sym->name}");
			$this->count('symbols');

			$existing = [];

			foreach ($this->symbols->getVersions($sym->id) as $row) {
				$existing[$row->introducedVersionId] = $row;
			}

			foreach ($data['versions'] ?? [] as $verData) {
				$introducedId = $this->versionId($verData['introduced'] ?? null, "symbol {$sym->name}");

				if ($introducedId === null) {
					throw new \RuntimeException("Symbol {$sym->name} has a contract without an introduced version");
				}

				$row      = $existing[$introducedId] ?? new DocSymbolVersion($this->db, $this->log);
				$rowIsNew = $row->id < 1;

				$row->symbolId            = $sym->id;
				$row->introducedVersionId = $introducedId;
				$row->removedVersionId    = $this->versionId($verData['removed'] ?? null, "symbol {$sym->name}");
				$row->signature           = $verData['signature'] ?? '';
				$row->summary             = $verData['summary'] ?? '';
				$row->status              = $verData['status'] ?? DocSymbolStatuses::STABLE;
				$row->sourcePath          = $verData['sourcePath'] ?? null;
				$row->sourceLine          = isset($verData['sourceLine']) ? intval($verData['sourceLine']) : null;
				$row->setParams($verData['params'] ?? []);
				$row->setReturns($verData['returns'] ?? []);
				$row->setThrows($verData['throws'] ?? []);

				$this->ensure($rowIsNew ? $row->create() : $row->update(), "contract for {$sym->name} at {$verData['introduced']}");
				$this->count('symbolVersions');
			}

			$childOrder = 0;

			foreach ($data['children'] ?? [] as $childData) {
				$this->importSymbol($module, $sym->id, $childData, ++$childOrder);
			}

			return;
		}

		/**
		 * Upserts a page, its symbol links, and its code samples.
		 *
		 * @param array $data Page data: mode, slug, title, summary, body, introduced, removed, minutes, symbols, samples.
		 * @return void
		 */
		protected function importPage(array $data) : void {
			$mode         = $data['mode'] ?? '';
			$slug         = $data['slug'] ?? '';
			$introducedId = $this->versionId($data['introduced'] ?? null, "page {$mode}/{$slug}");

			if ($introducedId === null) {
				throw new \RuntimeException("Page {$mode}/{$slug} has no introduced version");
			}

			$page  = $this->findPage($mode, $slug, $introducedId);
			$isNew = $page->id < 1;

			$page->mode                = $mode;
			$page->slug                = $slug;
			$page->title               = $data['title'] ?? '';
			$page->summary             = $data['summary'] ?? '';
			$page->body                = $data['body'] ?? '';
			$page->introducedVersionId = $introducedId;
			$page->removedVersionId    = $this->versionId($data['removed'] ?? null, "page {$mode}/{$slug}");
			$page->minutes             = isset($data['minutes']) ? intval($data['minutes']) : null;

			$this->ensure($isNew ? $page->create() : $page->update(), "page {$mode}/{$slug}");
			$this->count('pages');

			$this->pageIds["{$mode}/{$slug}"] = $page->id;

			foreach ($data['symbols'] ?? [] as $linkData) {
				$link           = new DocPageSymbol($this->db, $this->log);
				$link->pageId   = $page->id;
				$link->symbolId = $this->symbolId($linkData['ref'] ?? '', "page {$mode}/{$slug}");
				$link->role     = $linkData['role'] ?? DocPageSymbolRoles::MENTIONS;

				if ($link->read()->isGood()) {
					$link->role = $linkData['role'] ?? DocPageSymbolRoles::MENTIONS;
					$this->ensure($link->update(), "page symbol link on {$mode}/{$slug}");
				} else {
					$this->ensure($link->create(), "page symbol link on {$mode}/{$slug}");
				}

				$this->count('pageSymbols');
			}

			$existing = [];

			foreach ((new DocPages($this->db, $this->log))->getSamples($page->id) as $sample) {
				$existing["{$sample->sampleKey}|{$sample->language}|" . ($sample->variant ?? '')] = $sample;
			}

			foreach ($data['samples'] ?? [] as $sampleData) {
				$key      = ($sampleData['key'] ?? '') . '|' . ($sampleData['language'] ?? '') . '|' . ($sampleData['variant'] ?? '');
				$sample   = $existing[$key] ?? new DocCodeSample($this->db, $this->log);
				$smpIsNew = $sample->id < 1;

				$sample->pageId                = $page->id;
				$sample->sampleKey             = $sampleData['key'] ?? '';
				$sample->title                 = $sampleData['title'] ?? null;
				$sample->language              = $sampleData['language'] ?? '';
				$sample->variant               = $sampleData['variant'] ?? null;
				$sample->code                  = $sampleData['code'] ?? '';
				$sample->isTested              = boolval($sampleData['tested'] ?? false);
				$sample->lastTestPassVersionId = $this->versionId($sampleData['lastTestPass'] ?? null, "sample {$sample->sampleKey} on {$mode}/{$slug}");

				$this->ensure($smpIsNew ? $sample->create() : $sample->update(), "sample {$sample->sampleKey} on {$mode}/{$slug}");
				$this->count('codeSamples');
			}

			return;
		}

		/**
		 * Finds an existing page revision by mode, slug, and introduced version, or returns a blank page.
		 *
		 * @param string $mode Reader mode.
		 * @param string $slug Page slug.
		 * @param int $introducedId Identifier of the introduced version.
		 * @return DocPage
		 */
		protected function findPage(string $mode, string $slug, int $introducedId) : DocPage {
			$ret = new DocPage($this->db, $this->log);

			$stmt = $this->db->prepare("SELECT `ID` FROM `DocPage` WHERE `Mode` = :mode AND `Slug` = :slug AND `IntroducedVersionID` = :versionId LIMIT 1");
			$stmt->bindValue(':mode', $mode);
			$stmt->bindValue(':slug', $slug);
			$stmt->bindValue(':versionId', $introducedId, \PDO::PARAM_INT);
			$stmt->execute();

			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			if ($row !== false) {
				$ret = DocPage::fromId(intval($row['ID']), $this->db, $this->log);
			}

			return $ret;
		}

		/**
		 * Upserts a change and its symbol links. Changes are matched by version and title.
		 *
		 * @param array $data Change data: version, kind, title, why, rfcUrl, beforeCode, afterCode, codemodCmd, sortOrder, symbols.
		 * @return void
		 */
		protected function importChange(array $data) : void {
			$title     = $data['title'] ?? '';
			$versionId = $this->versionId($data['version'] ?? null, "change '{$title}'");

			if ($versionId === null) {
				throw new \RuntimeException("Change '{$title}' has no version");
			}

			$change = new DocChange($this->db, $this->log);
			$stmt   = $this->db->prepare("SELECT `ID` FROM `DocChange` WHERE `VersionID` = :versionId AND `Title` = :title LIMIT 1");
			$stmt->bindValue(':versionId', $versionId, \PDO::PARAM_INT);
			$stmt->bindValue(':title', $title);
			$stmt->execute();

			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			if ($row !== false) {
				$change = DocChange::fromId(intval($row['ID']), $this->db, $this->log);
			}

			$isNew = $change->id < 1;

			$change->versionId  = $versionId;
			$change->kind       = $data['kind'] ?? '';
			$change->title      = $title;
			$change->why        = $data['why'] ?? '';
			$change->rfcUrl     = $data['rfcUrl'] ?? null;
			$change->beforeCode = $data['beforeCode'] ?? null;
			$change->afterCode  = $data['afterCode'] ?? null;
			$change->codemodCmd = $data['codemodCmd'] ?? null;
			$change->sortOrder  = intval($data['sortOrder'] ?? 0);

			$this->ensure($isNew ? $change->create() : $change->update(), "change '{$title}'");
			$this->count('changes');

			foreach ($data['symbols'] ?? [] as $ref) {
				$link           = new DocChangeSymbol($this->db, $this->log);
				$link->changeId = $change->id;
				$link->symbolId = $this->symbolId($ref, "change '{$title}'");

				if ($link->read()->isBad()) {
					$this->ensure($link->create(), "change symbol link on '{$title}'");
				}

				$this->count('changeSymbols');
			}

			return;
		}

		/**
		 * Upserts a course, its lessons, and their steps.
		 *
		 * @param array $data Course data: slug, title, summary, sortOrder, lessons.
		 * @return void
		 */
		protected function importCourse(array $data) : void {
			$course = DocCourse::fromSlug($data['slug'] ?? '', $this->db, $this->log);
			$isNew  = $course->id < 1;

			$course->slug      = $data['slug'] ?? '';
			$course->title     = $data['title'] ?? '';
			$course->summary   = $data['summary'] ?? '';
			$course->sortOrder = intval($data['sortOrder'] ?? 0);

			$this->ensure($isNew ? $course->create() : $course->update(), "course {$course->slug}");
			$this->count('courses');

			$courses  = new DocCourses($this->db, $this->log);
			$existing = [];

			foreach ($courses->getLessons($course->id) as $entry) {
				$existing[$entry['lesson']->ordinal] = $entry['lesson'];
			}

			$ordinal = 0;

			foreach ($data['lessons'] ?? [] as $lessonData) {
				$ordinal++;

				$lessonOrdinal = intval($lessonData['ordinal'] ?? $ordinal);
				$lesson        = $existing[$lessonOrdinal] ?? new DocLesson($this->db, $this->log);
				$lessonIsNew   = $lesson->id < 1;

				$lesson->courseId = $course->id;
				$lesson->pageId   = $this->pageId($lessonData['page'] ?? '', "course {$course->slug} lesson {$lessonOrdinal}");
				$lesson->ordinal  = $lessonOrdinal;

				$this->ensure($lessonIsNew ? $lesson->create() : $lesson->update(), "lesson {$lessonOrdinal} of {$course->slug}");
				$this->count('lessons');

				$existingSteps = [];

				foreach ($courses->getSteps($lesson->id) as $step) {
					$existingSteps[$step->ordinal] = $step;
				}

				$stepOrdinal = 0;

				foreach ($lessonData['steps'] ?? [] as $stepData) {
					$stepOrdinal++;

					$thisOrdinal = intval($stepData['ordinal'] ?? $stepOrdinal);
					$step        = $existingSteps[$thisOrdinal] ?? new DocLessonStep($this->db, $this->log);
					$stepIsNew   = $step->id < 1;

					$step->lessonId       = $lesson->id;
					$step->ordinal        = $thisOrdinal;
					$step->prompt         = $stepData['prompt'] ?? '';
					$step->hint           = $stepData['hint'] ?? null;
					$step->answer         = $stepData['answer'] ?? null;
					$step->expectedOutput = $stepData['expectedOutput'] ?? null;

					$this->ensure($stepIsNew ? $step->create() : $step->update(), "step {$thisOrdinal} of lesson {$lessonOrdinal} in {$course->slug}");
					$this->count('lessonSteps');
				}
			}

			return;
		}
	}
