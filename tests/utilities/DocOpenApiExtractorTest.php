<?php

	use Zibings\DocOpenApiExtractor;
	use Zibings\DocSnapshotMerger;
	use Zibings\DocSymbolStatuses;

	/**
	 * OpenAPI documents become endpoint and schema symbols with contract ranges across releases.
	 */
	class DocOpenApiExtractorTest extends ZsfTestCase {
		protected function bundle() : array {
			$extractor = new DocOpenApiExtractor();

			return (new DocSnapshotMerger())->merge([
				$extractor->snapshot(DocOpenApiExtractor::load(STOIC_CORE_PATH . 'tests/fixtures/openapi/v1.yaml')),
				$extractor->snapshot(DocOpenApiExtractor::load(STOIC_CORE_PATH . 'tests/fixtures/openapi/v2.yaml'))
			], 'test');
		}

		protected function symbols(array $bundle, string $module) : array {
			foreach ($bundle['modules'] as $mod) {
				if ($mod['path'] === $module) {
					$ret = [];

					foreach ($mod['symbols'] as $symbol) {
						$ret[$symbol['name']] = $symbol;
					}

					return $ret;
				}
			}

			self::fail("module {$module} missing");
		}

		public function test_EndpointsAndSchemas() : void {
			$bundle  = $this->bundle();
			$widgets = $this->symbols($bundle, 'api/widgets');
			$schemas = $this->symbols($bundle, 'api/schemas');
			$default = $this->symbols($bundle, 'api/default');

			// labels come from info.version, module summary from the tag description
			self::assertEquals(['v1.0.0', 'v2.0.0'], array_column($bundle['versions'], 'label'));
			self::assertEquals(['1.0.0', '2.0.0'], array_column($bundle['versions'], 'tag'));
			self::assertEquals('Create and inspect widgets', $bundle['modules'][0]['summary']);

			// query param with default, array-of-ref return
			$list = $widgets['listWidgets']['versions'][0];
			self::assertEquals('endpoint', $widgets['listWidgets']['kind']);
			self::assertEquals('GET /Widgets', $list['signature']);
			self::assertEquals(['name' => 'limit', 'type' => 'integer', 'required' => false, 'default' => '20', 'description' => 'query · Page size'], $list['params'][0]);
			self::assertEquals('Widget[]', $list['returns']['type']);

			// body properties become params; a new required body field opens a second contract
			self::assertCount(2, $widgets['createWidget']['versions']);
			$create1 = $widgets['createWidget']['versions'][0];
			$create2 = $widgets['createWidget']['versions'][1];
			self::assertEquals('v2.0.0', $create1['removed']);
			self::assertEquals(['name', 'color'], array_column($create1['params'], 'name'));
			self::assertEquals(['name', 'color', 'size'], array_column($create2['params'], 'name'));
			self::assertTrue($create2['params'][2]['required']);
			self::assertEquals('"red" | "blue"', $create1['params'][1]['type']);
			self::assertEquals('"red"', $create1['params'][1]['default']);
			self::assertEquals([['type' => 'HTTP 400', 'description' => 'Invalid payload']], $create1['throws']);

			// path param is required; deprecation is a status change and therefore a new contract
			$get = $widgets['getWidget']['versions'];
			self::assertCount(2, $get);
			self::assertTrue($get[0]['params'][0]['required']);
			self::assertEquals('path', explode(' ', $get[0]['params'][0]['description'])[0]);
			self::assertEquals(DocSymbolStatuses::STABLE, $get[0]['status']);
			self::assertEquals(DocSymbolStatuses::DEPRECATED, $get[1]['status']);

			// operation without operationId or tags, removed in v2
			self::assertEquals('GET /Legacy/Ping', $default['get_Legacy_Ping']['versions'][0]['signature']);
			self::assertEquals('v2.0.0', $default['get_Legacy_Ping']['versions'][0]['removed']);

			// schema with property children; a property added in v2 gets its own range
			self::assertEquals('type', $schemas['Widget']['kind']);
			self::assertEquals('A widget record', $schemas['Widget']['versions'][0]['summary']);
			$props = [];

			foreach ($schemas['Widget']['children'] as $child) {
				$props[$child['name']] = $child;
			}

			self::assertEquals('option', $props['id']['kind']);
			self::assertEquals('id: integer', $props['id']['versions'][0]['signature']);
			self::assertEquals('color?: string | null', $props['color']['versions'][0]['signature']);
			self::assertEquals('v2.0.0', $props['createdAt']['versions'][0]['introduced']);
			self::assertEquals('string (date-time)', $props['createdAt']['versions'][0]['returns']['type']);

			return;
		}

		public function test_LoadsJsonToo() : void {
			$path = sys_get_temp_dir() . '/doc-openapi-' . uniqid() . '.json';
			file_put_contents($path, json_encode(['openapi' => '3.0.0', 'info' => ['title' => 'x', 'version' => '3.1'], 'paths' => ['/Ping' => ['get' => ['responses' => ['200' => ['description' => 'pong']]]]]]));

			$snapshot = (new DocOpenApiExtractor())->snapshot(DocOpenApiExtractor::load($path), null, 'svc');
			unlink($path);

			self::assertEquals('v3.1', $snapshot['label']);
			self::assertArrayHasKey('svc/default', $snapshot['modules']);
			self::assertArrayHasKey('get_Ping', $snapshot['modules']['svc/default']['symbols']);

			return;
		}
	}
