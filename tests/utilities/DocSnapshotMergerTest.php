<?php

	use Zibings\DocSnapshotMerger;
	use Zibings\DocSymbolStatuses;

	/**
	 * Folding per-version snapshots into contract ranges.
	 */
	class DocSnapshotMergerTest extends ZsfTestCase {
		protected function symbol(string $signature, array $params = [], string $status = DocSymbolStatuses::STABLE, string $summary = '', array $children = []) : array {
			return ['kind' => 'fn', 'signature' => $signature, 'summary' => $summary, 'status' => $status, 'params' => $params, 'returns' => [], 'throws' => [], 'sourcePath' => 'a.php', 'sourceLine' => 1, 'children' => $children];
		}

		public function test_RangesFollowContractChanges() : void {
			$merger = new DocSnapshotMerger();
			$bundle = $merger->merge([
				['label' => 'v1.0', 'modules' => ['lib' => ['summary' => 'first', 'symbols' => [
					'stable'  => $this->symbol('function stable()', [], DocSymbolStatuses::STABLE, 'old words'),
					'changed' => $this->symbol('function changed(int $a)', [['name' => 'a', 'type' => 'int', 'required' => true, 'default' => null, 'description' => '']]),
					'removed' => $this->symbol('function removed()'),
					'parent'  => $this->symbol('class Parent', [], DocSymbolStatuses::STABLE, '', ['child' => $this->symbol('public function child()')])
				]]]],
				['label' => 'v1.1', 'modules' => ['lib' => ['summary' => '', 'symbols' => [
					'stable'  => $this->symbol('function stable()', [], DocSymbolStatuses::STABLE, 'new words'),
					'changed' => $this->symbol('function changed(int $a, int $b = 0)', [['name' => 'a', 'type' => 'int', 'required' => true, 'default' => null, 'description' => ''], ['name' => 'b', 'type' => 'int', 'required' => false, 'default' => '0', 'description' => '']]),
					'parent'  => $this->symbol('class Parent', [], DocSymbolStatuses::STABLE, '', ['child' => $this->symbol('public function child()'), 'added' => $this->symbol('public function added()')])
				]]]],
				['label' => 'v2.0', 'tag' => '2.0.0', 'modules' => ['lib' => ['summary' => '', 'symbols' => [
					'stable'  => $this->symbol('function stable()', [], DocSymbolStatuses::DEPRECATED, 'new words'),
					'changed' => $this->symbol('function changed(int $a, int $b = 0)', [['name' => 'a', 'type' => 'int', 'required' => true, 'default' => null, 'description' => ''], ['name' => 'b', 'type' => 'int', 'required' => false, 'default' => '0', 'description' => '']]),
					'parent'  => $this->symbol('class Parent', [], DocSymbolStatuses::STABLE, '', ['child' => $this->symbol('public function child()'), 'added' => $this->symbol('public function added()')]),
					'fresh'   => $this->symbol('function fresh()')
				]]]]
			], 'test');

			self::assertEquals(['v1.0', 'v1.1', 'v2.0'], array_column($bundle['versions'], 'label'));
			self::assertEquals(['1.0', '1.1', '2.0.0'], array_column($bundle['versions'], 'tag'));
			self::assertEquals([10, 20, 30], array_column($bundle['versions'], 'sortKey'));
			self::assertEquals([false, false, true], array_column($bundle['versions'], 'latest'));
			self::assertEquals('first', $bundle['modules'][0]['summary']);

			$symbols = [];

			foreach ($bundle['modules'][0]['symbols'] as $symbol) {
				$symbols[$symbol['name']] = $symbol;
			}

			// summary-only changes do not open a new contract; the newest summary wins; a status change does
			self::assertCount(2, $symbols['stable']['versions']);
			self::assertEquals(['v1.0', 'v2.0'], [$symbols['stable']['versions'][0]['introduced'], $symbols['stable']['versions'][1]['introduced']]);
			self::assertEquals('v2.0', $symbols['stable']['versions'][0]['removed']);
			self::assertEquals('new words', $symbols['stable']['versions'][0]['summary']);
			self::assertEquals(DocSymbolStatuses::DEPRECATED, $symbols['stable']['versions'][1]['status']);

			// a parameter change closes the old contract at the version where the new one starts
			self::assertCount(2, $symbols['changed']['versions']);
			self::assertEquals('v1.1', $symbols['changed']['versions'][0]['removed']);
			self::assertEquals('v1.1', $symbols['changed']['versions'][1]['introduced']);
			self::assertNull($symbols['changed']['versions'][1]['removed']);
			self::assertCount(2, $symbols['changed']['versions'][1]['params']);

			// disappearance closes the contract; late arrival starts one
			self::assertEquals('v1.1', $symbols['removed']['versions'][0]['removed']);
			self::assertEquals('v2.0', $symbols['fresh']['versions'][0]['introduced']);

			// children merge recursively
			$children = [];

			foreach ($symbols['parent']['children'] as $child) {
				$children[$child['name']] = $child;
			}

			self::assertEquals('v1.0', $children['child']['versions'][0]['introduced']);
			self::assertEquals('v1.1', $children['added']['versions'][0]['introduced']);
			self::assertArrayNotHasKey('children', $symbols['fresh']);

			return;
		}

		public function test_RejectsDuplicateLabels() : void {
			$this->expectException(\InvalidArgumentException::class);

			(new DocSnapshotMerger())->merge([['label' => 'v1', 'modules' => []], ['label' => 'v1', 'modules' => []]]);

			return;
		}
	}
