<?php

	use Zibings\DocModule;

	class DocModuleClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$cls = DocTestHelper::makeModule(self::$db, self::$log);

			$byPath = DocModule::fromPath($cls->path, self::$db, self::$log);
			self::assertEquals($cls->id, $byPath->id);

			$dup       = new DocModule(self::$db, self::$log);
			$dup->path = $cls->path;
			self::assertFalse($dup->create()->isGood(), "duplicate path must be rejected");

			$cls->summary = 'updated';
			self::assertTrue($cls->update()->isGood());
			self::assertEquals('updated', DocModule::fromId($cls->id, self::$db, self::$log)->summary);

			self::assertTrue($cls->delete()->isGood());
			self::assertEquals(0, DocModule::fromId($cls->id, self::$db, self::$log)->id);

			return;
		}
	}
