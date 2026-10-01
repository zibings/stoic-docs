<?php

	/**
	 * Builds fixtures/stoic.json, the documentation bundle for the Stoic:PHP framework (stoic/stoic, stoic/io,
	 * stoic/pdo, stoic/web), from the sibling package checkouts plus the hand-written content in content/.
	 *
	 * Run from the docs-site root:
	 *
	 *   php fixtures/stoic/build.php --repos .. --out fixtures/stoic.json
	 *
	 * Each documented version is a *family*: one git tag per package. The script archives every tag into a temporary
	 * tree shaped `stoic-php-web/blob/v1.4.2/Web/Stoic.php`, so the extractor's relative source paths already carry
	 * the repository and tag and the site's `docs.sourceUrlPattern` can be `https://github.com/zibings/{path}#L{line}`.
	 */

	const STOIC_CORE_PATH = './';
	require_once(STOIC_CORE_PATH . 'inc/core.php');

	use PhpParser\Node;
	use PhpParser\Node\Stmt;

	use Stoic\Utilities\ConsoleHelper;

	use Zibings\CliScriptHelper;
	use Zibings\DocPhpExtractor;
	use Zibings\DocSnapshotMerger;
	use Zibings\DocSymbolKinds;

	/**
	 * Version families: label => [tag, releasedAt (date of the newest tag in the family), packages].
	 */
	const FAMILIES = [
		'v1.3' => [
			'tag'        => '1.3',
			'releasedAt' => '2025-08-02',
			'sortKey'    => 10300,
			'packages'   => ['stoic-php-core' => 'v1.3.1', 'stoic-php-io' => 'v1.3.1', 'stoic-php-pdo' => 'v1.3.4', 'stoic-php-web' => 'v1.3.16']
		],
		'v1.4' => [
			'tag'        => '1.4',
			'releasedAt' => '2025-10-10',
			'sortKey'    => 10400,
			'packages'   => ['stoic-php-core' => 'v1.4.0', 'stoic-php-io' => 'v1.4.0', 'stoic-php-pdo' => 'v1.4.0', 'stoic-php-web' => 'v1.4.2']
		]
	];

	/**
	 * Protected members that are extension points (subclasses override or call them), keyed by fully qualified class.
	 * The stock extractor only reads public members.
	 */
	const EXTENSION_POINTS = [
		'Stoic\Chain\DispatchBase' => ['makeConsumable', 'makeStateful', 'makeValid'],
		'Stoic\Chain\NodeBase'     => ['setKey', 'setVersion', 'isDispatchOfType'],
		'Stoic\Pdo\BaseDbClass'    => ['__initialize', 'logReturnHelperMessages', 'tryPdoExcept'],
		'Stoic\Pdo\BaseDbModel'    => ['__setupModel', '__canCreate', '__canDelete', '__canRead', '__canUpdate', 'setColumn', 'setTableName'],
		'Stoic\Web\Api\BaseDbApi'  => ['newResponse', 'requestHasInputVars']
	];

	/**
	 * Extractor tuned for the Stoic packages: nullable types render as `null|T` at every version (so a `?T` to
	 * `null|T` rewrite is not a contract change), implicitly nullable parameters (`array $x = null`) are normalized the
	 * same way, and allow-listed protected extension points are documented as methods.
	 */
	class StoicExtractor extends DocPhpExtractor {
		protected function type(Node $type) : string {
			if ($type instanceof Node\NullableType) {
				return 'null|' . $this->type($type->type);
			}

			return parent::type($type);
		}

		protected function callable(string $kind, string $head, array $params, ?Node $returnType, bool $byRef, array $doc, string $file, int $line) : array {
			$ret = parent::callable($kind, $head, $params, $returnType, $byRef, $doc, $file, $line);

			foreach ($ret['params'] as &$param) {
				if ($param['default'] === 'null' && $param['type'] !== 'mixed' && !str_contains($param['type'], 'null')) {
					$ret['signature'] = str_replace("{$param['type']} \${$param['name']} = null", "null|{$param['type']} \${$param['name']} = null", $ret['signature']);
					$param['type']    = 'null|' . $param['type'];
				}
			}

			unset($param);

			return $ret;
		}

		protected function classSymbol(Stmt\ClassLike $node, array $doc, string $file) : array {
			$ret  = parent::classSymbol($node, $doc, $file);
			$fqcn = isset($node->namespacedName) ? $node->namespacedName->toString() : (string) $node->name;

			foreach (EXTENSION_POINTS[$fqcn] ?? [] as $wanted) {
				foreach ($node->getMethods() as $method) {
					if ((string) $method->name !== $wanted || $method->isPublic()) {
						continue;
					}

					$mdoc = $this->docblock($method);
					$mods = ($method->isAbstract() ? 'abstract ' : '') . 'protected ' . ($method->isStatic() ? 'static ' : '');

					$ret['children'][$wanted] = $this->callable(self::KIND_METHOD, "{$mods}function {$wanted}", $method->params, $method->returnType, $method->byRef, $mdoc, $file, $method->getStartLine());
				}
			}

			ksort($ret['children']);

			return $ret;
		}
	}

	/**
	 * Maps the extractor's PHP-flavoured kinds onto the seven kinds the site knows.
	 */
	function mapKinds(array &$symbols) : void {
		foreach ($symbols as &$sym) {
			// A constructor's `@return void` docblock is not a return type; an empty `returns` must encode as {} rather than []
			foreach ($sym['versions'] as &$row) {
				if ($sym['name'] === '__construct' && str_ends_with($row['signature'], ': void')) {
					$row['signature'] = substr($row['signature'], 0, -6);
					$row['returns']   = [];
				}

				if (empty($row['returns'])) {
					$row['returns'] = (object) [];
				}
			}

			unset($row);

			$signature = $sym['versions'][count($sym['versions']) - 1]['signature'] ?? '';
			$children  = $sym['children'] ?? [];

			$sym['kind'] = match (true) {
				$sym['kind'] === 'property'                         => DocSymbolKinds::OPTION,
				$sym['kind'] === 'interface'                        => DocSymbolKinds::CLASS_,
				str_contains($signature, 'extends Exception')       => DocSymbolKinds::ERROR,
				str_contains($signature, 'extends EnumBase')        => DocSymbolKinds::TYPE,
				$sym['kind'] === 'class' && count($children) > 0 && count(array_filter($children, fn ($c) => $c['kind'] !== 'const')) === 0 => DocSymbolKinds::TYPE,
				default                                             => $sym['kind']
			};

			if (count($children) > 0) {
				mapKinds($sym['children']);
			}
		}

		unset($sym);

		return;
	}

	/**
	 * Fills empty contract summaries from content/summaries.php (`Class.*` entries cover every child constant).
	 */
	function fillSummaries(array &$symbols, string $module, array $summaries, string $prefix = '') : void {
		foreach ($symbols as &$sym) {
			$ref   = "{$module}#{$prefix}{$sym['name']}";
			$owner = ($prefix !== '') ? "{$module}#" . rtrim($prefix, '.') . '.*' : null;
			$fill  = $summaries[$ref] ?? (($owner !== null) ? ($summaries[$owner] ?? null) : null);

			if ($fill !== null) {
				foreach ($sym['versions'] as &$row) {
					if (trim($row['summary']) === '') {
						$value          = preg_match('/=\s*(.+)$/', $row['signature'], $m) ? trim($m[1]) : '';
						$row['summary'] = is_callable($fill) ? $fill($sym['name'], $value) : (string) $fill;
					}
				}

				unset($row);
			}

			if (!empty($sym['children'])) {
				fillSummaries($sym['children'], $module, $summaries, "{$prefix}{$sym['name']}.");
			}
		}

		unset($sym);

		return;
	}

	$ch     = new ConsoleHelper($argv);
	$script = new CliScriptHelper("Stoic:PHP bundle builder", "Extracts the public API of the four Stoic packages at each documented version family and merges it with the hand-written pages in fixtures/stoic/content into one bundle.")
		->addOption('repos', 'r', 'repos', 'Checkout root', 'Directory that contains the stoic-php-core, stoic-php-io, stoic-php-pdo, and stoic-php-web checkouts (tags must be fetched)', false, '..')
		->addOption('out', 'o', 'out', 'Bundle file to write', 'Path of the JSON bundle to write', false, 'fixtures/stoic.json');

	$opts  = $script->startScript($ch)->getOptions($ch);
	$repos = rtrim((string) $opts['repos'], '/');
	$work  = sys_get_temp_dir() . '/stoic-docs-build-' . getmypid();

	mkdir($work, 0777, true);

	$extractor = new StoicExtractor();
	$snapshots = [];

	foreach (FAMILIES as $label => $family) {
		$root = "{$work}/{$label}";

		foreach ($family['packages'] as $repo => $tag) {
			$dest = "{$root}/{$repo}/blob/{$tag}";

			mkdir($dest, 0777, true);

			exec(sprintf('git -C %s archive %s | tar -x -C %s', escapeshellarg("{$repos}/{$repo}"), escapeshellarg($tag), escapeshellarg($dest)), $out, $code);

			if ($code !== 0) {
				$ch->putLine("ERROR: could not archive {$repo} at {$tag}; is the tag fetched?");

				exit(1);
			}
		}

		$snap               = $extractor->snapshot($root, $label, ['vendor', 'tests', 'docs', 'templates'], $family['tag']);
		$snap['releasedAt'] = $family['releasedAt'];
		$snap['sortKey']    = $family['sortKey'];
		$snapshots[]        = $snap;

		$ch->putLine("Read {$label}: " . count($snap['modules']) . " namespace(s)");

		foreach ($extractor->errors as $error) {
			$ch->putLine("  skipped {$error}");
		}
	}

	exec('rm -rf ' . escapeshellarg($work));

	$bundle = (new DocSnapshotMerger())->merge($snapshots, 'fixtures/stoic/build.php');

	$summaries = require(__DIR__ . '/content/summaries.php');

	foreach ($bundle['modules'] as &$module) {
		mapKinds($module['symbols']);
		fillSummaries($module['symbols'], $module['path'], $summaries);
	}

	unset($module);

	// Hand-written content: module summaries and order, context options, pages, changes, courses
	$content = require(__DIR__ . '/content/index.php');

	foreach ($bundle['modules'] as &$module) {
		if (!isset($content['modules'][$module['path']])) {
			$ch->putLine("WARNING: no summary for module {$module['path']}");

			continue;
		}

		$module['summary']   = $content['modules'][$module['path']]['summary'];
		$module['sortOrder'] = $content['modules'][$module['path']]['sortOrder'];
	}

	unset($module);
	usort($bundle['modules'], fn ($a, $b) => $a['sortOrder'] <=> $b['sortOrder']);

	$bundle['contextOptions'] = $content['contextOptions'];
	$bundle['pages']          = $content['pages'];
	$bundle['changes']        = $content['changes'];
	$bundle['courses']        = $content['courses'];

	$json  = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$total = array_sum(array_map(fn ($m) => count($m['symbols']), $bundle['modules']));

	file_put_contents((string) $opts['out'], $json . "\n");

	$ch->putLine("Wrote {$opts['out']}: " . count($bundle['versions']) . " version(s), " . count($bundle['modules']) . " module(s), {$total} top-level symbol(s), " . count($bundle['pages']) . " page(s), " . count($bundle['changes']) . " change(s), " . count($bundle['courses']) . " course(s)");
