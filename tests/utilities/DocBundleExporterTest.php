<?php

	use Zibings\DocBundleExporter;
	use Zibings\DocBundleImporter;

	/**
	 * Export must round-trip: exporting, resetting, importing the export, and exporting again yields the same bundle.
	 */
	class DocBundleExporterTest extends ZsfTestCase {
		public function test_RoundTrip() : void {
			$importer = new DocBundleImporter(self::$db, self::$log);
			$exporter = new DocBundleExporter(self::$db, self::$log);

			self::assertTrue($importer->reset()->isGood());
			self::assertTrue($importer->importFile(STOIC_CORE_PATH . 'fixtures/tessel.json')->isGood());

			$first = $exporter->export();

			self::assertCount(6, $first['versions']);
			self::assertCount(5, $first['modules']);
			self::assertCount(14, $first['pages']);
			self::assertCount(11, $first['changes']);
			self::assertCount(1, array_filter($first['pages'], fn ($p) => $p['mode'] === 'home' && $p['slug'] === 'index'), "the landing page round-trips like any other page");
			self::assertCount(1, $first['courses']);
			self::assertCount(5, $first['courses'][0]['lessons']);

			$query = array_values(array_filter($first['modules'], fn ($m) => $m['path'] === 'tessel/query'))[0];
			$options = array_values(array_filter($query['symbols'], fn ($s) => $s['name'] === 'QueryOptions'))[0];
			self::assertCount(6, $options['children']);
			self::assertCount(3, $options['versions']);
			self::assertEquals('v4.0', $options['versions'][0]['removed']);

			$createQuery = array_values(array_filter($first['pages'], fn ($p) => $p['slug'] === 'tessel/query/createQuery'))[0];
			self::assertEquals('v2.0', $createQuery['introduced']);
			self::assertContains(['ref' => 'tessel/query#createQuery', 'role' => 'subject'], $createQuery['symbols']);
			self::assertEquals('user.ts', array_values(array_filter($createQuery['samples'], fn ($s) => $s['language'] === 'ts' && $s['key'] === 'minimal'))[0]['title']);

			self::assertEquals('learn/your-first-query', $first['courses'][0]['lessons'][0]['page']);

			self::assertTrue($importer->reset()->isGood());
			self::assertTrue($importer->importBundle($first)->isGood(), "an export imports cleanly");

			$second = $exporter->export();

			self::assertEquals(json_encode($first), json_encode($second), "export → import → export is stable");

			self::assertTrue($importer->reset()->isGood());

			return;
		}
	}
