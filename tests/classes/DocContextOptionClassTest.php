<?php

	use Zibings\DocContextOption;
	use Zibings\DocContextOptionKinds;

	class DocContextOptionClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$opt            = new DocContextOption(self::$db, self::$log);
			$opt->kind      = DocContextOptionKinds::LANGUAGE;
			$opt->optionKey = 'lang-' . uniqid();
			$opt->label     = 'A language';
			self::assertTrue($opt->create()->isGood());

			$found = DocContextOption::fromKey(DocContextOptionKinds::LANGUAGE, $opt->optionKey, self::$db, self::$log);
			self::assertEquals($opt->id, $found->id);

			$dup            = new DocContextOption(self::$db, self::$log);
			$dup->kind      = DocContextOptionKinds::LANGUAGE;
			$dup->optionKey = $opt->optionKey;
			$dup->label     = 'dup';
			self::assertFalse($dup->create()->isGood(), "duplicate kind+key must be rejected");

			$dup->kind = DocContextOptionKinds::PACKAGE_MANAGER;
			self::assertTrue($dup->create()->isGood(), "same key under another kind is fine");

			$opt->isDefault = true;
			self::assertTrue($opt->update()->isGood());
			self::assertTrue(DocContextOption::fromId($opt->id, self::$db, self::$log)->isDefault);

			self::assertTrue($opt->delete()->isGood());
			self::assertTrue($dup->delete()->isGood());

			return;
		}
	}
