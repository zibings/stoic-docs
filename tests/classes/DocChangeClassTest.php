<?php

	use Zibings\DocChange;
	use Zibings\DocChangeKinds;
	use Zibings\DocChangeSymbol;

	class DocChangeClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$v1  = DocTestHelper::makeVersion(self::$db, self::$log);
			$mod = DocTestHelper::makeModule(self::$db, self::$log);
			$sym = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);

			$change            = new DocChange(self::$db, self::$log);
			$change->versionId = $v1->id;
			$change->kind      = 'bogus';
			$change->title     = 'Something';
			self::assertFalse($change->create()->isGood(), "invalid kind must be rejected");

			$change->kind = DocChangeKinds::BREAKING;
			self::assertTrue($change->create()->isGood());

			$link           = new DocChangeSymbol(self::$db, self::$log);
			$link->changeId = $change->id;
			$link->symbolId = $sym->id;
			self::assertTrue($link->create()->isGood());
			self::assertTrue($link->read()->isGood());

			$change->codemodCmd = 'pnpm dlx migrate';
			self::assertTrue($change->update()->isGood());

			$again = DocChange::fromId($change->id, self::$db, self::$log);
			self::assertEquals('pnpm dlx migrate', $again->codemodCmd);
			self::assertNull($again->rfcUrl);

			self::assertTrue($change->delete()->isGood());
			self::assertFalse($link->read()->isGood(), "links cascade with change");

			$sym->delete();
			$mod->delete();
			$v1->delete();

			return;
		}
	}
