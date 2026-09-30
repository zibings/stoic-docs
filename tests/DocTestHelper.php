<?php

	use Stoic\Log\Logger;
	use Stoic\Pdo\PdoHelper;

	use Zibings\DocModule;
	use Zibings\DocPage;
	use Zibings\DocPageModes;
	use Zibings\DocSymbol;
	use Zibings\DocSymbolVersion;
	use Zibings\DocVersion;

	/**
	 * Small factory for docs records used by the docs test classes. Every record gets a unique natural key so tests
	 * can run against a shared test database and clean up after themselves.
	 */
	class DocTestHelper {
		/**
		 * Creates a version with a unique tag, label, and sort key.
		 *
		 * @param PdoHelper $db Database instance.
		 * @param Logger $log Logger instance.
		 * @param int|null $sortKey Optional sort key; a unique high value is generated otherwise.
		 * @return DocVersion
		 */
		public static function makeVersion(PdoHelper $db, Logger $log, ?int $sortKey = null) : DocVersion {
			static $counter = 0;

			$counter++;
			$unique = uniqid();

			$ver          = new DocVersion($db, $log);
			$ver->tag     = "t-{$unique}";
			$ver->label   = "l-{$unique}";
			$ver->sortKey = $sortKey ?? (1000000 + intval(substr(hexdec(substr($unique, -6)) . '', -6)) * 10 + $counter);

			if ($ver->create()->isBad()) {
				throw new \RuntimeException("Could not create test version");
			}

			return $ver;
		}

		/**
		 * Creates a module with a unique path.
		 *
		 * @param PdoHelper $db Database instance.
		 * @param Logger $log Logger instance.
		 * @return DocModule
		 */
		public static function makeModule(PdoHelper $db, Logger $log) : DocModule {
			$mod       = new DocModule($db, $log);
			$mod->path = "test/" . uniqid();

			if ($mod->create()->isBad()) {
				throw new \RuntimeException("Could not create test module");
			}

			return $mod;
		}

		/**
		 * Creates a symbol in a module.
		 *
		 * @param PdoHelper $db Database instance.
		 * @param Logger $log Logger instance.
		 * @param DocModule $module Owning module.
		 * @param string $kind Symbol kind.
		 * @param int|null $parentId Optional parent symbol identifier.
		 * @return DocSymbol
		 */
		public static function makeSymbol(PdoHelper $db, Logger $log, DocModule $module, string $kind = 'fn', ?int $parentId = null) : DocSymbol {
			$sym                 = new DocSymbol($db, $log);
			$sym->moduleId       = $module->id;
			$sym->parentSymbolId = $parentId;
			$sym->name           = "sym_" . uniqid();
			$sym->kind           = $kind;

			if ($sym->create()->isBad()) {
				throw new \RuntimeException("Could not create test symbol");
			}

			return $sym;
		}

		/**
		 * Creates a contract row for a symbol.
		 *
		 * @param PdoHelper $db Database instance.
		 * @param Logger $log Logger instance.
		 * @param DocSymbol $symbol The symbol.
		 * @param DocVersion $introduced Introduced version.
		 * @param DocVersion|null $removed Optional removed version.
		 * @param array $params Optional parameter descriptors.
		 * @return DocSymbolVersion
		 */
		public static function makeContract(PdoHelper $db, Logger $log, DocSymbol $symbol, DocVersion $introduced, ?DocVersion $removed = null, array $params = []) : DocSymbolVersion {
			$sv                      = new DocSymbolVersion($db, $log);
			$sv->symbolId            = $symbol->id;
			$sv->introducedVersionId = $introduced->id;
			$sv->removedVersionId    = $removed?->id;
			$sv->signature           = "function {$symbol->name}(): void";
			$sv->summary             = "Summary for {$symbol->name} at {$introduced->label}";
			$sv->setParams($params);

			if ($sv->create()->isBad()) {
				throw new \RuntimeException("Could not create test contract");
			}

			return $sv;
		}

		/**
		 * Creates a page.
		 *
		 * @param PdoHelper $db Database instance.
		 * @param Logger $log Logger instance.
		 * @param DocVersion $introduced Introduced version.
		 * @param string $mode Reader mode.
		 * @return DocPage
		 */
		public static function makePage(PdoHelper $db, Logger $log, DocVersion $introduced, string $mode = DocPageModes::DO) : DocPage {
			$page                      = new DocPage($db, $log);
			$page->mode                = $mode;
			$page->slug                = "page-" . uniqid();
			$page->title               = "Title " . uniqid();
			$page->body                = "Body";
			$page->introducedVersionId = $introduced->id;

			if ($page->create()->isBad()) {
				throw new \RuntimeException("Could not create test page");
			}

			return $page;
		}
	}
