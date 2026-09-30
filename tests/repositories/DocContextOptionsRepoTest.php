<?php

	use Zibings\DocContextOption;
	use Zibings\DocContextOptionKinds;
	use Zibings\DocContextOptions;

	class DocContextOptionsRepoTest extends ZsfTestCase {
		public function test_KindsAndDefaults() : void {
			$repo   = new DocContextOptions(self::$db, self::$log);
			$prefix = uniqid();
			$made   = [];

			foreach ([['b', 2, true], ['a', 1, false]] as [$suffix, $order, $default]) {
				$opt            = new DocContextOption(self::$db, self::$log);
				$opt->kind      = DocContextOptionKinds::PACKAGE_MANAGER;
				$opt->optionKey = "{$prefix}-{$suffix}";
				$opt->label     = strtoupper($suffix);
				$opt->sortOrder = $order;
				$opt->isDefault = $default;
				$opt->create();

				$made[] = $opt;
			}

			$ours = array_values(array_filter($repo->getByKind(DocContextOptionKinds::PACKAGE_MANAGER), fn ($o) => str_starts_with($o->optionKey, $prefix)));
			self::assertEquals(["{$prefix}-a", "{$prefix}-b"], array_map(fn ($o) => $o->optionKey, $ours), "ordered by sortOrder");

			$default = $repo->getDefault(DocContextOptionKinds::PACKAGE_MANAGER);
			self::assertTrue($default->isDefault);

			foreach ($made as $opt) {
				$opt->delete();
			}

			return;
		}
	}
