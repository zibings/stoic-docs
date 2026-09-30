<?php

	use Zibings\DocModules;
	use Zibings\DocSymbols;
	use Zibings\DocSymbolStates;

	class DocSymbolsRepoTest extends ZsfTestCase {
		public function test_VersionRangeResolution() : void {
			$repo = new DocSymbols(self::$db, self::$log);
			$base = 6000000 + random_int(0, 900000);
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 1);
			$v2   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 2);
			$v3   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 3);
			$v4   = DocTestHelper::makeVersion(self::$db, self::$log, $base + 4);
			$mod  = DocTestHelper::makeModule(self::$db, self::$log);

			// changed: contract A valid v1..v3, contract B valid v3..
			$changed = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);
			$cA      = DocTestHelper::makeContract(self::$db, self::$log, $changed, $v1, $v3, [['name' => 'x', 'type' => 'number', 'required' => false, 'default' => '0', 'description' => '']]);
			$cB      = DocTestHelper::makeContract(self::$db, self::$log, $changed, $v3, null, [['name' => 'x', 'type' => 'number', 'required' => false, 'default' => '30', 'description' => '']]);

			// removed at v3
			$removed = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);
			DocTestHelper::makeContract(self::$db, self::$log, $removed, $v1, $v3);

			// introduced later, at v4
			$late = DocTestHelper::makeSymbol(self::$db, self::$log, $mod);
			DocTestHelper::makeContract(self::$db, self::$log, $late, $v4);

			// child of changed
			$child = DocTestHelper::makeSymbol(self::$db, self::$log, $mod, 'option', $changed->id);
			DocTestHelper::makeContract(self::$db, self::$log, $child, $v2);

			// contract at version
			self::assertEquals($cA->id, $repo->getContractAtVersion($changed->id, $v1->id)->id);
			self::assertEquals($cA->id, $repo->getContractAtVersion($changed->id, $v2->id)->id);
			self::assertEquals($cB->id, $repo->getContractAtVersion($changed->id, $v3->id)->id);
			self::assertEquals($cB->id, $repo->getContractAtVersion($changed->id, $v4->id)->id);
			self::assertEquals(0, $repo->getContractAtVersion($removed->id, $v3->id)->id, "removed symbol has no contract at removal version");
			self::assertEquals(0, $repo->getContractAtVersion($late->id, $v2->id)->id, "not yet introduced");

			// node badges
			$nodeV2 = $repo->getNodeAtVersion($changed->id, $v2->id);
			self::assertEquals(DocSymbolStates::CURRENT, $nodeV2->state);
			self::assertEquals($v1->id, $nodeV2->sinceVersionId);
			self::assertNull($nodeV2->changedVersionId);
			self::assertCount(1, $nodeV2->children, "child introduced at v2 is visible at v2");

			$nodeV4 = $repo->getNodeAtVersion($changed->id, $v4->id);
			self::assertEquals($v1->id, $nodeV4->sinceVersionId);
			self::assertEquals($v3->id, $nodeV4->changedVersionId);

			$removedNode = $repo->getNodeAtVersion($removed->id, $v4->id);
			self::assertEquals(DocSymbolStates::REMOVED, $removedNode->state);
			self::assertEquals($v3->id, $removedNode->removedVersionId);

			self::assertNull($repo->getNodeAtVersion($late->id, $v1->id));
			self::assertNull($repo->getNodeAtVersion($changed->id, $v1->id)?->children[0] ?? null, "child not yet introduced at v1");

			// siblings
			$siblingIdsV4 = array_map(fn ($n) => $n->symbol->id, $repo->getSiblingsAtVersion($changed->id, $v4->id));
			self::assertEqualsCanonicalizing([$changed->id, $removed->id, $late->id], $siblingIdsV4);

			$siblingIdsNoRemoved = array_map(fn ($n) => $n->symbol->id, $repo->getSiblingsAtVersion($changed->id, $v4->id, false));
			self::assertEqualsCanonicalizing([$changed->id, $late->id], $siblingIdsNoRemoved);

			// tree
			$tree = $repo->getTreeAtVersion($v4->id);
			$ours = array_values(array_filter($tree, fn ($entry) => $entry['module']->id === $mod->id));
			self::assertCount(1, $ours);
			self::assertCount(3, $ours[0]['symbols']);

			$changedNode = null;

			foreach ($ours[0]['symbols'] as $node) {
				if ($node->symbol->id === $changed->id) {
					$changedNode = $node;
				}
			}

			self::assertNotNull($changedNode);
			self::assertCount(1, $changedNode->children);

			$treeV1 = $repo->getTreeAtVersion($v1->id);
			$oursV1 = array_values(array_filter($treeV1, fn ($entry) => $entry['module']->id === $mod->id));
			self::assertCount(2, $oursV1[0]['symbols'], "late symbol absent at v1");

			// modules at version
			$modsAtV1 = array_map(fn ($m) => $m->id, (new DocModules(self::$db, self::$log))->getAtVersion($v1->id));
			self::assertContains($mod->id, $modsAtV1);

			// refs
			$ref = $repo->buildRef($child);
			self::assertEquals("{$mod->path}#{$changed->name}.{$child->name}", $ref);
			self::assertEquals($child->id, $repo->findByRef($ref)->id);
			self::assertEquals(0, $repo->findByRef("{$mod->path}#nope")->id);

			$mod->delete();
			$v4->delete();
			$v3->delete();
			$v2->delete();
			$v1->delete();

			return;
		}
	}
