<?php

	use Zibings\DocPageSymbol;
	use Zibings\DocPageSymbolRoles;

	class DocPageSymbolClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log);
			$mod  = DocTestHelper::makeModule(self::$db, self::$log);
			$sym  = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);
			$page = DocTestHelper::makePage(self::$db, self::$log, $v1);

			$link           = new DocPageSymbol(self::$db, self::$log);
			$link->pageId   = $page->id;
			$link->symbolId = $sym->id;
			$link->role     = DocPageSymbolRoles::SUBJECT;
			self::assertTrue($link->create()->isGood());

			$again           = new DocPageSymbol(self::$db, self::$log);
			$again->pageId   = $page->id;
			$again->symbolId = $sym->id;
			self::assertTrue($again->read()->isGood());
			self::assertEquals(DocPageSymbolRoles::SUBJECT, $again->role);

			$again->role = 'bogus';
			self::assertFalse($again->update()->isGood());

			$again->role = DocPageSymbolRoles::MENTIONS;
			self::assertTrue($again->update()->isGood());

			self::assertTrue($link->delete()->isGood());

			$page->delete();
			$sym->delete();
			$mod->delete();
			$v1->delete();

			return;
		}
	}
