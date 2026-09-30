<?php

	use Zibings\DocCodeSample;

	class DocCodeSampleClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log);
			$page = DocTestHelper::makePage(self::$db, self::$log, $v1);

			$sample            = new DocCodeSample(self::$db, self::$log);
			$sample->pageId    = $page->id;
			$sample->sampleKey = 'minimal';
			$sample->language  = 'ts';
			$sample->code      = 'const x = 1;';
			self::assertTrue($sample->create()->isGood());

			$again = DocCodeSample::fromId($sample->id, self::$db, self::$log);
			self::assertNull($again->variant);
			self::assertFalse($again->isTested);

			$sample->variant               = 'pnpm';
			$sample->isTested              = true;
			$sample->lastTestPassVersionId = $v1->id;
			self::assertTrue($sample->update()->isGood());

			$again = DocCodeSample::fromId($sample->id, self::$db, self::$log);
			self::assertEquals('pnpm', $again->variant);
			self::assertTrue($again->isTested);
			self::assertEquals($v1->id, $again->lastTestPassVersionId);

			self::assertTrue($page->delete()->isGood());
			self::assertEquals(0, DocCodeSample::fromId($sample->id, self::$db, self::$log)->id, "samples cascade with page");

			$v1->delete();

			return;
		}
	}
