<?php

	use Zibings\DocBundleImporter;
	use Zibings\DocChanges;
	use Zibings\DocContextOptionKinds;
	use Zibings\DocContextOptions;
	use Zibings\DocCourse;
	use Zibings\DocCourses;
	use Zibings\DocPageModes;
	use Zibings\DocPages;
	use Zibings\DocSymbols;
	use Zibings\DocSymbolStates;
	use Zibings\DocVersion;
	use Zibings\DocVersions;

	/**
	 * Imports the tessel fixture into the test database and exercises the queries the UI depends on against it.
	 * Resets the docs tables when done, so it must not run alongside data other docs tests rely on.
	 */
	class DocBundleImporterTest extends ZsfTestCase {
		public function test_ImportFixtureAndQuery() : void {
			$importer = new DocBundleImporter(self::$db, self::$log);

			self::assertTrue($importer->reset()->isGood());

			$result = $importer->importFile(STOIC_CORE_PATH . 'fixtures/tessel.json');
			self::assertTrue($result->isGood(), $result->hasMessages() ? $result->getMessages()[0] : 'import failed');
			self::assertEquals(6, $importer->counts['versions']);
			self::assertEquals(5, $importer->counts['modules']);
			self::assertEquals(11, $importer->counts['changes']);
			self::assertEquals(5, $importer->counts['lessons']);

			$versions = new DocVersions(self::$db, self::$log);
			$symbols  = new DocSymbols(self::$db, self::$log);
			$pages    = new DocPages(self::$db, self::$log);
			$changes  = new DocChanges(self::$db, self::$log);

			$latest = $versions->getLatest();
			self::assertEquals('v4.2', $latest->label);

			$v38 = DocVersion::fromLabel('v3.8', self::$db, self::$log);
			$v40 = DocVersion::fromLabel('v4.0', self::$db, self::$log);
			$v42 = DocVersion::fromLabel('v4.2', self::$db, self::$log);

			// browse tree at v4.2: five modules, tessel/query has 7 top-level symbols incl. the removed one
			$tree = $symbols->getTreeAtVersion($v42->id);
			self::assertCount(5, $tree);
			self::assertEquals('tessel/query', $tree[0]['module']->path);
			self::assertCount(7, $tree[0]['symbols']);

			$byName = [];

			foreach ($tree[0]['symbols'] as $node) {
				$byName[$node->symbol->name] = $node;
			}

			self::assertEquals(DocSymbolStates::REMOVED, $byName['useLegacyCache']->state);
			self::assertEquals($v40->id, $byName['useLegacyCache']->removedVersionId);
			self::assertEquals($v40->id, $byName['createQuery']->changedVersionId);
			self::assertEquals(DocVersion::fromLabel('v2.0', self::$db, self::$log)->id, $byName['createQuery']->sinceVersionId);
			self::assertCount(6, $byName['QueryOptions']->children);

			$treeNoRemoved = $symbols->getTreeAtVersion($v42->id, false);
			self::assertCount(6, $treeNoRemoved[0]['symbols']);

			// contract follows version
			$staleTime = $symbols->findByRef('tessel/query#QueryOptions.staleTime');
			self::assertGreaterThan(0, $staleTime->id);
			self::assertStringContainsString('= 0', $symbols->getContractAtVersion($staleTime->id, $v38->id)->signature);
			self::assertStringContainsString('30_000', $symbols->getContractAtVersion($staleTime->id, $v42->id)->signature);

			// reference page + samples + linked pages
			$createQuery = $symbols->findByRef('tessel/query#createQuery');
			$refPage     = $pages->getReferencePageForSymbol($createQuery->id, $v42->id);
			self::assertEquals('createQuery', $refPage->title);
			self::assertEquals('pnpm add tessel', trim($pages->getSample($refPage->id, 'install', 'ts', 'pnpm')->code));
			self::assertStringContainsString('createQuery([', $pages->getSample($refPage->id, 'minimal', 'js', 'npm')->code);
			self::assertCount(3, $pages->getSymbols($refPage->id));

			$alsoCoveredIn = $pages->getPagesForSymbol($symbols->findByRef('tessel/query#prefetch')->id, $v42->id);
			self::assertGreaterThanOrEqual(3, count($alsoCoveredIn));

			// upgrade range 3.8 -> 4.2 has 11 changes, 4 breaking
			$between = $changes->getBetween($v38->id, $v42->id);
			self::assertCount(11, $between);
			self::assertEquals(4, $changes->getCountsByKind($v38->id, $v42->id)['breaking']);

			$queryOptions = $symbols->findByRef('tessel/query#QueryOptions');
			$diff         = $changes->getContractDiff($queryOptions->id, $v38->id, $v42->id);
			self::assertTrue($diff->hasChanges());
			self::assertEquals('staleTime', $diff->paramsChanged[0]['name']);

			// course spine
			$course  = DocCourse::fromSlug('tessel-in-an-afternoon', self::$db, self::$log);
			$lessons = (new DocCourses(self::$db, self::$log))->getLessons($course->id);
			self::assertCount(5, $lessons);
			self::assertEquals('Keep data fresh without refetching', $lessons[2]['page']->title);
			self::assertCount(3, (new DocCourses(self::$db, self::$log))->getSteps($lessons[2]['lesson']->id));

			// context options
			self::assertEquals('ts', (new DocContextOptions(self::$db, self::$log))->getDefault(DocContextOptionKinds::LANGUAGE)->optionKey);

			// re-import is an upsert, not a duplicate
			$again = $importer->importFile(STOIC_CORE_PATH . 'fixtures/tessel.json');
			self::assertTrue($again->isGood());
			self::assertCount(6, $versions->getAll());
			self::assertCount(11, $changes->getBetween($v38->id, $v42->id));

			self::assertTrue($importer->reset()->isGood());
			self::assertCount(0, $versions->getAll());

			return;
		}
	}
