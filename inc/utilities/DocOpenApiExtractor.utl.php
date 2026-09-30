<?php

	namespace Zibings;

	use Symfony\Component\Yaml\Yaml;

	/**
	 * Turns an OpenAPI 3 document into a symbol snapshot for DocSnapshotMerger.
	 *
	 * Mapping: one module per tag (`{prefix}/{tag}`) holding an `endpoint` symbol per operation, plus a
	 * `{prefix}/schemas` module holding a `type` symbol per component schema with an `option` child per property.
	 * Endpoint parameters come from path, query, header, and cookie parameters and from the request body's properties;
	 * the 2xx response is the return type; other responses are the throws list.
	 *
	 * @package Zibings
	 */
	class DocOpenApiExtractor {
		const string KIND_ENDPOINT = 'endpoint';
		const string KIND_TYPE     = 'type';
		const string KIND_OPTION   = 'option';

		const array METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];


		/**
		 * Loads a JSON or YAML document from disk.
		 *
		 * @param string $path File path.
		 * @return array
		 */
		public static function load(string $path) : array {
			if (!is_file($path)) {
				throw new \InvalidArgumentException("OpenAPI document not found: {$path}");
			}

			$raw = file_get_contents($path) ?: '';

			if (preg_match('/\.json$/i', $path) || str_starts_with(ltrim($raw), '{')) {
				$doc = json_decode($raw, true);

				if (!is_array($doc)) {
					throw new \InvalidArgumentException("OpenAPI document is not valid JSON: {$path}");
				}

				return $doc;
			}

			$doc = Yaml::parse($raw);

			if (!is_array($doc)) {
				throw new \InvalidArgumentException("OpenAPI document is not valid YAML: {$path}");
			}

			return $doc;
		}

		/**
		 * Builds a snapshot from a parsed document.
		 *
		 * @param array $doc Parsed OpenAPI document.
		 * @param string $label Version label for the snapshot; defaults to `v{info.version}`.
		 * @param string $prefix Module path prefix.
		 * @return array
		 */
		public function snapshot(array $doc, ?string $label = null, string $prefix = 'api') : array {
			$infoVersion = (string) ($doc['info']['version'] ?? '');
			$label     ??= ($infoVersion !== '') ? 'v' . ltrim($infoVersion, 'v') : throw new \InvalidArgumentException("The document has no info.version; pass a label");
			$tagSummary  = [];

			foreach ($doc['tags'] ?? [] as $tag) {
				if (isset($tag['name'])) {
					$tagSummary[$tag['name']] = (string) ($tag['description'] ?? '');
				}
			}

			$modules = [];

			foreach ($doc['paths'] ?? [] as $path => $item) {
				if (!is_array($item)) {
					continue;
				}

				$shared = $item['parameters'] ?? [];

				foreach (self::METHODS as $method) {
					if (!isset($item[$method]) || !is_array($item[$method])) {
						continue;
					}

					$op   = $item[$method];
					$tag  = (string) ($op['tags'][0] ?? 'default');
					$mod  = $prefix . '/' . $this->slug($tag);
					$name = $this->operationName($op, $method, (string) $path);

					$modules[$mod] ??= ['summary' => $tagSummary[$tag] ?? '', 'symbols' => []];
					$modules[$mod]['symbols'][$name] = $this->endpoint($doc, $op, $method, (string) $path, array_merge($shared, $op['parameters'] ?? []));
				}
			}

			$schemas = $doc['components']['schemas'] ?? [];

			if (count($schemas) > 0) {
				$mod           = $prefix . '/schemas';
				$modules[$mod] = ['summary' => 'Payload shapes shared by the endpoints', 'symbols' => []];

				foreach ($schemas as $schemaName => $schema) {
					$modules[$mod]['symbols'][(string) $schemaName] = $this->schemaSymbol((string) $schemaName, is_array($schema) ? $schema : []);
				}
			}

			return [
				'label'      => $label,
				'tag'        => ($infoVersion !== '') ? $infoVersion : ltrim($label, 'v'),
				'releasedAt' => null,
				'modules'    => $modules
			];
		}

		/**
		 * Builds the endpoint symbol for one operation.
		 *
		 * @param array $doc Whole document (for $ref resolution).
		 * @param array $op Operation object.
		 * @param string $method HTTP method.
		 * @param string $path Path template.
		 * @param array $parameters Path-level and operation-level parameters.
		 * @return array
		 */
		protected function endpoint(array $doc, array $op, string $method, string $path, array $parameters) : array {
			$params = [];

			foreach ($parameters as $param) {
				$param = $this->resolve($doc, $param);

				if (!isset($param['name'])) {
					continue;
				}

				$params[] = [
					'name'        => (string) $param['name'],
					'type'        => $this->typeOf($doc, $param['schema'] ?? []),
					'required'    => (bool) ($param['required'] ?? ($param['in'] ?? '') === 'path'),
					'default'     => $this->defaultOf($param['schema'] ?? []),
					'description' => trim(($param['in'] ?? 'query') . ' · ' . ($param['description'] ?? ''), ' ·')
				];
			}

			$body = $this->resolve($doc, $op['requestBody'] ?? []);

			foreach ($body['content'] ?? [] as $mediaType => $content) {
				$schema = $this->resolve($doc, $content['schema'] ?? []);

				if (isset($schema['properties']) && is_array($schema['properties'])) {
					$required = $schema['required'] ?? [];

					foreach ($schema['properties'] as $propName => $prop) {
						$params[] = [
							'name'        => (string) $propName,
							'type'        => $this->typeOf($doc, $prop),
							'required'    => in_array($propName, $required, true) || (bool) ($body['required'] ?? false) && in_array($propName, $required, true),
							'default'     => $this->defaultOf($prop),
							'description' => trim('body · ' . ($prop['description'] ?? ''), ' ·')
						];
					}
				} else if (count($schema) > 0) {
					$params[] = [
						'name'        => 'body',
						'type'        => $this->typeOf($doc, $schema),
						'required'    => (bool) ($body['required'] ?? false),
						'default'     => null,
						'description' => trim("body · {$mediaType} · " . ($body['description'] ?? ''), ' ·')
					];
				}

				break;
			}

			$returns = [];
			$throws  = [];

			foreach ($op['responses'] ?? [] as $code => $response) {
				$response = $this->resolve($doc, $response);
				$schema   = null;

				foreach ($response['content'] ?? [] as $content) {
					$schema = $content['schema'] ?? null;

					break;
				}

				$type = ($schema !== null) ? $this->typeOf($doc, $schema) : 'void';
				$desc = (string) ($response['description'] ?? '');

				if (preg_match('/^2\d\d$/', (string) $code) && count($returns) < 1) {
					$returns = ['type' => $type, 'description' => trim("{$code} · {$desc}", ' ·')];
				} else if (!preg_match('/^2\d\d$/', (string) $code)) {
					$throws[] = ['type' => "HTTP {$code}", 'description' => trim($desc . (($type !== 'void' && $type !== 'string') ? " ({$type})" : ''))];
				}
			}

			$status = !empty($op['deprecated']) ? DocSymbolStatuses::DEPRECATED : DocSymbolStatuses::STABLE;

			return [
				'kind'       => self::KIND_ENDPOINT,
				'signature'  => strtoupper($method) . ' ' . $path,
				'summary'    => (string) ($op['summary'] ?? $op['description'] ?? ''),
				'status'     => $status,
				'params'     => $params,
				'returns'    => $returns,
				'throws'     => $throws,
				'sourcePath' => null,
				'sourceLine' => null,
				'children'   => []
			];
		}

		/**
		 * Builds the type symbol (with property children) for a component schema.
		 *
		 * @param string $name Schema name.
		 * @param array $schema Schema object.
		 * @return array
		 */
		protected function schemaSymbol(string $name, array $schema) : array {
			$children = [];
			$required = $schema['required'] ?? [];

			foreach ($schema['properties'] ?? [] as $propName => $prop) {
				$type = $this->typeOf([], is_array($prop) ? $prop : []);

				$children[(string) $propName] = [
					'kind'       => self::KIND_OPTION,
					'signature'  => "{$propName}" . (in_array($propName, $required, true) ? '' : '?') . ": {$type}",
					'summary'    => (string) ($prop['description'] ?? ''),
					'status'     => !empty($prop['deprecated']) ? DocSymbolStatuses::DEPRECATED : DocSymbolStatuses::STABLE,
					'params'     => [],
					'returns'    => ['type' => $type, 'description' => ''],
					'throws'     => [],
					'sourcePath' => null,
					'sourceLine' => null,
					'children'   => []
				];
			}

			$kindWord = ($schema['type'] ?? 'object');

			return [
				'kind'       => self::KIND_TYPE,
				'signature'  => "{$kindWord} {$name}" . (isset($schema['enum']) ? ' = ' . $this->typeOf([], $schema) : ''),
				'summary'    => (string) ($schema['description'] ?? ''),
				'status'     => !empty($schema['deprecated']) ? DocSymbolStatuses::DEPRECATED : DocSymbolStatuses::STABLE,
				'params'     => [],
				'returns'    => [],
				'throws'     => [],
				'sourcePath' => null,
				'sourceLine' => null,
				'children'   => $children
			];
		}

		/**
		 * Renders a schema as a short type string.
		 *
		 * @param array $doc Document for reference lookups.
		 * @param mixed $schema Schema object.
		 * @return string
		 */
		protected function typeOf(array $doc, mixed $schema) : string {
			if (!is_array($schema) || count($schema) < 1) {
				return 'any';
			}

			if (isset($schema['$ref'])) {
				return (string) preg_replace('#^.*/#', '', (string) $schema['$ref']);
			}

			foreach (['oneOf', 'anyOf'] as $combinator) {
				if (isset($schema[$combinator]) && is_array($schema[$combinator])) {
					$parts = array_map(fn ($s) => $this->typeOf($doc, $s), $schema[$combinator]);

					return implode(' | ', array_unique($parts)) . (!empty($schema['nullable']) ? ' | null' : '');
				}
			}

			if (isset($schema['allOf']) && is_array($schema['allOf'])) {
				return implode(' & ', array_unique(array_map(fn ($s) => $this->typeOf($doc, $s), $schema['allOf'])));
			}

			if (isset($schema['enum']) && is_array($schema['enum'])) {
				return implode(' | ', array_map(fn ($v) => is_string($v) ? "\"{$v}\"" : json_encode($v), $schema['enum']));
			}

			$type = $schema['type'] ?? null;

			if (is_array($type)) {
				return implode(' | ', $type);
			}

			$ret = match ($type) {
				'array'   => $this->typeOf($doc, $schema['items'] ?? []) . '[]',
				'integer' => 'integer',
				'number'  => 'number',
				'string'  => isset($schema['format']) ? "string ({$schema['format']})" : 'string',
				'boolean' => 'boolean',
				'object'  => isset($schema['properties']) ? '{ ' . implode(', ', array_keys($schema['properties'])) . ' }' : 'object',
				null      => isset($schema['properties']) ? '{ ' . implode(', ', array_keys($schema['properties'])) . ' }' : 'any',
				default   => (string) $type
			};

			return $ret . (!empty($schema['nullable']) ? ' | null' : '');
		}

		/**
		 * Default value of a schema as a display string, or null.
		 *
		 * @param mixed $schema Schema object.
		 * @return null|string
		 */
		protected function defaultOf(mixed $schema) : ?string {
			if (!is_array($schema) || !array_key_exists('default', $schema)) {
				return null;
			}

			$default = $schema['default'];

			return is_string($default) ? "\"{$default}\"" : json_encode($default);
		}

		/**
		 * Follows a local `$ref` once.
		 *
		 * @param array $doc Document.
		 * @param mixed $node Node that may be a reference.
		 * @return array
		 */
		protected function resolve(array $doc, mixed $node) : array {
			if (!is_array($node)) {
				return [];
			}

			if (!isset($node['$ref']) || !str_starts_with((string) $node['$ref'], '#/')) {
				return $node;
			}

			$current = $doc;

			foreach (explode('/', substr((string) $node['$ref'], 2)) as $segment) {
				$segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

				if (!is_array($current) || !array_key_exists($segment, $current)) {
					return [];
				}

				$current = $current[$segment];
			}

			return is_array($current) ? $current : [];
		}

		/**
		 * Symbol name for an operation: operationId, or verb + path.
		 *
		 * @param array $op Operation object.
		 * @param string $method HTTP method.
		 * @param string $path Path template.
		 * @return string
		 */
		protected function operationName(array $op, string $method, string $path) : string {
			if (!empty($op['operationId'])) {
				return (string) $op['operationId'];
			}

			$slug = preg_replace('/[^A-Za-z0-9]+/', '_', trim($path, '/')) ?: 'root';

			return strtolower($method) . '_' . trim($slug, '_');
		}

		/**
		 * URL-safe lowercase slug.
		 *
		 * @param string $text Text.
		 * @return string
		 */
		protected function slug(string $text) : string {
			return trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text) ?: 'default'), '-') ?: 'default';
		}
	}
