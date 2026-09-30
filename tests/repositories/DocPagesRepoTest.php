<?php

	use Zibings\DocCodeSample;
	use Zibings\DocPageModes;
	use Zibings\DocPages;
	use Zibings\DocPageSymbol;
	use Zibings\DocPageSymbolRoles;

	class DocPagesRepoTest extends ZsfTestCase {
		public function test_VersionedLookupsAndSamples() : void {
			$repo = new DocPages(self::$db, self::$log);
			$base = 8000000 + random_int(0, 900000);
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 1);
			$v2   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 2);
			$mod  = DocTestHelper::makeModule(self::$db, self::$log);
			$sym  = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);

			$old                   = DocTestHelper::makePage(self::$db, self::$log, $v1, DocPageModes::REFERENCE);
			$old->removedVersionId = $v2->id;
			$old->update();

			$new       = DocTestHelper::makePage(self::$db, self::$log, $v2, DocPageModes::REFERENCE);
			$new->slug = $old->slug;
			$new->update();

			foreach ([$old, $new] as $page) {
				$link           = new DocPageSymbol(self::$db, self::$log);
				$link->pageId   = $page->id;
				$link->symbolId = $sym->id;
				$link->role     = DocPageSymbolRoles::SUBJECT;
				$link->create();
			}

			self::assertEquals($old->id, $repo->getAtVersion(DocPageModes::REFERENCE, $old->slug, $v1->id)->id);
			self::assertEquals($new->id, $repo->getAtVersion(DocPageModes::REFERENCE, $old->slug, $v2->id)->id);
			self::assertEquals(0, $repo->getAtVersion(DocPageModes::DO, $old->slug, $v2->id)->id);

			self::assertEquals($new->id, $repo->getReferencePageForSymbol($sym->id, $v2->id)->id);
			self::assertEquals($old->id, $repo->getReferencePageForSymbol($sym->id, $v1->id)->id);

			$forSymbol = $repo->getPagesForSymbol($sym->id, $v2->id);
			self::assertCount(1, $forSymbol);
			self::assertEquals(DocPageSymbolRoles::SUBJECT, $forSymbol[0]['role']);

			self::assertEquals($sym->id, $repo->getSubjectSymbol($new->id)->id);

			$byMode = array_map(fn ($p) => $p->id, $repo->getByMode(DocPageModes::REFERENCE, $v2->id));
			self::assertContains($new->id, $byMode);
			self::assertNotContains($old->id, $byMode);

			foreach ([['ts', null, 'generic'], ['ts', 'pnpm', 'pnpm specific']] as [$lang, $variant, $code]) {
				$s            = new DocCodeSample(self::$db, self::$log);
				$s->pageId    = $new->id;
				$s->sampleKey = 'install';
				$s->language  = $lang;
				$s->variant   = $variant;
				$s->code      = $code;
				$s->create();
			}

			self::assertEquals('pnpm specific', $repo->getSample($new->id, 'install', 'ts', 'pnpm')->code);
			self::assertEquals('generic', $repo->getSample($new->id, 'install', 'ts', 'yarn')->code, "falls back to variant-less sample");
			self::assertEquals(0, $repo->getSample($new->id, 'install', 'php', null)->id);
			self::assertCount(2, $repo->getSamples($new->id));

			$new->delete();
			$old->delete();
			$mod->delete();
			$v2->delete();
			$v1->delete();

			return;
		}
	}
