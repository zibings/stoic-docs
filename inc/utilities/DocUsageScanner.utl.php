<?php

	namespace Zibings;

	use PhpParser\Node;
	use PhpParser\Node\Expr;
	use PhpParser\Node\Stmt;
	use PhpParser\NodeTraverser;
	use PhpParser\NodeVisitor\NameResolver;
	use PhpParser\NodeVisitorAbstract;
	use PhpParser\Parser;
	use PhpParser\ParserFactory;

	/**
	 * Scans a consuming PHP project for the symbols it uses from one documented library, producing the scan report the
	 * upgrade view's "only symbols my code imports" filter reads:
	 *
	 *   { "tool": "scan-usage.php", "package": "stoic/web", "fromVersion": "1.4.0",
	 *     "imports": [ { "ref": "stoic/web#Stoic", "callSites": 12, "files": 4 }, ... ] }
	 *
	 * The library's namespaces and installed version come from `composer.lock` (PSR-4 autoload of the package), or from
	 * explicit namespaces when the project does not use Composer. Refs use the same `module#Class` and
	 * `module#Class.member` form DocPhpExtractor emits, so the two line up.
	 *
	 * Counted: `use` imports, `new`, static calls, class constants, static properties, `instanceof`, `extends`,
	 * `implements`, type declarations, `catch` types, and method calls on variables whose class is known from a `new`
	 * assignment or a typed parameter in the same function. Reports stay on the reader's machine.
	 *
	 * @package Zibings
	 */
	class DocUsageScanner {
		protected Parser $parser;
		/** @var string[] */
		public array $errors = [];


		public function __construct() {
			$this->parser = (new ParserFactory())->createForNewestSupportedVersion();

			return;
		}

		/**
		 * Reads a package's installed version and PSR-4 namespaces from composer.lock.
		 *
		 * @param string $projectDir Consuming project root.
		 * @param string $package Package name (`vendor/name`).
		 * @return array{version: null|string, namespaces: string[]}
		 */
		public function packageInfo(string $projectDir, string $package) : array {
			$lockFile = rtrim($projectDir, '/') . '/composer.lock';

			if (!is_file($lockFile)) {
				return ['version' => null, 'namespaces' => []];
			}

			$lock = json_decode(file_get_contents($lockFile) ?: '', true) ?: [];

			foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $entry) {
				if (($entry['name'] ?? '') !== $package) {
					continue;
				}

				$namespaces = [];

				foreach (['psr-4', 'psr-0'] as $kind) {
					foreach (array_keys($entry['autoload'][$kind] ?? []) as $ns) {
						$namespaces[] = trim((string) $ns, '\\');
					}
				}

				// Classmap packages declare no namespace roots; read them off the installed sources instead
				if (count($namespaces) < 1) {
					foreach ($entry['autoload']['classmap'] ?? [] as $dir) {
						$namespaces = array_merge($namespaces, $this->declaredNamespaces(rtrim($projectDir, '/') . "/vendor/{$package}/" . trim((string) $dir, '/')));
					}
				}

				return ['version' => isset($entry['version']) ? (string) $entry['version'] : null, 'namespaces' => $this->rootNamespaces($namespaces)];
			}

			return ['version' => null, 'namespaces' => []];
		}

		/**
		 * Namespaces declared by the PHP files under a directory.
		 *
		 * @param string $dir Directory.
		 * @return string[]
		 */
		protected function declaredNamespaces(string $dir) : array {
			if (!is_dir($dir)) {
				return [];
			}

			$ret = [];

			foreach ($this->phpFiles($dir, ['tests', 'test', 'vendor']) as $file) {
				$head = file_get_contents($file, false, null, 0, 4096) ?: '';

				if (preg_match('/^\s*namespace\s+([A-Za-z_\\\\][\w\\\\]*)\s*[;{]/m', $head, $m)) {
					$ret[] = $m[1];
				}
			}

			return array_values(array_unique($ret));
		}

		/**
		 * Reduces a namespace list to the ones not nested inside another entry.
		 *
		 * @param string[] $namespaces Namespaces.
		 * @return string[]
		 */
		protected function rootNamespaces(array $namespaces) : array {
			$namespaces = array_values(array_unique(array_map(fn ($ns) => trim((string) $ns, '\\'), $namespaces)));
			sort($namespaces);

			return array_values(array_filter($namespaces, function (string $ns) use ($namespaces) : bool {
				foreach ($namespaces as $other) {
					if ($other !== $ns && str_starts_with($ns, $other . '\\')) {
						return false;
					}
				}

				return true;
			}));
		}

		/**
		 * Scans a project and returns the report array.
		 *
		 * @param string $projectDir Project root.
		 * @param string $package Documented package name, recorded in the report.
		 * @param string[] $namespaces Library namespaces to match; taken from composer.lock when empty.
		 * @param array $exclude Directory names to skip.
		 * @return array
		 */
		public function scan(string $projectDir, string $package, array $namespaces = [], array $exclude = ['vendor', 'node_modules', '.git']) : array {
			$projectDir = rtrim(realpath($projectDir) ?: $projectDir, '/');

			if (!is_dir($projectDir)) {
				throw new \InvalidArgumentException("Project directory not found: {$projectDir}");
			}

			$info = $this->packageInfo($projectDir, $package);

			if (count($namespaces) < 1) {
				$namespaces = $info['namespaces'];
			}

			if (count($namespaces) < 1) {
				throw new \InvalidArgumentException("No namespaces for {$package}: it is not in composer.lock, so pass them explicitly");
			}

			$namespaces   = array_map(fn (string $ns) => trim($ns, '\\'), $namespaces);
			$counts       = [];
			$this->errors = [];

			foreach ($this->phpFiles($projectDir, $exclude) as $file) {
				$relative = ltrim(substr($file, strlen($projectDir)), '/');

				try {
					$ast = $this->parser->parse(file_get_contents($file) ?: '');
				} catch (\Throwable $ex) {
					$this->errors[] = "{$relative}: {$ex->getMessage()}";

					continue;
				}

				if ($ast === null) {
					continue;
				}

				$visitor   = new DocUsageVisitor($namespaces);
				$traverser = new NodeTraverser();
				$traverser->addVisitor(new NameResolver());
				$traverser->addVisitor($visitor);
				$traverser->traverse($ast);

				foreach ($visitor->refs as $ref => $hits) {
					$counts[$ref] ??= ['callSites' => 0, 'files' => 0];
					$counts[$ref]['callSites'] += $hits;
					$counts[$ref]['files']     += 1;
				}
			}

			$imports = [];

			foreach ($counts as $ref => $c) {
				$imports[] = ['ref' => $ref, 'callSites' => $c['callSites'], 'files' => $c['files']];
			}

			usort($imports, fn ($a, $b) => [$b['callSites'], $a['ref']] <=> [$a['callSites'], $b['ref']]);

			return [
				'tool'        => 'scan-usage.php',
				'package'     => $package,
				'fromVersion' => $info['version'],
				'namespaces'  => $namespaces,
				'generatedAt' => date('c'),
				'imports'     => $imports
			];
		}

		/**
		 * Lists PHP files under a root, skipping excluded directories.
		 *
		 * @param string $root Root directory.
		 * @param array $exclude Directory names to skip.
		 * @return string[]
		 */
		protected function phpFiles(string $root, array $exclude) : array {
			$ret      = [];
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveCallbackFilterIterator(
					new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
					fn (\SplFileInfo $file) : bool => $file->isDir() ? !in_array(strtolower($file->getFilename()), array_map('strtolower', $exclude), true) : strtolower($file->getExtension()) === 'php'
				)
			);

			foreach ($iterator as $file) {
				$ret[] = $file->getPathname();
			}

			sort($ret);

			return $ret;
		}
	}

	/**
	 * Collects references to library classes and members within one file.
	 *
	 * @package Zibings
	 */
	class DocUsageVisitor extends NodeVisitorAbstract {
		/** @var array<string,int> ref => hits */
		public array $refs = [];
		/** @var array<string,string> variable name => FQCN for the current function scope */
		protected array $scope = [];
		/** @var array<int,array<string,string>> */
		protected array $scopeStack = [];


		/**
		 * @param string[] $namespaces Library namespaces (no leading backslash).
		 */
		public function __construct(protected array $namespaces) {
			return;
		}

		public function enterNode(Node $node) {
			if ($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassMethod || $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction) {
				$this->scopeStack[] = $this->scope;
				$this->scope        = ($node instanceof Expr\ArrowFunction) ? $this->scope : [];

				foreach ($node->getParams() as $param) {
					$class = $this->classOf($param->type);

					if ($class !== null) {
						$this->scope[(string) $param->var->name] = $class;
					}
				}
			}

			if ($node instanceof Stmt\Use_ || $node instanceof Stmt\GroupUse) {
				foreach ($node->uses as $use) {
					$name = ($node instanceof Stmt\GroupUse) ? $node->prefix->toString() . '\\' . $use->name->toString() : $use->name->toString();

					$this->hitClass($name);
				}
			} else if ($node instanceof Expr\New_ && $node->class instanceof Node\Name) {
				$this->hitClass($node->class->toString());
			} else if ($node instanceof Expr\StaticCall && $node->class instanceof Node\Name && $node->name instanceof Node\Identifier) {
				$this->hitMember($node->class->toString(), (string) $node->name);
			} else if ($node instanceof Expr\ClassConstFetch && $node->class instanceof Node\Name && $node->name instanceof Node\Identifier) {
				if (strtolower((string) $node->name) === 'class') {
					$this->hitClass($node->class->toString());
				} else {
					$this->hitMember($node->class->toString(), (string) $node->name);
				}
			} else if ($node instanceof Expr\StaticPropertyFetch && $node->class instanceof Node\Name && $node->name instanceof Node\VarLikeIdentifier) {
				$this->hitMember($node->class->toString(), (string) $node->name);
			} else if ($node instanceof Expr\Instanceof_ && $node->class instanceof Node\Name) {
				$this->hitClass($node->class->toString());
			} else if ($node instanceof Stmt\Class_) {
				if ($node->extends !== null) {
					$this->hitClass($node->extends->toString());
				}

				foreach ($node->implements as $iface) {
					$this->hitClass($iface->toString());
				}
			} else if ($node instanceof Stmt\Interface_) {
				foreach ($node->extends as $iface) {
					$this->hitClass($iface->toString());
				}
			} else if ($node instanceof Stmt\TraitUse) {
				foreach ($node->traits as $trait) {
					$this->hitClass($trait->toString());
				}
			} else if ($node instanceof Stmt\Catch_) {
				foreach ($node->types as $type) {
					$this->hitClass($type->toString());
				}
			} else if ($node instanceof Node\Param || $node instanceof Stmt\Property) {
				foreach ($this->namesIn($node->type) as $name) {
					$this->hitClass($name);
				}
			}

			return null;
		}

		/**
		 * Receiver-based references are handled on the way out, once NameResolver has resolved the child nodes.
		 */
		public function leaveNode(Node $node) {
			if ($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassMethod || $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction) {
				$this->scope = array_pop($this->scopeStack) ?? [];
			} else if ($node instanceof Expr\Assign && $node->var instanceof Expr\Variable && is_string($node->var->name)) {
				$class = ($node->expr instanceof Expr\New_ && $node->expr->class instanceof Node\Name) ? $node->expr->class->toString() : null;

				if ($class !== null) {
					$this->scope[$node->var->name] = $class;
				}
			} else if (($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall) && $node->name instanceof Node\Identifier) {
				$class = $this->receiverClass($node->var);

				if ($class !== null) {
					$this->hitMember($class, (string) $node->name);
				}
			} else if (($node instanceof Expr\PropertyFetch || $node instanceof Expr\NullsafePropertyFetch) && $node->name instanceof Node\Identifier) {
				$class = $this->receiverClass($node->var);

				if ($class !== null) {
					$this->hitMember($class, (string) $node->name);
				}
			}

			return null;
		}

		/**
		 * Class of a method-call receiver when it is a known variable or a direct `new`.
		 *
		 * @param Expr $var Receiver expression.
		 * @return null|string
		 */
		protected function receiverClass(Expr $var) : ?string {
			if ($var instanceof Expr\Variable && is_string($var->name)) {
				return $this->scope[$var->name] ?? null;
			}

			if ($var instanceof Expr\New_ && $var->class instanceof Node\Name) {
				return $var->class->toString();
			}

			return null;
		}

		/**
		 * FQCN named by a type node when it is a single class name.
		 *
		 * @param null|Node $type Type node.
		 * @return null|string
		 */
		protected function classOf(?Node $type) : ?string {
			if ($type instanceof Node\NullableType) {
				return $this->classOf($type->type);
			}

			return ($type instanceof Node\Name) ? $type->toString() : null;
		}

		/**
		 * All class names within a (possibly compound) type node.
		 *
		 * @param null|Node $type Type node.
		 * @return string[]
		 */
		protected function namesIn(?Node $type) : array {
			return match (true) {
				$type instanceof Node\NullableType => $this->namesIn($type->type),
				$type instanceof Node\UnionType, $type instanceof Node\IntersectionType => array_merge(...array_map(fn ($t) => $this->namesIn($t), $type->types)),
				$type instanceof Node\Name => [$type->toString()],
				default => []
			};
		}

		/**
		 * Records a class reference when it belongs to a library namespace.
		 *
		 * @param string $fqcn Fully-qualified class name.
		 * @return void
		 */
		protected function hitClass(string $fqcn) : void {
			$ref = $this->ref($fqcn);

			if ($ref !== null) {
				$this->refs[$ref] = ($this->refs[$ref] ?? 0) + 1;
			}

			return;
		}

		/**
		 * Records a member reference (and the owning class) when it belongs to a library namespace.
		 *
		 * @param string $fqcn Fully-qualified class name.
		 * @param string $member Member name.
		 * @return void
		 */
		protected function hitMember(string $fqcn, string $member) : void {
			$ref = $this->ref($fqcn);

			if ($ref !== null) {
				$this->refs[$ref]              = ($this->refs[$ref] ?? 0) + 1;
				$this->refs["{$ref}.{$member}"] = ($this->refs["{$ref}.{$member}"] ?? 0) + 1;
			}

			return;
		}

		/**
		 * `module#Class` ref for a class inside a library namespace, or null.
		 *
		 * @param string $fqcn Fully-qualified class name.
		 * @return null|string
		 */
		protected function ref(string $fqcn) : ?string {
			$fqcn = ltrim($fqcn, '\\');

			foreach ($this->namespaces as $ns) {
				if ($fqcn === $ns || str_starts_with($fqcn, $ns . '\\')) {
					$parts = explode('\\', $fqcn);
					$class = array_pop($parts);

					return DocPhpExtractor::modulePath(implode('\\', $parts)) . '#' . $class;
				}
			}

			return null;
		}
	}
