<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbQueryTypes;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Writes every documentation record back out as a bundle: the inverse of DocBundleImporter, using the same natural
	 * keys (version labels, module paths, symbol refs, `mode/slug` page refs). Exporting, resetting, and importing the
	 * result reproduces the database.
	 *
	 * @package Zibings
	 */
	class DocBundleExporter extends StoicDbClass {
		protected DocVersions $versions;
		protected DocModules $modules;
		protected DocSymbols $symbols;
		protected DocPages $pages;
		protected DocChanges $changes;
		protected DocCourses $courses;
		protected DocContextOptions $contextOptions;

		/** @var array<int, string> */
		protected array $labels = [];


		/**
		 * Initializes the repositories.
		 *
		 * @return void
		 */
		protected function __initialize() : void {
			$this->versions       = new DocVersions($this->db, $this->log);
			$this->modules        = new DocModules($this->db, $this->log);
			$this->symbols        = new DocSymbols($this->db, $this->log);
			$this->pages          = new DocPages($this->db, $this->log);
			$this->changes        = new DocChanges($this->db, $this->log);
			$this->courses        = new DocCourses($this->db, $this->log);
			$this->contextOptions = new DocContextOptions($this->db, $this->log);

			return;
		}

		/**
		 * Builds the bundle.
		 *
		 * @return array
		 */
		public function export() : array {
			$this->labels = [];

			foreach ($this->versions->getAll() as $ver) {
				$this->labels[$ver->id] = $ver->label;
			}

			return [
				'contextOptions' => $this->exportContextOptions(),
				'versions'       => $this->exportVersions(),
				'modules'        => $this->exportModules(),
				'pages'          => $this->exportPages(),
				'changes'        => $this->exportChanges(),
				'courses'        => $this->exportCourses(),
			];
		}


		protected function label(?int $versionId) : ?string {
			return $versionId === null ? null : ($this->labels[$versionId] ?? null);
		}

		protected function exportContextOptions() : array {
			return array_map(fn (DocContextOption $o) => [
				'kind'      => $o->kind,
				'key'       => $o->optionKey,
				'label'     => $o->label,
				'sortOrder' => $o->sortOrder,
				'default'   => $o->isDefault,
			], $this->contextOptions->getAll());
		}

		protected function exportVersions() : array {
			return array_map(fn (DocVersion $v) => [
				'tag'        => $v->tag,
				'label'      => $v->label,
				'sortKey'    => $v->sortKey,
				'releasedAt' => $v->releasedAt?->format('Y-m-d'),
				'latest'     => $v->isLatest,
				'supported'  => $v->isSupported,
			], $this->versions->getAll());
		}

		protected function exportModules() : array {
			$ret = [];

			foreach ($this->modules->getAll() as $module) {
				$byParent = [];

				foreach ($this->symbols->getByModule($module->id) as $symbol) {
					$byParent[$symbol->parentSymbolId ?? 0][] = $symbol;
				}

				$ret[] = [
					'path'      => $module->path,
					'summary'   => $module->summary,
					'sortOrder' => $module->sortOrder,
					'symbols'   => $this->exportSymbols($byParent, 0),
				];
			}

			return $ret;
		}

		protected function exportSymbols(array $byParent, int $parentId) : array {
			$ret = [];

			foreach ($byParent[$parentId] ?? [] as $symbol) {
				$entry = [
					'name'      => $symbol->name,
					'kind'      => $symbol->kind,
					'sortOrder' => $symbol->sortOrder,
					'versions'  => array_map(fn (DocSymbolVersion $sv) => $this->exportContract($sv), $this->symbols->getVersions($symbol->id)),
				];

				$children = $this->exportSymbols($byParent, $symbol->id);

				if (count($children) > 0) {
					$entry['children'] = $children;
				}

				$ret[] = $entry;
			}

			return $ret;
		}

		protected function exportContract(DocSymbolVersion $sv) : array {
			$entry = [
				'introduced' => $this->label($sv->introducedVersionId),
				'removed'    => $this->label($sv->removedVersionId),
				'signature'  => $sv->signature,
				'summary'    => $sv->summary,
				'status'     => $sv->status,
				'params'     => $sv->getParams(),
				'returns'    => $sv->getReturns(),
				'throws'     => $sv->getThrows(),
			];

			if ($sv->sourcePath !== null) {
				$entry['sourcePath'] = $sv->sourcePath;
			}

			if ($sv->sourceLine !== null) {
				$entry['sourceLine'] = $sv->sourceLine;
			}

			return $entry;
		}

		protected function exportPages() : array {
			$ret   = [];
			$model = new DocPage($this->db, $this->log);
			$sql   = $model->generateClassQuery(BaseDbQueryTypes::SELECT, false) . " ORDER BY FIELD(`Mode`, 'reference', 'do', 'explain', 'learn'), `Slug` ASC, `IntroducedVersionID` ASC";

			$this->tryPdoExcept(function () use (&$ret, $sql) {
				$stmt = $this->db->query($sql);

				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$page = DocPage::fromArray($row, $this->db, $this->log);

					$ret[] = [
						'mode'       => $page->mode,
						'slug'       => $page->slug,
						'title'      => $page->title,
						'summary'    => $page->summary,
						'body'       => $page->body,
						'introduced' => $this->label($page->introducedVersionId),
						'removed'    => $this->label($page->removedVersionId),
						'minutes'    => $page->minutes,
						'symbols'    => array_map(fn ($link) => ['ref' => $this->symbols->buildRef($link['symbol']), 'role' => $link['role']], $this->pages->getSymbols($page->id)),
						'samples'    => array_map(fn (DocCodeSample $s) => [
							'key'          => $s->sampleKey,
							'title'        => $s->title,
							'language'     => $s->language,
							'variant'      => $s->variant,
							'code'         => $s->code,
							'tested'       => $s->isTested,
							'lastTestPass' => $this->label($s->lastTestPassVersionId),
						], $this->pages->getSamples($page->id)),
					];
				}

				return;
			}, "Failed to export pages");

			return $ret;
		}

		protected function exportChanges() : array {
			$ret = [];

			foreach ($this->versions->getAll() as $version) {
				foreach ($this->changes->getForVersion($version->id) as $change) {
					$ret[] = [
						'version'    => $version->label,
						'kind'       => $change->kind,
						'title'      => $change->title,
						'why'        => $change->why,
						'rfcUrl'     => $change->rfcUrl,
						'beforeCode' => $change->beforeCode,
						'afterCode'  => $change->afterCode,
						'codemodCmd' => $change->codemodCmd,
						'sortOrder'  => $change->sortOrder,
						'symbols'    => array_map(fn (DocSymbol $s) => $this->symbols->buildRef($s), $this->changes->getSymbols($change->id)),
					];
				}
			}

			return $ret;
		}

		protected function exportCourses() : array {
			$ret = [];

			foreach ($this->courses->getAll() as $course) {
				$lessons = [];

				foreach ($this->courses->getLessons($course->id) as $entry) {
					$lessons[] = [
						'page'    => "{$entry['page']->mode}/{$entry['page']->slug}",
						'ordinal' => $entry['lesson']->ordinal,
						'steps'   => array_map(fn (DocLessonStep $s) => [
							'ordinal'        => $s->ordinal,
							'prompt'         => $s->prompt,
							'hint'           => $s->hint,
							'answer'         => $s->answer,
							'expectedOutput' => $s->expectedOutput,
						], $this->courses->getSteps($entry['lesson']->id)),
					];
				}

				$ret[] = [
					'slug'      => $course->slug,
					'title'     => $course->title,
					'summary'   => $course->summary,
					'sortOrder' => $course->sortOrder,
					'lessons'   => $lessons,
				];
			}

			return $ret;
		}
	}
