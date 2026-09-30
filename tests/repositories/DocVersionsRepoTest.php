<?php

	use Zibings\DocVersions;

	class DocVersionsRepoTest extends ZsfTestCase {
		public function test_OrderingRangeAndLatest() : void {
			$repo = new DocVersions(self::$db, self::$log);
			$base = 5000000 + random_int(0, 900000);
			$a    = DocTestHelper::makeVersion(self::$db, self::$log, $base + 1);
			$b    = DocTestHelper::makeVersion(self::$db, self::$log, $base + 2);
			$c    = DocTestHelper::makeVersion(self::$db, self::$log, $base + 3);

			$ids = array_map(fn ($v) => $v->id, $repo->getAll());
			$ia  = array_search($a->id, $ids);
			$ib  = array_search($b->id, $ids);
			$ic  = array_search($c->id, $ids);
			self::assertLessThan($ib, $ia, "a sorts before b");
			self::assertLessThan($ic, $ib, "b sorts before c");

			$range = $repo->getRange($a->id, $c->id);
			self::assertEquals([$b->id, $c->id], array_map(fn ($v) => $v->id, $range));

			self::assertTrue($repo->markLatest($b->id));
			self::assertEquals($b->id, $repo->getLatest()->id);

			$map = $repo->getSortKeyMap();
			self::assertEquals($base + 3, $map[$c->id]);

			$c->delete();
			$b->delete();
			$a->delete();

			return;
		}
	}
