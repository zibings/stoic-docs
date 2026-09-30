<?php

	use Zibings\DocSymbolVersion;

	class DocSymbolVersionClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$v1  = DocTestHelper::makeVersion(self::$db, self::$log);
			$mod = DocTestHelper::makeModule(self::$db, self::$log);
			$sym = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);

			$sv = DocTestHelper::makeContract(self::$db, self::$log, $sym, $v1, null, [
				['name' => 'key', 'type' => 'string', 'required' => true, 'default' => null, 'description' => 'the key']
			]);
			$sv->setReturns(['type' => 'void', 'description' => 'nothing']);
			$sv->setThrows([['type' => 'SomeError', 'description' => 'when bad']]);
			$sv->sourcePath = 'src/a.ts';
			$sv->sourceLine = 12;
			self::assertTrue($sv->update()->isGood());

			$again = DocSymbolVersion::fromId($sv->id, self::$db, self::$log);
			self::assertEquals('key', $again->getParams()[0]['name']);
			self::assertEquals('void', $again->getReturns()['type']);
			self::assertEquals('SomeError', $again->getThrows()[0]['type']);
			self::assertEquals(12, $again->sourceLine);
			self::assertNull($again->removedVersionId);

			$serialized = $again->toSerializableArray();
			self::assertArrayHasKey('params', $serialized);
			self::assertArrayNotHasKey('paramsJson', $serialized);

			$bad             = new DocSymbolVersion(self::$db, self::$log);
			$bad->symbolId   = $sym->id;
			$bad->introducedVersionId = $v1->id;
			$bad->paramsJson = '{not json';
			self::assertFalse($bad->create()->isGood(), "invalid JSON must be rejected");

			self::assertTrue($sv->delete()->isGood());

			$sym->delete();
			$mod->delete();
			$v1->delete();

			return;
		}
	}
