<?php

	use Zibings\DocVersion;

	class DocVersionClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$cls = DocTestHelper::makeVersion(self::$db, self::$log);

			self::assertGreaterThan(0, $cls->id);
			self::assertTrue($cls->isSupported);
			self::assertFalse($cls->isLatest);

			$byId = DocVersion::fromId($cls->id, self::$db, self::$log);
			self::assertEquals($cls->tag, $byId->tag);

			$byLabel = DocVersion::fromLabel($cls->label, self::$db, self::$log);
			self::assertEquals($cls->id, $byLabel->id);

			$byTag = DocVersion::fromTag($cls->tag, self::$db, self::$log);
			self::assertEquals($cls->id, $byTag->id);

			$dup          = new DocVersion(self::$db, self::$log);
			$dup->tag     = $cls->tag;
			$dup->label   = 'other-' . uniqid();
			$dup->sortKey = $cls->sortKey + 1;
			self::assertFalse($dup->create()->isGood(), "duplicate tag must be rejected");

			$cls->releasedAt = new \DateTimeImmutable('2025-01-02 03:04:05', new \DateTimeZone('UTC'));
			$cls->isLatest   = true;
			self::assertTrue($cls->update()->isGood());

			$again = DocVersion::fromId($cls->id, self::$db, self::$log);
			self::assertEquals('2025-01-02', $again->releasedAt->format('Y-m-d'));
			self::assertTrue($again->isLatest);

			self::assertTrue($cls->delete()->isGood());
			self::assertEquals(0, DocVersion::fromId($cls->id, self::$db, self::$log)->id);

			return;
		}
	}
