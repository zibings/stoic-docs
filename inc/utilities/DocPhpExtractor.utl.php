<?php

	namespace Zibings;

	use PhpParser\Modifiers;
	use PhpParser\Node;
	use PhpParser\Node\Stmt;
	use PhpParser\NodeTraverser;
	use PhpParser\NodeVisitor\NameResolver;
	use PhpParser\Parser;
	use PhpParser\ParserFactory;
	use PhpParser\PrettyPrinter\Standard;

	/**
	 * Extracts the public API of a PHP source tree into a symbol snapshot for DocSnapshotMerger.
	 *
	 * One module per namespace (`Stoic\Web\Resources` becomes `stoic/web/resources`; the global namespace is `global`).
	 * Classes, interfaces, traits, enums, and functions are symbols; public methods, properties (including promoted
	 * constructor parameters), constants, and enum cases are their children. Declared types win over docblock types,
	 * docblocks supply summaries, parameter descriptions, `@return`, `@throws`, `@deprecated`, and `@internal` (which
	 * hides the symbol). Every contract records its file and line so the source-link pattern works.
	 *
	 * @package Zibings
	 */
	class DocPhpExtractor {
		const string KIND_CLASS     = 'class';
		const string KIND_INTERFACE = 'interface';
		const string KIND_TRAIT     = 'trait';
		const string KIND_ENUM      = 'enum';
		const string KIND_FUNCTION  = 'fn';
		const string KIND_METHOD    = 'method';
		const string KIND_PROPERTY  = 'property';
		const string KIND_CONST     = 'const';
		const string KIND_CASE      = 'case';

		protected Parser $parser;
		protected Standard $printer;
		/** @var string[] */
		public array $errors = [];


		public function __construct() {
			$this->parser  = (new ParserFactory())->createForNewestSupportedVersion();
			$this->printer = new Standard();

			return;
		}

		/**
		 * Builds a snapshot from every PHP file under a directory.
		 *
		 * @param string $root Source root; paths in the snapshot are relative to it.
		 * @param string $label Version label.
		 * @param array $exclude Directory names to skip (relative to root or bare names).
		 * @param null|string $tag Version tag; defaults to the label without a leading `v`.
		 * @return array
		 */
		public function snapshot(string $root, string $label, array $exclude = ['vendor', 'tests', 'test', 'node_modules'], ?string $tag = null) : array {
			$root = rtrim(realpath($root) ?: $root, '/');

			if (!is_dir($root)) {
				throw new \InvalidArgumentException("Source root not found: {$root}");
			}

			$modules      = [];
			$this->errors = [];

			foreach ($this->phpFiles($root, $exclude) as $file) {
				$relative = ltrim(substr($file, strlen($root)), '/');

				try {
					$ast = $this->parser->parse(file_get_contents($file) ?: '');
				} catch (\Throwable $ex) {
					$this->errors[] = "{$relative}: {$ex->getMessage()}";

					continue;
				}

				if ($ast === null) {
					continue;
				}

				$traverser = new NodeTraverser();
				$traverser->addVisitor(new NameResolver());
				$ast = $traverser->traverse($ast);

				$this->collect($ast, $relative, $modules);
			}

			ksort($modules);

			foreach ($modules as &$module) {
				ksort($module['symbols']);
			}

			unset($module);

			return [
				'label'      => $label,
				'tag'        => $tag ?? ltrim($label, 'v'),
				'releasedAt' => null,
				'modules'    => $modules
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
					function (\SplFileInfo $file) use ($root, $exclude) : bool {
						if ($file->isDir()) {
							$relative = ltrim(substr($file->getPathname(), strlen($root)), '/');

							$lower = array_map('strtolower', $exclude);

							return !in_array(strtolower($file->getFilename()), $lower, true) && !in_array(strtolower($relative), $lower, true) && !str_starts_with($file->getFilename(), '.');
						}

						return strtolower($file->getExtension()) === 'php';
					}
				)
			);

			foreach ($iterator as $file) {
				$ret[] = $file->getPathname();
			}

			sort($ret);

			return $ret;
		}

		/**
		 * Walks top-level statements (and namespaces) collecting symbols into modules.
		 *
		 * @param Node[] $nodes Statements.
		 * @param string $file Relative file path.
		 * @param array $modules Module accumulator.
		 * @return void
		 */
		protected function collect(array $nodes, string $file, array &$modules) : void {
			foreach ($nodes as $node) {
				if ($node instanceof Stmt\Namespace_) {
					$this->collect($node->stmts, $file, $modules);

					continue;
				}

				if ($node instanceof Stmt\Class_ && $node->name === null) {
					continue;
				}

				if ($node instanceof Stmt\ClassLike || $node instanceof Stmt\Function_) {
					$doc = $this->docblock($node);

					if ($doc['internal']) {
						continue;
					}

					$fqcn  = isset($node->namespacedName) ? $node->namespacedName->toString() : (string) $node->name;
					$parts = explode('\\', $fqcn);
					$name  = array_pop($parts);
					$mod   = count($parts) > 0 ? self::modulePath(implode('\\', $parts)) : 'global';

					$modules[$mod] ??= ['summary' => '', 'symbols' => []];
					$modules[$mod]['symbols'][$name] = ($node instanceof Stmt\Function_)
						? $this->functionSymbol($node, $doc, $file)
						: $this->classSymbol($node, $doc, $file);
				}
			}

			return;
		}

		/**
		 * Module path for a namespace.
		 *
		 * @param string $namespace Namespace.
		 * @return string
		 */
		public static function modulePath(string $namespace) : string {
			return strtolower(str_replace('\\', '/', trim($namespace, '\\'))) ?: 'global';
		}

		/**
		 * Builds a function symbol.
		 *
		 * @param Stmt\Function_ $fn Function node.
		 * @param array $doc Parsed docblock.
		 * @param string $file Relative file.
		 * @return array
		 */
		protected function functionSymbol(Stmt\Function_ $fn, array $doc, string $file) : array {
			$name = (string) $fn->name;

			return $this->callable(self::KIND_FUNCTION, "function {$name}", $fn->params, $fn->returnType, $fn->byRef, $doc, $file, $fn->getStartLine());
		}

		/**
		 * Builds a class-like symbol with its public members as children.
		 *
		 * @param Stmt\ClassLike $node Class, interface, trait, or enum.
		 * @param array $doc Parsed docblock.
		 * @param string $file Relative file.
		 * @return array
		 */
		protected function classSymbol(Stmt\ClassLike $node, array $doc, string $file) : array {
			$name     = (string) $node->name;
			$children = [];

			[$kind, $signature] = match (true) {
				$node instanceof Stmt\Interface_ => [self::KIND_INTERFACE, "interface {$name}" . (count($node->extends) > 0 ? ' extends ' . implode(', ', array_map(fn ($n) => $this->shortName($n), $node->extends)) : '')],
				$node instanceof Stmt\Trait_     => [self::KIND_TRAIT, "trait {$name}"],
				$node instanceof Stmt\Enum_      => [self::KIND_ENUM, "enum {$name}" . ($node->scalarType ? ': ' . $this->type($node->scalarType) : '') . (count($node->implements) > 0 ? ' implements ' . implode(', ', array_map(fn ($n) => $this->shortName($n), $node->implements)) : '')],
				default                          => [self::KIND_CLASS, $this->classSignature($node)]
			};

			foreach ($node->getConstants() as $const) {
				if (!$const->isPublic()) {
					continue;
				}

				$cdoc = $this->docblock($const);

				if ($cdoc['internal']) {
					continue;
				}

				foreach ($const->consts as $c) {
					$cname = (string) $c->name;
					$type  = $const->type ? $this->type($const->type) : $this->exprType($c->value);

					$children[$cname] = $this->leaf(self::KIND_CONST, "const {$cname}: {$type} = " . $this->expr($c->value), $type, $cdoc, $file, $const->getStartLine());
				}
			}

			if ($node instanceof Stmt\Enum_) {
				foreach ($node->stmts as $stmt) {
					if ($stmt instanceof Stmt\EnumCase) {
						$cdoc  = $this->docblock($stmt);
						$cname = (string) $stmt->name;

						$children[$cname] = $this->leaf(self::KIND_CASE, "case {$cname}" . ($stmt->expr ? ' = ' . $this->expr($stmt->expr) : ''), $name, $cdoc, $file, $stmt->getStartLine());
					}
				}
			}

			foreach ($node->getProperties() as $prop) {
				if (!$prop->isPublic()) {
					continue;
				}

				$pdoc = $this->docblock($prop);

				if ($pdoc['internal']) {
					continue;
				}

				foreach ($prop->props as $p) {
					$pname = (string) $p->name;
					$type  = $prop->type ? $this->type($prop->type) : ($pdoc['var'] ?? 'mixed');
					$mods  = ($prop->isStatic() ? 'static ' : '') . ($prop->isReadonly() ? 'readonly ' : '');

					$children[$pname] = $this->leaf(self::KIND_PROPERTY, "public {$mods}{$type} \${$pname}" . ($p->default ? ' = ' . $this->expr($p->default) : ''), $type, $pdoc, $file, $prop->getStartLine());
				}
			}

			foreach ($node->getMethods() as $method) {
				if (!$method->isPublic()) {
					continue;
				}

				$mdoc = $this->docblock($method);

				if ($mdoc['internal']) {
					continue;
				}

				$mname = (string) $method->name;
				$mods  = ($method->isAbstract() ? 'abstract ' : '') . ($method->isFinal() ? 'final ' : '') . 'public ' . ($method->isStatic() ? 'static ' : '');

				$children[$mname] = $this->callable(self::KIND_METHOD, "{$mods}function {$mname}", $method->params, $method->returnType, $method->byRef, $mdoc, $file, $method->getStartLine());

				// Promoted constructor parameters are public properties too
				if ($mname === '__construct') {
					foreach ($method->params as $param) {
						if (($param->flags & Modifiers::PUBLIC) === 0) {
							continue;
						}

						$pname = (string) $param->var->name;
						$type  = $param->type ? $this->type($param->type) : 'mixed';
						$ro    = ($param->flags & Modifiers::READONLY) !== 0 ? 'readonly ' : '';

						$children[$pname] ??= $this->leaf(self::KIND_PROPERTY, "public {$ro}{$type} \${$pname}", $type, ['summary' => $mdoc['params'][$pname] ?? '', 'deprecated' => false, 'return' => null, 'throws' => [], 'params' => [], 'var' => null, 'internal' => false], $file, $param->getStartLine());
					}
				}
			}

			return [
				'kind'       => $kind,
				'signature'  => $signature,
				'summary'    => $doc['summary'],
				'status'     => $doc['deprecated'] ? DocSymbolStatuses::DEPRECATED : DocSymbolStatuses::STABLE,
				'params'     => [],
				'returns'    => [],
				'throws'     => [],
				'sourcePath' => $file,
				'sourceLine' => $node->getStartLine(),
				'children'   => $children
			];
		}

		/**
		 * Signature line for a class.
		 *
		 * @param Stmt\Class_ $class Class node.
		 * @return string
		 */
		protected function classSignature(Stmt\Class_ $class) : string {
			$mods = ($class->isAbstract() ? 'abstract ' : '') . ($class->isFinal() ? 'final ' : '') . ($class->isReadonly() ? 'readonly ' : '');
			$sig  = "{$mods}class {$class->name}";

			if ($class->extends !== null) {
				$sig .= ' extends ' . $this->shortName($class->extends);
			}

			if (count($class->implements) > 0) {
				$sig .= ' implements ' . implode(', ', array_map(fn ($n) => $this->shortName($n), $class->implements));
			}

			return $sig;
		}

		/**
		 * Builds a function or method symbol from its parameters and return type.
		 *
		 * @param string $kind Symbol kind.
		 * @param string $head Signature head (`public function name`).
		 * @param Node\Param[] $params Parameters.
		 * @param null|Node $returnType Declared return type.
		 * @param bool $byRef Returns by reference.
		 * @param array $doc Parsed docblock.
		 * @param string $file Relative file.
		 * @param int $line Start line.
		 * @return array
		 */
		protected function callable(string $kind, string $head, array $params, ?Node $returnType, bool $byRef, array $doc, string $file, int $line) : array {
			$paramList = [];
			$rendered  = [];

			foreach ($params as $param) {
				$name     = (string) $param->var->name;
				$type     = $param->type ? $this->type($param->type) : ($doc['paramTypes'][$name] ?? 'mixed');
				$default  = $param->default ? $this->expr($param->default) : null;
				$variadic = $param->variadic ? '...' : '';

				$rendered[]  = trim("{$type} {$variadic}" . ($param->byRef ? '&' : '') . "\${$name}" . ($default !== null ? " = {$default}" : ''));
				$paramList[] = [
					'name'        => $name,
					'type'        => $type . ($param->variadic ? '[]' : ''),
					'required'    => $default === null && !$param->variadic,
					'default'     => $default,
					'description' => $doc['params'][$name] ?? ''
				];
			}

			$ret     = $returnType ? $this->type($returnType) : ($doc['return']['type'] ?? null);
			$retDesc = $doc['return']['description'] ?? '';

			return [
				'kind'       => $kind,
				'signature'  => $head . ($byRef ? ' &' : '') . '(' . implode(', ', $rendered) . ')' . ($ret !== null ? ": {$ret}" : ''),
				'summary'    => $doc['summary'],
				'status'     => $doc['deprecated'] ? DocSymbolStatuses::DEPRECATED : DocSymbolStatuses::STABLE,
				'params'     => $paramList,
				'returns'    => ($ret !== null) ? ['type' => $ret, 'description' => $retDesc] : [],
				'throws'     => $doc['throws'],
				'sourcePath' => $file,
				'sourceLine' => $line,
				'children'   => []
			];
		}

		/**
		 * Builds a leaf symbol (constant, property, enum case).
		 *
		 * @param string $kind Symbol kind.
		 * @param string $signature Signature.
		 * @param string $type Value type.
		 * @param array $doc Parsed docblock.
		 * @param string $file Relative file.
		 * @param int $line Start line.
		 * @return array
		 */
		protected function leaf(string $kind, string $signature, string $type, array $doc, string $file, int $line) : array {
			return [
				'kind'       => $kind,
				'signature'  => $signature,
				'summary'    => $doc['summary'],
				'status'     => $doc['deprecated'] ? DocSymbolStatuses::DEPRECATED : DocSymbolStatuses::STABLE,
				'params'     => [],
				'returns'    => ['type' => $type, 'description' => ''],
				'throws'     => [],
				'sourcePath' => $file,
				'sourceLine' => $line,
				'children'   => []
			];
		}

		/**
		 * Parses the docblock attached to a node.
		 *
		 * @param Node $node Node.
		 * @return array{summary: string, deprecated: bool, internal: bool, params: array<string,string>, paramTypes: array<string,string>, return: null|array{type: string, description: string}, throws: array, var: null|string}
		 */
		protected function docblock(Node $node) : array {
			$ret = ['summary' => '', 'deprecated' => false, 'internal' => false, 'params' => [], 'paramTypes' => [], 'return' => null, 'throws' => [], 'var' => null];

			foreach ($node->attrGroups ?? [] as $group) {
				foreach ($group->attrs as $attr) {
					if (in_array(strtolower($attr->name->toString()), ['deprecated', '\deprecated'], true)) {
						$ret['deprecated'] = true;
					}
				}
			}

			$comment = $node->getDocComment();

			if ($comment === null) {
				return $ret;
			}

			$lines   = preg_split('/\R/', $comment->getText()) ?: [];
			$summary = [];
			$inTags  = false;

			foreach ($lines as $line) {
				$line = trim(preg_replace('#\s*\*/\s*$#', '', preg_replace('#^\s*(/\*\*|\*/|\*)\s?#', '', $line) ?? '') ?? '');

				if ($line === '' && count($summary) > 0 && !$inTags) {
					$inTags = true;

					continue;
				}

				if (str_starts_with($line, '@')) {
					$inTags = true;

					if (preg_match('/^@param\s+(\S+)\s+\.{0,3}\$(\w+)\s*(.*)$/', $line, $m)) {
						$ret['paramTypes'][$m[2]] = $m[1];
						$ret['params'][$m[2]]     = $m[3];
					} else if (preg_match('/^@param\s+\.{0,3}\$(\w+)\s*(.*)$/', $line, $m)) {
						$ret['params'][$m[1]] = $m[2];
					} else if (preg_match('/^@return\s+(\S+)\s*(.*)$/', $line, $m)) {
						$ret['return'] = ['type' => $m[1], 'description' => $m[2]];
					} else if (preg_match('/^@throws\s+(\S+)\s*(.*)$/', $line, $m)) {
						$ret['throws'][] = ['type' => $this->shortName($m[1]), 'description' => $m[2]];
					} else if (preg_match('/^@var\s+(\S+)/', $line, $m)) {
						$ret['var'] = $m[1];
					} else if (str_starts_with($line, '@deprecated')) {
						$ret['deprecated'] = true;
					} else if (str_starts_with($line, '@internal')) {
						$ret['internal'] = true;
					}

					continue;
				}

				if (!$inTags && $line !== '') {
					$summary[] = $line;
				}
			}

			$ret['summary'] = implode(' ', $summary);

			return $ret;
		}

		/**
		 * Renders a type node using short class names.
		 *
		 * @param Node $type Type node.
		 * @return string
		 */
		protected function type(Node $type) : string {
			return match (true) {
				$type instanceof Node\NullableType     => '?' . $this->type($type->type),
				$type instanceof Node\UnionType        => implode('|', array_map(fn ($t) => $this->type($t), $type->types)),
				$type instanceof Node\IntersectionType => implode('&', array_map(fn ($t) => $this->type($t), $type->types)),
				$type instanceof Node\Name             => $this->shortName($type),
				$type instanceof Node\Identifier       => $type->toString(),
				default                                => 'mixed'
			};
		}

		/**
		 * Last segment of a (possibly resolved) name.
		 *
		 * @param Node\Name|string $name Name node or string.
		 * @return string
		 */
		protected function shortName(Node\Name|string $name) : string {
			$text = ($name instanceof Node\Name) ? $name->toString() : $name;

			return substr($text, strrpos('\\' . $text, '\\'));
		}

		/**
		 * Pretty-prints an expression (defaults, constant values).
		 *
		 * @param Node\Expr $expr Expression.
		 * @return string
		 */
		protected function expr(Node\Expr $expr) : string {
			// Names were resolved to fully-qualified form; readers want the short form the author wrote
			return preg_replace('/\\\\?(?:[A-Za-z_]\w*\\\\)+(?=[A-Za-z_])/', '', $this->printer->prettyPrintExpr($expr)) ?? '';
		}

		/**
		 * Rough type of a constant expression.
		 *
		 * @param Node\Expr $expr Expression.
		 * @return string
		 */
		protected function exprType(Node\Expr $expr) : string {
			return match (true) {
				$expr instanceof Node\Scalar\String_, $expr instanceof Node\Scalar\InterpolatedString => 'string',
				$expr instanceof Node\Scalar\Int_    => 'int',
				$expr instanceof Node\Scalar\Float_  => 'float',
				$expr instanceof Node\Expr\Array_    => 'array',
				$expr instanceof Node\Expr\ConstFetch => match (strtolower($expr->name->toString())) { 'true', 'false' => 'bool', 'null' => 'null', default => 'mixed' },
				default => 'mixed'
			};
		}
	}
