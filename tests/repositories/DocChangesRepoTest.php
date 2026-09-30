<?php

	use Zibings\DocChange;
	use Zibings\DocChangeKinds;
	use Zibings\DocChangeSymbol;
	use Zibings\DocChanges;

	class DocChangesRepoTest extends ZsfTestCase {
		public function test_RangeAndDiff() : void {
			$repo = new DocChanges(self::$db, self::$log);
			$base = 7000000 + random_int(0, 900000);
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 1);
			$v2   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 2);
			$v3   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 3);
			$mod  = DocTestHelper::makeModule(self::$db, self::$log);
			$sym  = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);

			DocTestHelper::makeContract(self::$db, self::$log, $sym, $v1, $v2, [
				['name' => 'staleTime', 'type' => 'number', 'required' => false, 'default' => '0', 'description' => ''],
				['name' => 'legacy', 'type' => 'boolean', 'required' => false, 'default' => 'false', 'description' => '']
			]);
			DocTestHelper::makeContract(self::$db, self::$log, $sym, $v2, null, [
				['name' => 'staleTime', 'type' => 'number', 'required' => false, 'default' => '30_000', 'description' => ''],
				['name' => 'signal', 'type' => 'AbortSignal', 'required' => false, 'default' => null, 'description' => '']
			]);

			$make = function (int $versionId, string $kind, string $title) use ($sym) : DocChange {
				$c            = new DocChange(self::$db, self::$log);
				$c->versionId = $versionId;
				$c->kind      = $kind;
				$c->title     = $title;
				$c->create();

				$link           = new DocChangeSymbol(self::$db, self::$log);
				$link->changeId = $c->id;
				$link->symbolId = $sym->id;
				$link->create();

				return $c;
			};

			$c1 = $make($v1->id, DocChangeKinds::ADDED, 'in v1 (excluded)');
			$c2 = $make($v2->id, DocChangeKinds::ADDED, 'added in v2');
			$c3 = $make($v2->id, DocChangeKinds::BREAKING, 'breaking in v2');
			$c4 = $make($v3->id, DocChangeKinds::BEHAVIOR, 'behavior in v3');

			$between = $repo->getBetween($v1->id, $v3->id);
			self::assertEquals([$c3->id, $c2->id, $c4->id], array_map(fn ($c) => $c->id, $between), "ordered by version then kind order");

			$counts = $repo->getCountsByKind($v1->id, $v3->id);
			self::assertEquals(1, $counts[DocChangeKinds::BREAKING]);
			self::assertEquals(1, $counts[DocChangeKinds::ADDED]);
			self::assertEquals(0, $counts[DocChangeKinds::DEPRECATED]);

			self::assertCount(2, $repo->getForVersion($v2->id));
			self::assertCount(4, $repo->getChangesForSymbol($sym->id));
			self::assertEquals($sym->id, $repo->getSymbols($c3->id)[0]->id);

			$diff = $repo->getContractDiff($sym->id, $v1->id, $v3->id);
			self::assertTrue($diff->hasChanges());
			self::assertEquals('signal', $diff->paramsAdded[0]['name']);
			self::assertEquals('legacy', $diff->paramsRemoved[0]['name']);
			self::assertEquals('staleTime', $diff->paramsChanged[0]['name']);
			self::assertEquals('30_000', $diff->paramsChanged[0]['after']['default']);
			self::assertArrayHasKey('before', $diff->toArray());

			$same = $repo->getContractDiff($sym->id, $v2->id, $v3->id);
			self::assertFalse($same->hasChanges());

			$mod->delete();
			$v3->delete();
			$v2->delete();
			$v1->delete();

			return;
		}
	}
