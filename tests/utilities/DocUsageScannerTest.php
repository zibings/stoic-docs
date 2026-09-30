<?php

	use Zibings\DocUsageScanner;

	/**
	 * The usage scanner counts library references in a consuming project and reads composer.lock.
	 */
	class DocUsageScannerTest extends ZsfTestCase {
		public function test_CountsReferencesFromComposerLock() : void {
			$scanner = new DocUsageScanner();
			$report  = $scanner->scan(STOIC_CORE_PATH . 'tests/fixtures/consumer', 'acme/widgets');

			self::assertEquals([], $scanner->errors);
			self::assertEquals('scan-usage.php', $report['tool']);
			self::assertEquals('acme/widgets', $report['package']);
			self::assertEquals('1.0.0', $report['fromVersion']);
			self::assertEquals(['Acme\Widgets'], $report['namespaces']);

			$byRef = [];

			foreach ($report['imports'] as $import) {
				$byRef[$import['ref']] = $import;
			}

			// use, extends, param type, new ×2, instanceof, member owners; two files
			self::assertEquals(['callSites' => 13, 'files' => 2], ['callSites' => $byRef['acme/widgets#Widget']['callSites'], 'files' => $byRef['acme/widgets#Widget']['files']]);
			// method calls through a local `new` variable, a typed parameter, and a direct `(new X)->`
			self::assertEquals(3, $byRef['acme/widgets#Widget.paint']['callSites']);
			self::assertEquals(1, $byRef['acme/widgets#Widget.setColor']['callSites']);
			self::assertEquals(1, $byRef['acme/widgets#Widget.fromArray']['callSites']);
			self::assertEquals(1, $byRef['acme/widgets#Widget.DEFAULT_NAME']['callSites']);
			// enum cases are class constants; nested namespace module path
			self::assertEquals(4, $byRef['acme/widgets/util#Color.Red']['callSites']);
			self::assertEquals(2, $byRef['acme/widgets/util#Color.Red']['files']);
			// use function
			self::assertEquals(1, $byRef['acme/widgets#make_widget']['callSites']);
			// ordering: most call sites first
			self::assertEquals('acme/widgets#Widget', $report['imports'][0]['ref']);

			return;
		}

		public function test_ExplicitNamespacesWhenNotInLock() : void {
			$scanner = new DocUsageScanner();
			$report  = $scanner->scan(STOIC_CORE_PATH . 'tests/fixtures/consumer', 'other/thing', ['Acme\Widgets\Util']);

			self::assertNull($report['fromVersion']);
			self::assertEquals(['acme/widgets/util#Color', 'acme/widgets/util#Color.Red', 'acme/widgets/util#Color.Blue'], array_column($report['imports'], 'ref'));

			$this->expectException(\InvalidArgumentException::class);
			$scanner->scan(STOIC_CORE_PATH . 'tests/fixtures/consumer', 'other/thing');

			return;
		}
	}
