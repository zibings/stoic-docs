<?php

	use Zibings\DocBundleImporter;
	use Zibings\DocsReader;

	/**
	 * Exercises the read-side service behind the public API against the tessel fixture. Imports the fixture into the
	 * test database first and resets the docs tables when done.
	 */
	class DocsReaderTest extends ZsfTestCase {
		public function test_EndpointsAgainstFixture() : void {
			global $Settings;

			$importer = new DocBundleImporter(self::$db, self::$log);
			self::assertTrue($importer->reset()->isGood());
			self::assertTrue($importer->importFile(STOIC_CORE_PATH . 'fixtures/tessel.json')->isGood());

			$reader = new DocsReader(self::$db, self::$log, $Settings);

			// version resolution
			self::assertEquals('v4.2', $reader->resolveVersion('latest')->label);
			self::assertEquals('v4.0', $reader->resolveVersion('v4.0')->label);
			self::assertEquals('v4.0', $reader->resolveVersion('4.0.0')->label);
			self::assertEquals(0, $reader->resolveVersion('v9.9')->id);

			$v38 = $reader->resolveVersion('v3.8');
			$v42 = $reader->resolveVersion('v4.2');

			// site
			$site = $reader->site();
			self::assertEquals('v4.2', $site['latest']);
			self::assertCount(6, $site['versions']);
			self::assertEquals('ts', $site['contextOptions']['languages'][0]['key']);
			self::assertTrue($site['contextOptions']['packageManagers'][0]['isDefault']);
			self::assertEquals(['learn', 'do', 'reference', 'explain'], $site['modes']);
			self::assertNotEmpty($site['libraryName']);
			self::assertArrayHasKey('siteUrl', $site);
			self::assertNull($site['siteUrl'], "unset (<changeme>) site URL reads as null");

			// tree
			$tree = $reader->tree($v42);
			self::assertCount(5, $tree['modules']);
			self::assertEquals('tessel/query', $tree['modules'][0]['path']);
			self::assertEquals(7, $tree['modules'][0]['count']);

			$byName = [];

			foreach ($tree['modules'][0]['symbols'] as $node) {
				$byName[$node['name']] = $node;
			}

			self::assertEquals('removed', $byName['useLegacyCache']['state']);
			self::assertEquals('v4.0', $byName['useLegacyCache']['removedLabel']);
			self::assertEquals('v2.0', $byName['createQuery']['sinceLabel']);
			self::assertEquals('v4.0', $byName['createQuery']['changedLabel']);
			self::assertEquals('/v4.2/reference/tessel/query/createQuery', $byName['createQuery']['route']);
			self::assertCount(6, $byName['QueryOptions']['children']);
			self::assertEquals('QueryOptions.staleTime', $byName['QueryOptions']['children'][1]['name']);
			self::assertEquals(6, $reader->tree($v42, false)['modules'][0]['count']);

			// symbol
			$symbol = $reader->symbol($v42, 'tessel/query', 'createQuery');
			self::assertNotNull($symbol);
			self::assertEquals(['tessel', 'query', 'createQuery'], $symbol['breadcrumb']);
			self::assertEquals('createQuery', $symbol['page']['title']);
			self::assertStringContainsString('{sym:', $symbol['page']['body']);
			self::assertCount(5, $symbol['samples']);
			self::assertContains('user.ts', array_map(fn ($s) => $s['title'], $symbol['samples']));
			self::assertArrayHasKey('QueryOptions', $symbol['relatedTypes'], "parameter type QueryOptions<T> resolves to the sibling type symbol");
			self::assertArrayHasKey('QueryKey', $symbol['relatedTypes'], "parameter type QueryKey resolves too");
			self::assertCount(0, $symbol['relatedTypes']['QueryKey']['children'], "a type without fields expands to nothing");
			self::assertCount(6, $symbol['relatedTypes']['QueryOptions']['children']);
			self::assertEqualsCanonicalizing(['tessel/query#QueryOptions.staleTime', 'tessel/errors#QueryKeyError'], array_map(fn ($m) => $m['ref'], $symbol['mentions']), "symbols the reference prose mentions, across modules");
			self::assertEquals('tessel/query#QueryOptions.staleTime', $symbol['relatedTypes']['QueryOptions']['children'][1]['ref']);
			self::assertEquals(2, $symbol['testedSamples']['total']);
			self::assertEquals(2, $symbol['testedSamples']['passingAtVersion']);
			self::assertCount(7, $symbol['siblings']);
			self::assertNotEmpty($symbol['changes']);
			self::assertEquals('v4.0', $symbol['changes'][0]['versionLabel']);
			self::assertStringContainsString('src/query/create.ts', $symbol['symbol']['contract']['sourceUrl']);
			self::assertStringContainsString('4.2.0', $symbol['symbol']['contract']['sourceUrl'], "source URL uses the version tag");
			self::assertStringContainsString('#L42', $symbol['symbol']['contract']['sourceUrl']);

			$alsoModes = array_map(fn ($p) => $p['mode'], $symbol['alsoCoveredIn']);
			self::assertContains('do', $alsoModes);
			self::assertNotContains('reference', $alsoModes, "the reference page itself is not listed as also-covered-in");

			$nested = $reader->symbol($v42, 'tessel/query', 'QueryOptions.staleTime');
			self::assertNotNull($nested);
			self::assertStringContainsString('30_000', $nested['symbol']['contract']['signature']);
			self::assertEquals(['tessel', 'query', 'QueryOptions', 'staleTime'], $nested['breadcrumb']);
			self::assertCount(7, $nested['siblings'], "nested symbols show the module-level siblings for the rail");

			self::assertStringContainsString('= 0', $reader->symbol($v38, 'tessel/query', 'QueryOptions.staleTime')['symbol']['contract']['signature']);
			self::assertNull($reader->symbol($v38, 'tessel/server', 'createServerClient'), "not yet introduced at v3.8");
			self::assertNull($reader->symbol($v42, 'tessel/query', 'nope'));
			self::assertEquals('removed', $reader->symbol($v42, 'tessel/query', 'useLegacyCache')['symbol']['state'], "removed symbols still resolve");

			// page
			$doPage = $reader->page($v42, 'do', 'prefetch-on-hover');
			self::assertNotNull($doPage);
			self::assertEquals('/v4.2/do/prefetch-on-hover', $doPage['page']['route']);
			self::assertEquals('prefetch', $doPage['symbols'][0]['shortName']);
			self::assertNull($doPage['course']);
			self::assertNull($reader->page($reader->resolveVersion('v2.0'), 'do', 'prefetch-on-hover'), "page introduced at v3.0");
			self::assertNull($reader->page($v42, 'bogus', 'prefetch-on-hover'));

			$learn = $reader->page($v42, 'learn', 'keep-data-fresh-without-refetching');
			self::assertNotNull($learn['course']);
			self::assertEquals(3, $learn['course']['currentLesson']);
			self::assertEquals(5, $learn['course']['lessonCount']);
			self::assertEquals(60, $learn['course']['totalMinutes']);
			self::assertCount(3, $learn['course']['steps']);
			self::assertTrue($learn['course']['lessons'][2]['current']);
			self::assertEquals('/v4.2/learn/tessel-in-an-afternoon/your-first-query', $learn['course']['lessons'][0]['route']);
			self::assertEquals($learn['course']['lessons'][2]['route'], $learn['page']['route']);

			// diff
			$diff = $reader->diff($v38, $v42);
			self::assertEquals(11, $diff['total']);
			self::assertEquals(['v4.0', 'v4.1', 'v4.2'], array_map(fn ($v) => $v['label'], $diff['versions']));
			self::assertEquals('breaking', $diff['groups'][0]['kind']);
			self::assertEquals(4, $diff['groups'][0]['count']);
			self::assertEquals('/upgrade/v3.8/v4.2', $diff['route']);

			$staleChange = $diff['groups'][0]['changes'][0];
			self::assertStringContainsString('staleTime', $staleChange['title']);
			self::assertTrue($staleChange['hasCodemod']);
			self::assertEqualsCanonicalizing(['tessel/query#QueryOptions.staleTime', 'tessel/query#createQuery'], array_map(fn ($s) => $s['ref'], $staleChange['symbols']));

			$staleDiffs = array_values(array_filter($staleChange['contractDiffs'], fn ($d) => $d['ref'] === 'tessel/query#QueryOptions.staleTime'));
			self::assertCount(1, $staleDiffs);
			self::assertStringContainsString('= 0', $staleDiffs[0]['before']['signature']);
			self::assertStringContainsString('30_000', $staleDiffs[0]['after']['signature']);
			self::assertTrue($staleDiffs[0]['signatureChanged']);

			try {
				$reader->diff($v42, $v38);
				self::fail("reversed range must throw");
			} catch (\InvalidArgumentException $ex) {
				self::assertStringContainsString('older', $ex->getMessage());
			}

			// manifest
			$manifest = $reader->manifest($v42);
			self::assertEquals('v4.1', $manifest['previous']['label']);
			self::assertContains('/v4.2/reference/tessel/query/createQuery', $manifest['routes']);
			self::assertContains('/v4.2/reference/tessel/query/QueryOptions.staleTime', $manifest['routes']);
			self::assertContains('/v4.2/do/prefetch-on-hover', $manifest['routes']);
			self::assertContains('/v4.2/learn/tessel-in-an-afternoon/your-first-query', $manifest['routes']);
			self::assertContains('/upgrade/v3.8/v4.2', $manifest['routes']);
			self::assertNotContains('/upgrade/v4.2/v4.2', $manifest['routes']);
			self::assertCount(count(array_unique($manifest['routes'])), $manifest['routes']);
			self::assertCount(1, $manifest['courses']);
			self::assertEquals('tessel-in-an-afternoon', $manifest['courses'][0]['slug']);
			self::assertEquals(5, $manifest['courses'][0]['lessonCount']);
			self::assertEquals('/v4.2/learn/tessel-in-an-afternoon/your-first-query', $manifest['courses'][0]['lessons'][0]['route']);

			$types = array_count_values(array_map(fn ($r) => $r['type'], $manifest['searchRecords']));
			self::assertEquals(32, $types['symbol']);
			self::assertEquals(11, $types['page']);
			self::assertEquals(11, $types['change']);

			$removedRecords = array_values(array_filter($manifest['searchRecords'], fn ($r) => $r['type'] === 'symbol' && $r['state'] === 'removed'));
			self::assertCount(2, $removedRecords);
			self::assertEquals('useLegacyCache removed', $removedRecords[0]['replacedBy']);

			$oldManifest = $reader->manifest($reader->resolveVersion('v2.0'));
			self::assertNull($oldManifest['previous']);
			self::assertNotContains('/v2.0/do/prefetch-on-hover', $oldManifest['routes']);

			self::assertTrue($importer->reset()->isGood());

			return;
		}
	}
