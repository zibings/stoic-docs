<?php

	use Zibings\DocPage;
	use Zibings\DocPageModes;

	class DocPageClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log);
			$page = DocTestHelper::makePage(self::$db, self::$log, $v1, DocPageModes::EXPLAIN);

			$again = DocPage::fromId($page->id, self::$db, self::$log);
			self::assertEquals($page->slug, $again->slug);
			self::assertNull($again->minutes);

			$dup                      = new DocPage(self::$db, self::$log);
			$dup->mode                = $page->mode;
			$dup->slug                = $page->slug;
			$dup->title               = 'dup';
			$dup->introducedVersionId = $v1->id;
			self::assertFalse($dup->create()->isGood(), "duplicate mode+slug+version must be rejected");

			$invalid                      = new DocPage(self::$db, self::$log);
			$invalid->mode                = 'nope';
			$invalid->slug                = uniqid();
			$invalid->title               = 'x';
			$invalid->introducedVersionId = $v1->id;
			self::assertFalse($invalid->create()->isGood(), "invalid mode must be rejected");

			$page->minutes = 7;
			$page->body    = 'changed';
			self::assertTrue($page->update()->isGood());
			self::assertEquals(7, DocPage::fromId($page->id, self::$db, self::$log)->minutes);

			self::assertTrue($page->delete()->isGood());
			$v1->delete();

			return;
		}

		public function test_HomeMode() : void {
			self::assertTrue(DocPageModes::isValid(DocPageModes::HOME), "home is a storable mode");
			self::assertContains(DocPageModes::HOME, DocPageModes::all());
			self::assertNotContains(DocPageModes::HOME, DocPageModes::readerModes(), "home is never a header tab");
			self::assertCount(4, DocPageModes::readerModes());

			$v1   = DocTestHelper::makeVersion(self::$db, self::$log);
			$page = DocTestHelper::makePage(self::$db, self::$log, $v1, DocPageModes::HOME);
			self::assertEquals(DocPageModes::HOME, DocPage::fromId($page->id, self::$db, self::$log)->mode);

			$page->delete();
			$v1->delete();

			return;
		}
	}
