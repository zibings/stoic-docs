<?php

	use Zibings\DocPhpExtractor;
	use Zibings\DocSnapshotMerger;
	use Zibings\DocSymbolStatuses;

	/**
	 * PHP source trees become namespace modules with class/function symbols and public-member children.
	 */
	class DocPhpExtractorTest extends ZsfTestCase {
		protected function bundle() : array {
			$extractor = new DocPhpExtractor();
			$bundle    = (new DocSnapshotMerger())->merge([
				$extractor->snapshot(STOIC_CORE_PATH . 'tests/fixtures/php/v1/src', 'v1.0'),
				$extractor->snapshot(STOIC_CORE_PATH . 'tests/fixtures/php/v2/src', 'v2.0')
			], 'test');

			self::assertEquals([], $extractor->errors);

			return $bundle;
		}

		protected function byName(array $list) : array {
			$ret = [];

			foreach ($list as $entry) {
				$ret[$entry['name']] = $entry;
			}

			return $ret;
		}

		public function test_ClassesFunctionsAndMembers() : void {
			$bundle  = $this->bundle();
			$modules = [];

			foreach ($bundle['modules'] as $mod) {
				$modules[$mod['path']] = $this->byName($mod['symbols']);
			}

			self::assertEquals(['acme/widgets', 'acme/widgets/util'], array_keys($modules));

			$widget = $modules['acme/widgets']['Widget'];
			self::assertEquals(DocPhpExtractor::KIND_CLASS, $widget['kind']);
			self::assertCount(1, $widget['versions']);
			self::assertEquals('class Widget implements JsonSerializable', $widget['versions'][0]['signature']);
			self::assertEquals('A widget you can paint.', $widget['versions'][0]['summary']);
			self::assertEquals('Widget.php', $widget['versions'][0]['sourcePath']);
			self::assertEquals(12, $widget['versions'][0]['sourceLine']);

			$members = $this->byName($widget['children']);

			// non-public and @internal members are hidden
			self::assertArrayNotHasKey('hidden', $members);
			self::assertArrayNotHasKey('secret', $members);
			self::assertArrayNotHasKey('debugState', $members);

			// constants, properties, promoted constructor properties
			self::assertEquals(DocPhpExtractor::KIND_CONST, $members['DEFAULT_NAME']['kind']);
			self::assertEquals("const DEFAULT_NAME: string = 'widget'", $members['DEFAULT_NAME']['versions'][0]['signature']);
			self::assertEquals('public array $tags = []', $members['tags']['versions'][0]['signature']);
			self::assertEquals(DocPhpExtractor::KIND_PROPERTY, $members['name']['kind']);
			self::assertEquals('public readonly string $name', $members['name']['versions'][0]['signature']);
			self::assertEquals('Display name.', $members['name']['versions'][0]['summary']);
			self::assertArrayNotHasKey('color', $members);

			// defaults print with short class names; docblock descriptions attach to params
			$ctor = $members['__construct']['versions'][0];
			self::assertEquals('public function __construct(string $name, Color $color = Color::Red)', $ctor['signature']);
			self::assertEquals('Paint color.', $ctor['params'][1]['description']);
			self::assertFalse($ctor['params'][1]['required']);

			// a new optional parameter opens a second contract at v2.0
			$paint = $members['paint']['versions'];
			self::assertCount(2, $paint);
			self::assertEquals('public function paint(Color $color): static', $paint[0]['signature']);
			self::assertEquals('v2.0', $paint[0]['removed']);
			self::assertEquals('public function paint(Color $color, int $coats = 1): static', $paint[1]['signature']);
			self::assertEquals('How many coats.', $paint[1]['params'][1]['description']);
			self::assertEquals(['type' => 'static', 'description' => ''], $paint[1]['returns']);
			self::assertEquals([['type' => 'InvalidArgumentException', 'description' => 'when the color is unknown']], $paint[1]['throws']);

			// @deprecated sets status; removal closes the range; additions start one
			self::assertEquals(DocSymbolStatuses::DEPRECATED, $members['setColor']['versions'][0]['status']);
			self::assertEquals('v2.0', $members['setColor']['versions'][0]['removed']);
			self::assertEquals('v2.0', $members['fromArray']['versions'][0]['introduced']);
			self::assertEquals('public static function fromArray(array $data): self', $members['fromArray']['versions'][0]['signature']);
			self::assertEquals('Builds a widget from an array.', $members['fromArray']['versions'][0]['summary']);

			// functions: nullable and variadic params, docblock type fallbacks
			$fn = $modules['acme/widgets']['make_widget'];
			self::assertEquals(DocPhpExtractor::KIND_FUNCTION, $fn['kind']);
			self::assertEquals('function make_widget(?string $name = null, int ...$sizes): Widget', $fn['versions'][0]['signature']);
			self::assertEquals('?string', $fn['versions'][0]['params'][0]['type']);
			self::assertEquals('int[]', $fn['versions'][0]['params'][1]['type']);
			self::assertFalse($fn['versions'][0]['params'][1]['required']);

			// enums and cases
			$color = $modules['acme/widgets/util']['Color'];
			self::assertEquals(DocPhpExtractor::KIND_ENUM, $color['kind']);
			self::assertEquals('enum Color: string', $color['versions'][0]['signature']);
			self::assertEquals('Paint colors.', $color['versions'][0]['summary']);
			$cases = $this->byName($color['children']);
			self::assertEquals("case Red = 'red'", $cases['Red']['versions'][0]['signature']);
			self::assertEquals('v2.0', $cases['Green']['versions'][0]['introduced']);

			return;
		}
	}
