<?php

	namespace Zibings;

	use AndyM84\Config\ConfigContainer;

	use Stoic\Log\Logger;
	use Stoic\Pdo\PdoHelper;
	use Stoic\Pdo\StoicDbClass;

	/**
	 * Read-side service behind the public docs API. One method per endpoint, each returning plain arrays ready for
	 * JSON output, or null when the requested record does not exist at the requested version. The API controller
	 * only parses the URL and maps null to a 404.
	 *
	 * Route paths follow the public site's URL scheme so both the manifest (build) and the API agree:
	 *   /{version}/reference/{module/path}/{Symbol.Name}
	 *   /{version}/{do|explain}/{slug}
	 *   /{version}/learn/{course}/{lesson-slug}
	 *   /upgrade/{from}/{to}
	 *
	 * @package Zibings
	 */
	class DocsReader extends StoicDbClass {
		const string LATEST_ALIAS = 'latest';


		/**
		 * Site settings, used for the library name and source-link pattern.
		 *
		 * @var null|ConfigContainer
		 */
		protected ?ConfigContainer $settings;
		/**
		 * Cache of every version keyed by identifier.
		 *
		 * @var null|DocVersion[]
		 */
		protected ?array $versionsById = null;
		protected DocVersions $versions;
		protected DocModules $modules;
		protected DocSymbols $symbols;
		protected DocPages $pages;
		protected DocChanges $changes;
		protected DocCourses $courses;
		protected DocContextOptions $contextOptions;


		/**
		 * Instantiates a new DocsReader object.
		 *
		 * @param PdoHelper $db PdoHelper instance for internal use.
		 * @param null|Logger $log Optional Logger instance for internal use.
		 * @param null|ConfigContainer $settings Optional site settings for library name and source links.
		 */
		public function __construct(PdoHelper $db, null|Logger $log = null, null|ConfigContainer $settings = null) {
			$this->settings = $settings;

			parent::__construct($db, $log);

			return;
		}

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
		 * Resolves a URL version segment (label, tag, or `latest`) to a version. Returns a blank version if unknown.
		 *
		 * @param string $segment Version segment from the URL.
		 * @return DocVersion
		 */
		public function resolveVersion(string $segment) : DocVersion {
			if (strtolower($segment) === self::LATEST_ALIAS) {
				return $this->versions->getLatest();
			}

			$ver = DocVersion::fromLabel($segment, $this->db, $this->log);

			if ($ver->id < 1) {
				$ver = DocVersion::fromTag($segment, $this->db, $this->log);
			}

			return $ver;
		}

		/**
		 * Everything the site shell needs on first load: versions, context options, and library identity.
		 *
		 * @return array
		 */
		public function site() : array {
			$latest  = $this->versions->getLatest();
			$options = ['language' => [], 'packageManager' => []];

			foreach ($this->contextOptions->getAll() as $opt) {
				$options[$opt->kind][] = [
					'key'       => $opt->optionKey,
					'label'     => $opt->label,
					'isDefault' => $opt->isDefault
				];
			}

			$siteUrl = trim(strval($this->setting(SettingsStrings::DOCS_SITE_URL, '')));

			return [
				'libraryName'    => $this->setting(SettingsStrings::DOCS_LIBRARY_NAME, 'Library'),
				'repoUrl'        => $this->setting(SettingsStrings::DOCS_REPO_URL, ''),
				'siteUrl'        => ($siteUrl === '' || $siteUrl === '<changeme>') ? null : rtrim($siteUrl, '/'),
				'latest'         => ($latest->id > 0) ? $latest->label : null,
				'versions'       => $this->versionList(),
				'contextOptions' => [
					'languages'       => $options[DocContextOptionKinds::LANGUAGE],
					'packageManagers' => $options[DocContextOptionKinds::PACKAGE_MANAGER]
				],
				'modes'          => DocPageModes::readerModes()
			];
		}

		/**
		 * All versions, oldest first.
		 *
		 * @return array
		 */
		public function versionList() : array {
			return array_map(fn (DocVersion $v) => $this->versionSummary($v), $this->versions->getAll());
		}

		/**
		 * The browse tree at a version.
		 *
		 * @param DocVersion $version The version.
		 * @param bool $includeRemoved Whether removed symbols are included.
		 * @return array
		 */
		public function tree(DocVersion $version, bool $includeRemoved = true) : array {
			$modules = [];

			foreach ($this->symbols->getTreeAtVersion($version->id, $includeRemoved) as $entry) {
				$module    = $entry['module'];
				$modules[] = [
					'path'    => $module->path,
					'summary' => $module->summary,
					'count'   => count($entry['symbols']),
					'symbols' => array_map(fn (DocSymbolNode $n) => $this->nodeToArray($n, $module->path, $version, true), $entry['symbols'])
				];
			}

			return [
				'version' => $this->versionSummary($version),
				'modules' => $modules
			];
		}

		/**
		 * A symbol page at a version, or null if the symbol does not exist or had not been introduced yet.
		 *
		 * @param DocVersion $version The version.
		 * @param string $modulePath Module path.
		 * @param string $name Symbol name, dotted for nested symbols.
		 * @return null|array
		 */
		public function symbol(DocVersion $version, string $modulePath, string $name) : ?array {
			$symbol = $this->symbols->findByRef("{$modulePath}#{$name}");

			if ($symbol->id < 1) {
				return null;
			}

			$node = $this->symbols->getNodeAtVersion($symbol->id, $version->id);

			if ($node === null) {
				return null;
			}

			$module   = DocModule::fromId($symbol->moduleId, $this->db, $this->log);
			$page     = $this->pages->getReferencePageForSymbol($symbol->id, $version->id);
			$samples  = ($page->id > 0) ? $this->pages->getSamples($page->id) : [];
			$covered  = [];
			$changes  = [];
			$siblings = [];

			foreach ($this->pages->getPagesForSymbol($symbol->id, $version->id) as $link) {
				if ($link['page']->id === $page->id || $link['page']->mode === DocPageModes::HOME) {
					continue;
				}

				$covered[] = $this->pageSummary($link['page'], $version) + ['role' => $link['role']];
			}

			$mentions = [];

			if ($page->id > 0) {
				foreach ($this->pages->getSymbols($page->id) as $link) {
					if ($link['symbol']->id === $symbol->id) {
						continue;
					}

					$mentionNode = $this->symbols->getNodeAtVersion($link['symbol']->id, $version->id);

					if ($mentionNode !== null) {
						$mentionModule = DocModule::fromId($link['symbol']->moduleId, $this->db, $this->log);
						$mentions[]    = $this->nodeToArray($mentionNode, $mentionModule->path, $version, false) + ['role' => $link['role']];
					}
				}
			}

			foreach ($this->changes->getChangesForSymbol($symbol->id) as $change) {
				$changes[] = $this->changeSummary($change);
			}

			$root = $symbol;

			while ($root->parentSymbolId !== null && $root->parentSymbolId > 0) {
				$parent = DocSymbol::fromId($root->parentSymbolId, $this->db, $this->log);

				if ($parent->id < 1) {
					break;
				}

				$root = $parent;
			}

			foreach ($this->symbols->getSiblingsAtVersion($root->id, $version->id) as $sibling) {
				$siblings[] = $this->nodeToArray($sibling, $module->path, $version, false);
			}

			$tested  = 0;
			$passing = 0;

			foreach ($samples as $sample) {
				if ($sample->isTested) {
					$tested++;

					if ($sample->lastTestPassVersionId === $version->id) {
						$passing++;
					}
				}
			}

			return [
				'version'       => $this->versionSummary($version),
				'module'        => ['path' => $module->path, 'summary' => $module->summary],
				'breadcrumb'    => array_merge(explode('/', $module->path), explode('.', $name)),
				'symbol'        => $this->nodeToArray($node, $module->path, $version, true),
				'relatedTypes'  => $this->relatedTypes($node, $module, $version),
				'mentions'      => $mentions,
				'page'          => ($page->id > 0) ? $this->pageBody($page, $version) : null,
				'samples'       => array_map(fn (DocCodeSample $s) => $this->sampleToArray($s), $samples),
				'testedSamples' => ['total' => $tested, 'passingAtVersion' => $passing],
				'alsoCoveredIn' => $covered,
				'changes'       => $changes,
				'siblings'      => $siblings
			];
		}

		/**
		 * A prose page at a version, or null if none is valid there.
		 *
		 * @param DocVersion $version The version.
		 * @param string $mode Reader mode.
		 * @param string $slug Page slug.
		 * @return null|array
		 */
		public function page(DocVersion $version, string $mode, string $slug) : ?array {
			if (!DocPageModes::isValid($mode)) {
				return null;
			}

			$page = $this->pages->getAtVersion($mode, $slug, $version->id);

			if ($page->id < 1) {
				return null;
			}

			$symbols = [];

			foreach ($this->pages->getSymbols($page->id) as $link) {
				$node = $this->symbols->getNodeAtVersion($link['symbol']->id, $version->id);

				if ($node === null) {
					continue;
				}

				$module    = DocModule::fromId($link['symbol']->moduleId, $this->db, $this->log);
				$symbols[] = $this->nodeToArray($node, $module->path, $version, true) + ['role' => $link['role']];
			}

			$ret = [
				'version' => $this->versionSummary($version),
				'page'    => $this->pageBody($page, $version),
				'symbols' => $symbols,
				'samples' => array_map(fn (DocCodeSample $s) => $this->sampleToArray($s), $this->pages->getSamples($page->id)),
				'course'  => null
			];

			if ($mode === DocPageModes::LEARN) {
				$ret['course'] = $this->courseSpine($page, $version);
			}

			return $ret;
		}

		/**
		 * The upgrade view between two versions.
		 *
		 * @param DocVersion $from Earlier version.
		 * @param DocVersion $to Later version.
		 * @throws \InvalidArgumentException When `from` is not older than `to`.
		 * @return array
		 */
		public function diff(DocVersion $from, DocVersion $to) : array {
			if ($from->sortKey >= $to->sortKey) {
				throw new \InvalidArgumentException("The 'from' version must be older than the 'to' version");
			}

			$groups = [];

			foreach (DocChangeKinds::all() as $kind) {
				$groups[$kind] = [];
			}

			foreach ($this->changes->getBetween($from->id, $to->id) as $change) {
				$entry = $this->changeSummary($change) + [
					'why'           => $change->why,
					'rfcUrl'        => $change->rfcUrl,
					'beforeCode'    => $change->beforeCode,
					'afterCode'     => $change->afterCode,
					'symbols'       => [],
					'contractDiffs' => []
				];

				foreach ($this->changes->getSymbols($change->id) as $symbol) {
					$module = DocModule::fromId($symbol->moduleId, $this->db, $this->log);
					$ref    = $this->symbols->buildRef($symbol);
					$name   = substr($ref, strpos($ref, '#') + 1);

					$entry['symbols'][] = [
						'ref'   => $ref,
						'name'  => $name,
						'kind'  => $symbol->kind,
						'route' => $this->symbolRoute($to, $module->path, $name)
					];

					$contractDiff = $this->changes->getContractDiff($symbol->id, $from->id, $to->id);

					if ($contractDiff->hasChanges()) {
						$entry['contractDiffs'][] = ['ref' => $ref, 'name' => $name] + $this->contractDiffToArray($contractDiff, $from, $to);
					}
				}

				$groups[$change->kind][] = $entry;
			}

			$grouped = [];

			foreach ($groups as $kind => $entries) {
				$grouped[] = ['kind' => $kind, 'count' => count($entries), 'changes' => $entries];
			}

			return [
				'from'     => $this->versionSummary($from),
				'to'       => $this->versionSummary($to),
				'versions' => array_map(fn (DocVersion $v) => $this->versionSummary($v), $this->versions->getRange($from->id, $to->id)),
				'total'    => array_sum(array_map(fn ($g) => $g['count'], $grouped)),
				'groups'   => $grouped,
				'route'    => $this->upgradeRoute($from, $to)
			];
		}

		/**
		 * Every route at a version plus the records the search index is built from.
		 *
		 * @param DocVersion $version The version.
		 * @return array
		 */
		public function manifest(DocVersion $version) : array {
			$routes  = [];
			$records = [];

			foreach ($this->symbols->getTreeAtVersion($version->id, true) as $entry) {
				$modulePath = $entry['module']->path;

				foreach ($entry['symbols'] as $node) {
					$this->collectSymbolRecords($node, $modulePath, $version, $routes, $records);
				}
			}

			foreach ([DocPageModes::DO, DocPageModes::EXPLAIN, DocPageModes::LEARN] as $mode) {
				foreach ($this->pages->getByMode($mode, $version->id) as $page) {
					$summary   = $this->pageSummary($page, $version);
					$routes[]  = $summary['route'];
					$records[] = [
						'type'    => 'page',
						'mode'    => $page->mode,
						'slug'    => $page->slug,
						'title'   => $page->title,
						'summary' => $page->summary,
						'minutes' => $page->minutes,
						'route'   => $summary['route']
					];
				}
			}

			foreach ($this->versions->getAll() as $other) {
				if ($other->sortKey >= $version->sortKey) {
					continue;
				}

				$routes[] = $this->upgradeRoute($other, $version);
			}

			$previous = $this->previousVersion($version);

			foreach ($this->versions->getAll() as $shipped) {
				if ($shipped->sortKey > $version->sortKey) {
					continue;
				}

				$shippedPrevious = $this->previousVersion($shipped);

				foreach ($this->changes->getForVersion($shipped->id) as $change) {
					$records[] = $this->changeSummary($change) + [
						'type'  => 'change',
						'why'   => $change->why,
						'route' => ($shippedPrevious !== null) ? $this->upgradeRoute($shippedPrevious, $shipped) : null
					];
				}
			}

			return [
				'version'       => $this->versionSummary($version),
				'previous'      => ($previous !== null) ? $this->versionSummary($previous) : null,
				'routes'        => array_values(array_unique($routes)),
				'searchRecords' => $records,
				'courses'       => $this->courseList($version)
			];
		}

		/**
		 * Lists every course with its lessons that exist at a version, for the Learn index.
		 *
		 * @param DocVersion $version The version.
		 * @return array
		 */
		protected function courseList(DocVersion $version) : array {
			$ret = [];

			foreach ($this->courses->getAll() as $course) {
				$lessons = [];
				$total   = 0;

				foreach ($this->courses->getLessons($course->id) as $entry) {
					$page = $entry['page'];

					if (($this->versionById($page->introducedVersionId)?->sortKey ?? 0) > $version->sortKey) {
						continue;
					}

					if ($page->removedVersionId !== null && ($this->versionById($page->removedVersionId)?->sortKey ?? PHP_INT_MAX) <= $version->sortKey) {
						continue;
					}

					$total    += $page->minutes ?? 0;
					$lessons[] = [
						'ordinal' => $entry['lesson']->ordinal,
						'title'   => $page->title,
						'slug'    => $page->slug,
						'minutes' => $page->minutes,
						'route'   => $this->learnRoute($version, $course->slug, $page->slug)
					];
				}

				if (count($lessons) < 1) {
					continue;
				}

				$ret[] = [
					'slug'         => $course->slug,
					'title'        => $course->title,
					'summary'      => $course->summary,
					'totalMinutes' => $total,
					'lessonCount'  => count($lessons),
					'lessons'      => $lessons
				];
			}

			return $ret;
		}


		/**
		 * Adds a symbol node and its children to the manifest routes and search records.
		 *
		 * @param DocSymbolNode $node Node to add.
		 * @param string $modulePath Module path.
		 * @param DocVersion $version The version.
		 * @param array $routes Routes accumulator.
		 * @param array $records Records accumulator.
		 * @return void
		 */
		protected function collectSymbolRecords(DocSymbolNode $node, string $modulePath, DocVersion $version, array &$routes, array &$records) : void {
			$arr       = $this->nodeToArray($node, $modulePath, $version, false);
			$routes[]  = $arr['route'];
			$record    = [
				'type'         => 'symbol',
				'ref'          => $arr['ref'],
				'name'         => $arr['name'],
				'kind'         => $arr['kind'],
				'module'       => $modulePath,
				'summary'      => $arr['contract']['summary'],
				'signature'    => $arr['contract']['signature'],
				'status'       => $arr['contract']['status'],
				'state'        => $arr['state'],
				'sinceLabel'   => $arr['sinceLabel'],
				'removedLabel' => $arr['removedLabel'],
				'replacedBy'   => null,
				'route'        => $arr['route']
			];

			if ($node->state === DocSymbolStates::REMOVED) {
				foreach ($this->changes->getChangesForSymbol($node->symbol->id) as $change) {
					if ($change->versionId === $node->removedVersionId) {
						$record['replacedBy'] = $change->title;

						break;
					}
				}
			}

			$records[] = $record;

			foreach ($node->children as $child) {
				$this->collectSymbolRecords($child, $modulePath, $version, $routes, $records);
			}

			return;
		}

		/**
		 * Resolves the sibling type symbols named by a contract's parameter types, so the contract pane can flatten
		 * their fields (e.g. `options: QueryOptions<T>` becomes `options.staleTime` rows). Keyed by type name.
		 *
		 * @param DocSymbolNode $node The symbol node.
		 * @param DocModule $module The symbol's module.
		 * @param DocVersion $version The version.
		 * @return array
		 */
		protected function relatedTypes(DocSymbolNode $node, DocModule $module, DocVersion $version) : array {
			$ret = [];

			foreach ($node->version->getParams() as $param) {
				$type = $param['type'] ?? '';

				if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)/', trim($type), $match)) {
					continue;
				}

				$typeName = $match[1];

				if ($typeName === $node->symbol->name || array_key_exists($typeName, $ret)) {
					continue;
				}

				$typeSymbol = DocSymbol::fromName($module->id, $typeName, null, $this->db, $this->log);

				if ($typeSymbol->id < 1) {
					continue;
				}

				$typeNode = $this->symbols->getNodeAtVersion($typeSymbol->id, $version->id);

				if ($typeNode !== null) {
					$ret[$typeName] = $this->nodeToArray($typeNode, $module->path, $version, true);
				}
			}

			return $ret;
		}

		/**
		 * Serializes a change for lists.
		 *
		 * @param DocChange $change The change.
		 * @return array
		 */
		protected function changeSummary(DocChange $change) : array {
			return [
				'id'           => $change->id,
				'kind'         => $change->kind,
				'title'        => $change->title,
				'versionLabel' => $this->labelFor($change->versionId),
				'hasCodemod'   => !empty($change->codemodCmd),
				'codemodCmd'   => $change->codemodCmd
			];
		}

		/**
		 * Serializes a contract row with a rendered source link.
		 *
		 * @param DocSymbolVersion $contract The contract.
		 * @param DocVersion $version Version whose tag fills the source-link pattern.
		 * @return array
		 */
		protected function contractToArray(DocSymbolVersion $contract, DocVersion $version) : array {
			$arr = $contract->toSerializableArray();

			$arr['introducedLabel'] = $this->labelFor($contract->introducedVersionId);
			$arr['removedLabel']    = $this->labelFor($contract->removedVersionId);
			$arr['sourceUrl']       = $this->sourceUrl($contract, $version);

			return $arr;
		}

		/**
		 * Serializes a contract diff.
		 *
		 * @param DocContractDiff $diff The diff.
		 * @param DocVersion $from Earlier version.
		 * @param DocVersion $to Later version.
		 * @return array
		 */
		protected function contractDiffToArray(DocContractDiff $diff, DocVersion $from, DocVersion $to) : array {
			return [
				'before'           => ($diff->before->id > 0) ? $this->contractToArray($diff->before, $from) : null,
				'after'            => ($diff->after->id > 0) ? $this->contractToArray($diff->after, $to) : null,
				'signatureChanged' => $diff->signatureChanged,
				'paramsAdded'      => $diff->paramsAdded,
				'paramsChanged'    => $diff->paramsChanged,
				'paramsRemoved'    => $diff->paramsRemoved
			];
		}

		/**
		 * The course spine for a learn-mode page, or null when the page is not part of a course.
		 *
		 * @param DocPage $page The page.
		 * @param DocVersion $version The version.
		 * @return null|array
		 */
		protected function courseSpine(DocPage $page, DocVersion $version) : ?array {
			$lesson = $this->courses->getLessonForPage($page->id);

			if ($lesson->id < 1) {
				return null;
			}

			$course  = DocCourse::fromId($lesson->courseId, $this->db, $this->log);
			$lessons = [];
			$total   = 0;

			foreach ($this->courses->getLessons($course->id) as $entry) {
				$total    += $entry['page']->minutes ?? 0;
				$lessons[] = [
					'ordinal' => $entry['lesson']->ordinal,
					'title'   => $entry['page']->title,
					'slug'    => $entry['page']->slug,
					'minutes' => $entry['page']->minutes,
					'current' => $entry['lesson']->id === $lesson->id,
					'route'   => $this->learnRoute($version, $course->slug, $entry['page']->slug)
				];
			}

			$steps = array_map(fn (DocLessonStep $s) => [
				'ordinal'        => $s->ordinal,
				'prompt'         => $s->prompt,
				'hint'           => $s->hint,
				'answer'         => $s->answer,
				'expectedOutput' => $s->expectedOutput
			], $this->courses->getSteps($lesson->id));

			return [
				'slug'          => $course->slug,
				'title'         => $course->title,
				'summary'       => $course->summary,
				'totalMinutes'  => $total,
				'lessonCount'   => count($lessons),
				'currentLesson' => $lesson->ordinal,
				'lessons'       => $lessons,
				'steps'         => $steps
			];
		}

		/**
		 * Returns the label of a version by identifier, or null.
		 *
		 * @param null|int $versionId Version identifier.
		 * @return null|string
		 */
		protected function labelFor(?int $versionId) : ?string {
			if ($versionId === null) {
				return null;
			}

			return $this->versionById($versionId)?->label;
		}

		/**
		 * Builds a learn route.
		 *
		 * @param DocVersion $version The version.
		 * @param string $courseSlug Course slug.
		 * @param string $lessonSlug Lesson page slug.
		 * @return string
		 */
		protected function learnRoute(DocVersion $version, string $courseSlug, string $lessonSlug) : string {
			return "/{$version->label}/learn/{$courseSlug}/{$lessonSlug}";
		}

		/**
		 * Serializes a symbol node.
		 *
		 * @param DocSymbolNode $node The node.
		 * @param string $modulePath Module path of the symbol.
		 * @param DocVersion $version The version.
		 * @param bool $withChildren Whether to serialize children recursively.
		 * @return array
		 */
		protected function nodeToArray(DocSymbolNode $node, string $modulePath, DocVersion $version, bool $withChildren) : array {
			$ref  = $this->symbols->buildRef($node->symbol);
			$name = substr($ref, strpos($ref, '#') + 1);

			$arr = [
				'ref'          => $ref,
				'name'         => $name,
				'shortName'    => $node->symbol->name,
				'kind'         => $node->symbol->kind,
				'state'        => $node->state,
				'sinceLabel'   => $this->labelFor($node->sinceVersionId),
				'changedLabel' => $this->labelFor($node->changedVersionId),
				'removedLabel' => $this->labelFor($node->removedVersionId),
				'route'        => $this->symbolRoute($version, $modulePath, $name),
				'contract'     => $this->contractToArray($node->version, $version),
				'children'     => []
			];

			if ($withChildren) {
				$arr['children'] = array_map(fn (DocSymbolNode $c) => $this->nodeToArray($c, $modulePath, $version, true), $node->children);
			} else {
				$arr['childCount'] = count($node->children);
			}

			return $arr;
		}

		/**
		 * Serializes a page with its body.
		 *
		 * @param DocPage $page The page.
		 * @param DocVersion $version The version.
		 * @return array
		 */
		protected function pageBody(DocPage $page, DocVersion $version) : array {
			return $this->pageSummary($page, $version) + [
				'body'            => $page->body,
				'introducedLabel' => $this->labelFor($page->introducedVersionId),
				'removedLabel'    => $this->labelFor($page->removedVersionId),
				'updated'         => $page->updated->format('Y-m-d H:i:s')
			];
		}

		/**
		 * Serializes a page without its body.
		 *
		 * @param DocPage $page The page.
		 * @param DocVersion $version The version.
		 * @return array
		 */
		protected function pageSummary(DocPage $page, DocVersion $version) : array {
			return [
				'id'      => $page->id,
				'mode'    => $page->mode,
				'slug'    => $page->slug,
				'title'   => $page->title,
				'summary' => $page->summary,
				'minutes' => $page->minutes,
				'route'   => $this->pageRoute($page, $version)
			];
		}

		/**
		 * Builds the route for a page, routing learn pages through their course when they have one.
		 *
		 * @param DocPage $page The page.
		 * @param DocVersion $version The version.
		 * @return string
		 */
		protected function pageRoute(DocPage $page, DocVersion $version) : string {
			if ($page->mode === DocPageModes::HOME) {
				return "/{$version->label}";
			}

			if ($page->mode === DocPageModes::LEARN) {
				$lesson = $this->courses->getLessonForPage($page->id);

				if ($lesson->id > 0) {
					$course = DocCourse::fromId($lesson->courseId, $this->db, $this->log);

					return $this->learnRoute($version, $course->slug, $page->slug);
				}
			}

			return "/{$version->label}/{$page->mode}/{$page->slug}";
		}

		/**
		 * Returns the version immediately before another, or null for the oldest.
		 *
		 * @param DocVersion $version The version.
		 * @return null|DocVersion
		 */
		protected function previousVersion(DocVersion $version) : ?DocVersion {
			$previous = null;

			foreach ($this->versions->getAll() as $candidate) {
				if ($candidate->sortKey >= $version->sortKey) {
					break;
				}

				$previous = $candidate;
			}

			return $previous;
		}

		/**
		 * Serializes a code sample.
		 *
		 * @param DocCodeSample $sample The sample.
		 * @return array
		 */
		protected function sampleToArray(DocCodeSample $sample) : array {
			return [
				'key'               => $sample->sampleKey,
				'title'             => $sample->title,
				'language'          => $sample->language,
				'variant'           => $sample->variant,
				'code'              => $sample->code,
				'isTested'          => $sample->isTested,
				'lastTestPassLabel' => $this->labelFor($sample->lastTestPassVersionId)
			];
		}

		/**
		 * Reads a setting with a default when no settings container was supplied.
		 *
		 * @param string $key Setting key.
		 * @param mixed $default Default value.
		 * @return mixed
		 */
		protected function setting(string $key, mixed $default) : mixed {
			if ($this->settings === null) {
				return $default;
			}

			return $this->settings->get($key, $default);
		}

		/**
		 * Renders the source link for a contract from the configured pattern, or null when there is no source path.
		 *
		 * @param DocSymbolVersion $contract The contract.
		 * @param DocVersion $version Version whose tag fills the pattern.
		 * @return null|string
		 */
		protected function sourceUrl(DocSymbolVersion $contract, DocVersion $version) : ?string {
			$pattern = $this->setting(SettingsStrings::DOCS_SOURCE_URL, '');

			if (empty($contract->sourcePath) || empty($pattern)) {
				return null;
			}

			$url = str_replace(['{tag}', '{label}', '{path}', '{line}'], [$version->tag, $version->label, $contract->sourcePath, strval($contract->sourceLine ?? '')], $pattern);

			if ($contract->sourceLine === null) {
				$url = preg_replace('/#L?$/', '', $url);
			}

			return $url;
		}

		/**
		 * Builds a symbol route.
		 *
		 * @param DocVersion $version The version.
		 * @param string $modulePath Module path.
		 * @param string $name Dotted symbol name.
		 * @return string
		 */
		protected function symbolRoute(DocVersion $version, string $modulePath, string $name) : string {
			return "/{$version->label}/reference/{$modulePath}/{$name}";
		}

		/**
		 * Builds an upgrade route.
		 *
		 * @param DocVersion $from Earlier version.
		 * @param DocVersion $to Later version.
		 * @return string
		 */
		protected function upgradeRoute(DocVersion $from, DocVersion $to) : string {
			return "/upgrade/{$from->label}/{$to->label}";
		}

		/**
		 * Returns a version by identifier from the cache, or null.
		 *
		 * @param int $versionId Version identifier.
		 * @return null|DocVersion
		 */
		protected function versionById(int $versionId) : ?DocVersion {
			if ($this->versionsById === null) {
				$this->versionsById = [];

				foreach ($this->versions->getAll() as $ver) {
					$this->versionsById[$ver->id] = $ver;
				}
			}

			return $this->versionsById[$versionId] ?? null;
		}

		/**
		 * Serializes a version.
		 *
		 * @param DocVersion $version The version.
		 * @return array
		 */
		protected function versionSummary(DocVersion $version) : array {
			return [
				'id'          => $version->id,
				'tag'         => $version->tag,
				'label'       => $version->label,
				'sortKey'     => $version->sortKey,
				'releasedAt'  => $version->releasedAt?->format('Y-m-d'),
				'isLatest'    => $version->isLatest,
				'isSupported' => $version->isSupported
			];
		}
	}
