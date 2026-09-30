<?php

	use Zibings\DocBundleImporter;
	use Zibings\DocsAdminException;
	use Zibings\DocsAdminWriter;
	use Zibings\DocVersion;

	/**
	 * Exercises the admin write service against the tessel fixture. Resets the docs tables when done.
	 */
	class DocsAdminWriterTest extends ZsfTestCase {
		public function test_CrudReorderLinksAndValidation() : void {
			$importer = new DocBundleImporter(self::$db, self::$log);
			self::assertTrue($importer->reset()->isGood());
			self::assertTrue($importer->importFile(STOIC_CORE_PATH . 'fixtures/tessel.json')->isGood());

			$writer = new DocsAdminWriter(self::$db, self::$log);

			// resource names are case-insensitive; unknown ones are 404
			self::assertEquals('contextOptions', $writer->resourceName('contextoptions'));

			try {
				$writer->list('nope');
				self::fail('unknown resource must throw');
			} catch (DocsAdminException $ex) {
				self::assertEquals(404, $ex->status);
			}

			// list + filters
			self::assertCount(6, $writer->list('versions'));
			$query = array_values(array_filter($writer->list('modules'), fn ($m) => $m['path'] === 'tessel/query'))[0];
			$symbols = $writer->list('symbols', ['moduleId' => $query['id'], 'parentSymbolId' => null]);
			self::assertCount(13, $symbols, "all symbols in tessel/query incl. nested options");
			self::assertEquals('tessel/query#createQuery', array_values(array_filter($symbols, fn ($s) => $s['name'] === 'createQuery'))[0]['ref']);

			// create + get + update + delete a version
			$created = $writer->create('versions', ['tag' => '5.0.0', 'label' => 'v5.0', 'sortKey' => 500, 'releasedAt' => '2026-01-15', 'supported' => true, 'isSupported' => 'true']);
			self::assertGreaterThan(0, $created['id']);
			self::assertEquals('2026-01-15 00:00:00', $created['releasedAt']);
			self::assertEquals($created, $writer->get('versions', $created['id']));

			$updated = $writer->update('versions', $created['id'], ['label' => 'v5.0-rc', 'releasedAt' => null]);
			self::assertEquals('v5.0-rc', $updated['label']);
			self::assertNull($updated['releasedAt']);

			try {
				$writer->create('versions', ['tag' => '4.2.0', 'label' => 'dup', 'sortKey' => 999]);
				self::fail('duplicate tag must be rejected');
			} catch (DocsAdminException $ex) {
				self::assertEquals(400, $ex->status);
				self::assertStringContainsString('duplicate', strtolower($ex->getMessage()));
			}

			$writer->markLatest($created['id']);
			self::assertTrue($writer->get('versions', $created['id'])['isLatest']);
			self::assertFalse(DocVersion::fromLabel('v4.2', self::$db, self::$log)->isLatest);

			$writer->delete('versions', $created['id']);

			try {
				$writer->get('versions', $created['id']);
				self::fail('deleted row must be gone');
			} catch (DocsAdminException $ex) {
				self::assertEquals(404, $ex->status);
			}

			// a referenced version cannot be deleted
			try {
				$writer->delete('versions', DocVersion::fromLabel('v4.0', self::$db, self::$log)->id);
				self::fail('referenced version must be rejected');
			} catch (DocsAdminException $ex) {
				self::assertEquals(400, $ex->status);
			}

			// contracts: coercion of json fields and overlap validation
			$createQuery = array_values(array_filter($symbols, fn ($s) => $s['name'] === 'createQuery'))[0];
			$v42         = DocVersion::fromLabel('v4.2', self::$db, self::$log);
			$v41         = DocVersion::fromLabel('v4.1', self::$db, self::$log);

			try {
				$writer->create('contracts', ['symbolId' => $createQuery['id'], 'introducedVersionId' => $v41->id, 'signature' => 'x', 'params' => []]);
				self::fail('overlapping contract must be rejected');
			} catch (DocsAdminException $ex) {
				self::assertStringContainsString('overlaps', $ex->getMessage());
			}

			try {
				$writer->create('contracts', ['symbolId' => $createQuery['id'], 'introducedVersionId' => $v42->id, 'removedVersionId' => $v41->id, 'signature' => 'x']);
				self::fail('reversed range must be rejected');
			} catch (DocsAdminException $ex) {
				self::assertStringContainsString('newer', $ex->getMessage());
			}

			$contracts = $writer->list('contracts', ['symbolId' => $createQuery['id']]);
			self::assertCount(2, $contracts);
			self::assertIsArray($contracts[0]['params']);

			$patched = $writer->update('contracts', $contracts[1]['id'], ['params' => [['name' => 'key', 'type' => 'QueryKey', 'required' => true, 'default' => null, 'description' => 'edited']]]);
			self::assertEquals('edited', $patched['params'][0]['description']);

			try {
				$writer->update('contracts', $contracts[1]['id'], ['params' => 'not-a-list']);
				self::fail('json field must be an array');
			} catch (DocsAdminException $ex) {
				self::assertStringContainsString('params', $ex->getMessage());
			}

			// reorder lessons (unique ordinals) and reject non-orderable resources
			$course  = $writer->list('courses')[0];
			$lessons = $writer->list('lessons', ['courseId' => $course['id']]);
			$reversed = array_reverse(array_map(fn ($l) => $l['id'], $lessons));
			$reordered = $writer->reorder('lessons', $reversed);
			self::assertEquals([1, 2, 3, 4, 5], array_map(fn ($l) => $l['ordinal'], $reordered));
			self::assertEquals($reversed, array_map(fn ($l) => $l['id'], $reordered));

			try {
				$writer->reorder('versions', [1]);
				self::fail('versions cannot be reordered');
			} catch (DocsAdminException $ex) {
				self::assertEquals(400, $ex->status);
			}

			// page symbol links replaced as a set, by id or ref
			$page = array_values(array_filter($writer->list('pages', ['mode' => 'do']), fn ($p) => $p['slug'] === 'prefetch-on-hover'))[0];
			self::assertCount(1, $writer->getPageSymbols($page['id']));

			$links = $writer->setPageSymbols($page['id'], [
				['ref' => 'tessel/query#prefetch', 'role' => 'subject'],
				['symbolId' => $createQuery['id'], 'role' => 'mentions'],
			]);
			self::assertEqualsCanonicalizing(['tessel/query#prefetch', 'tessel/query#createQuery'], array_map(fn ($l) => $l['ref'], $links));
			self::assertEquals('subject', array_values(array_filter($links, fn ($l) => $l['ref'] === 'tessel/query#prefetch'))[0]['role']);

			try {
				$writer->setPageSymbols($page['id'], [['ref' => 'tessel/query#prefetch', 'role' => 'bogus']]);
				self::fail('invalid role must be rejected');
			} catch (DocsAdminException $ex) {
				self::assertStringContainsString('role', $ex->getMessage());
			}

			// change symbol links accept refs, ids, and objects
			$change = $writer->list('changes', ['kind' => 'deprecated'])[0];
			$set    = $writer->setChangeSymbols($change['id'], ['tessel/query#invalidate', $createQuery['id'], ['ref' => 'tessel/query#prefetch']]);
			self::assertCount(3, $set);

			// import returns counts, export produces a bundle
			$counts = $writer->import(['contextOptions' => [['kind' => 'language', 'key' => 'php', 'label' => 'PHP', 'sortOrder' => 3]]]);
			self::assertEquals(1, $counts['contextOptions']);
			self::assertCount(3, $writer->list('contextOptions', ['kind' => 'language']));
			self::assertArrayHasKey('modules', $writer->export());

			self::assertTrue($importer->reset()->isGood());

			return;
		}
	}
