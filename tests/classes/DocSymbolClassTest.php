<?php

	use Zibings\DocSymbol;

	class DocSymbolClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$mod   = DocTestHelper::makeModule(self::$db, self::$log);
			$sym   = DocTestHelper::makeSymbol(self::$db, self::$log, $mod, 'type');
			$child = DocTestHelper::makeSymbol(self::$db, self::$log, $mod, 'option', $sym->id);

			$found = DocSymbol::fromName($mod->id, $sym->name, null, self::$db, self::$log);
			self::assertEquals($sym->id, $found->id);
			self::assertNull($found->parentSymbolId);

			$foundChild = DocSymbol::fromName($mod->id, $child->name, $sym->id, self::$db, self::$log);
			self::assertEquals($child->id, $foundChild->id);
			self::assertEquals($sym->id, $foundChild->parentSymbolId);

			$dup           = new DocSymbol(self::$db, self::$log);
			$dup->moduleId = $mod->id;
			$dup->name     = $sym->name;
			$dup->kind     = 'fn';
			self::assertFalse($dup->create()->isGood(), "duplicate sibling name must be rejected");

			$sameNameOtherParent                 = new DocSymbol(self::$db, self::$log);
			$sameNameOtherParent->moduleId       = $mod->id;
			$sameNameOtherParent->parentSymbolId = $sym->id;
			$sameNameOtherParent->name           = $sym->name;
			$sameNameOtherParent->kind           = 'method';
			self::assertTrue($sameNameOtherParent->create()->isGood(), "same name under a different parent is fine");

			$sym->kind = 'class';
			self::assertTrue($sym->update()->isGood());
			self::assertEquals('class', DocSymbol::fromId($sym->id, self::$db, self::$log)->kind);

			self::assertTrue($sym->delete()->isGood());
			self::assertEquals(0, DocSymbol::fromId($child->id, self::$db, self::$log)->id, "children cascade");

			$mod->delete();

			return;
		}
	}
