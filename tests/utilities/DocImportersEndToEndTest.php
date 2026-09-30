<?php

	use Zibings\DocBundleImporter;
	use Zibings\DocOpenApiExtractor;
	use Zibings\DocSnapshotMerger;
	use Zibings\DocsReader;
	use Zibings\DocVersion;

	/**
	 * A generated bundle imports cleanly and the reader serves version-aware contracts from it.
	 * Resets the docs tables before and after, like the other importer tests.
	 */
	class DocImportersEndToEndTest extends ZsfTestCase {
		public function test_OpenApiBundleRoundTrip() : void {
			$extractor = new DocOpenApiExtractor();
			$bundle    = (new DocSnapshotMerger())->merge([
				$extractor->snapshot(DocOpenApiExtractor::load(STOIC_CORE_PATH . 'tests/fixtures/openapi/v1.yaml')),
				$extractor->snapshot(DocOpenApiExtractor::load(STOIC_CORE_PATH . 'tests/fixtures/openapi/v2.yaml'))
			], 'test');

			$importer = new DocBundleImporter(self::$db, self::$log);
			self::assertTrue($importer->reset()->isGood());

			$result = $importer->importBundle($bundle);
			self::assertTrue($result->isGood(), $result->hasMessages() ? $result->getMessages()[0] : 'import failed');
			self::assertEquals(2, $importer->counts['versions']);
			self::assertEquals(3, $importer->counts['modules']);

			$reader = new DocsReader(self::$db, self::$log);
			$v1     = DocVersion::fromLabel('v1.0.0', self::$db, self::$log);
			$v2     = DocVersion::fromLabel('v2.0.0', self::$db, self::$log);

			// the endpoint that changed shows the new contract at v2 and the old one at v1
			$create = $reader->symbol($v2, 'api/widgets', 'createWidget');
			self::assertNotNull($create);
			self::assertEquals('POST /Widgets', $create['symbol']['contract']['signature']);
			self::assertEquals(['name', 'color', 'size'], array_column($create['symbol']['contract']['params'], 'name'));
			self::assertEquals('v1.0.0', $create['symbol']['sinceLabel']);
			self::assertEquals('v2.0.0', $create['symbol']['changedLabel']);

			$createOld = $reader->symbol($v1, 'api/widgets', 'createWidget');
			self::assertEquals(['name', 'color'], array_column($createOld['symbol']['contract']['params'], 'name'));
			self::assertNull($createOld['symbol']['changedLabel']);

			// the removed endpoint is struck through at v2 and the diff reports the contract change
			$tree = $reader->tree($v2);
			$names = [];

			foreach ($tree['modules'] as $module) {
				foreach ($module['symbols'] as $node) {
					$names[$node['shortName']] = $node['state'];
				}
			}

			self::assertEquals('removed', $names['get_Legacy_Ping']);
			self::assertEquals('current', $names['listWidgets']);

			// manifest routes include the schema property child
			$manifest = $reader->manifest($v2);
			self::assertContains('/v2.0.0/reference/api/schemas/Widget.createdAt', $manifest['routes']);

			self::assertTrue($importer->reset()->isGood());

			return;
		}
	}
