<?php

	namespace Zibings;

	/**
	 * Folds per-version symbol snapshots into one documentation bundle with contract version ranges.
	 *
	 * A snapshot describes a library at one version: modules, each with symbols (recursively, via `children`), each
	 * carrying the contract as it was at that version. The merger walks the versions in order and, per symbol, opens a
	 * new contract row when the signature, parameters, returns, throws, or status change, closes it when the symbol
	 * disappears, and keeps summary and source location at their latest values. Both extractors feed it.
	 *
	 * Snapshot shape:
	 *   ['label' => 'v1.0', 'tag' => '1.0.0', 'releasedAt' => null, 'modules' => [
	 *     'stoic/web' => ['summary' => '...', 'symbols' => [
	 *       'Stoic' => ['kind' => 'class', 'signature' => '...', 'summary' => '...', 'status' => 'stable',
	 *                   'params' => [], 'returns' => [], 'throws' => [], 'sourcePath' => null, 'sourceLine' => null,
	 *                   'children' => [ 'getInstance' => [...] ]]
	 *     ]]
	 *   ]]
	 *
	 * @package Zibings
	 */
	class DocSnapshotMerger {
		/**
		 * Merges ordered snapshots into a bundle array ready for DocBundleImporter.
		 *
		 * @param array $snapshots Snapshots, oldest first.
		 * @param string $tool Name recorded in the bundle's `generatedBy` field.
		 * @return array
		 */
		public function merge(array $snapshots, string $tool = 'DocSnapshotMerger') : array {
			if (count($snapshots) < 1) {
				throw new \InvalidArgumentException("At least one snapshot is required");
			}

			$versions = [];
			$labels   = [];

			foreach (array_values($snapshots) as $i => $snap) {
				$label = $snap['label'] ?? throw new \InvalidArgumentException("Snapshot {$i} has no label");

				if (in_array($label, $labels, true)) {
					throw new \InvalidArgumentException("Duplicate snapshot label '{$label}'");
				}

				$labels[]   = $label;
				$versions[] = [
					'tag'        => $snap['tag'] ?? ltrim($label, 'v'),
					'label'      => $label,
					'sortKey'    => $snap['sortKey'] ?? (($i + 1) * 10),
					'releasedAt' => $snap['releasedAt'] ?? null,
					'latest'     => $i === count($snapshots) - 1,
					'supported'  => $snap['supported'] ?? true
				];
			}

			$modulePaths = [];

			foreach ($snapshots as $snap) {
				foreach (array_keys($snap['modules'] ?? []) as $path) {
					if (!in_array($path, $modulePaths, true)) {
						$modulePaths[] = $path;
					}
				}
			}

			$modules = [];

			foreach ($modulePaths as $order => $path) {
				$summary = '';

				foreach ($snapshots as $snap) {
					if (isset($snap['modules'][$path]['summary']) && $snap['modules'][$path]['summary'] !== '') {
						$summary = $snap['modules'][$path]['summary'];
					}
				}

				$perVersion = array_map(fn (array $snap) => $snap['modules'][$path]['symbols'] ?? null, array_values($snapshots));

				$modules[] = [
					'path'      => $path,
					'summary'   => $summary,
					'sortOrder' => $order,
					'symbols'   => $this->mergeSymbols($perVersion, $labels)
				];
			}

			return [
				'generatedBy'    => $tool,
				'generatedAt'    => date('c'),
				'contextOptions' => [],
				'versions'       => $versions,
				'modules'        => $modules,
				'pages'          => [],
				'changes'        => [],
				'courses'        => []
			];
		}

		/**
		 * Merges one level of symbols across versions.
		 *
		 * @param array $perVersion Per-version maps of name => symbol snapshot (null where the module is absent).
		 * @param array $labels Version labels, parallel to $perVersion.
		 * @return array
		 */
		protected function mergeSymbols(array $perVersion, array $labels) : array {
			$names = [];

			foreach ($perVersion as $symbols) {
				foreach (array_keys($symbols ?? []) as $name) {
					if (!in_array($name, $names, true)) {
						$names[] = $name;
					}
				}
			}

			$ret = [];

			foreach ($names as $order => $name) {
				$appearances = array_map(fn (?array $symbols) => $symbols[$name] ?? null, $perVersion);
				$contracts   = [];
				$open        = null;
				$kind        = '';

				foreach ($appearances as $i => $snap) {
					if ($snap === null) {
						if ($open !== null) {
							$open['removed'] = $labels[$i];
							$contracts[]     = $open;
							$open            = null;
						}

						continue;
					}

					$kind = $snap['kind'] ?? $kind;

					if ($open !== null && !$this->sameContract($open, $snap)) {
						$open['removed'] = $labels[$i];
						$contracts[]     = $open;
						$open            = null;
					}

					if ($open === null) {
						$open = [
							'introduced' => $labels[$i],
							'removed'    => null,
							'signature'  => $snap['signature'] ?? '',
							'summary'    => $snap['summary'] ?? '',
							'status'     => $snap['status'] ?? DocSymbolStatuses::STABLE,
							'params'     => array_values($snap['params'] ?? []),
							'returns'    => $snap['returns'] ?? [],
							'throws'     => array_values($snap['throws'] ?? []),
							'sourcePath' => $snap['sourcePath'] ?? null,
							'sourceLine' => $snap['sourceLine'] ?? null
						];
					} else {
						// Same contract, newer prose and location win
						$open['summary']    = ($snap['summary'] ?? '') !== '' ? $snap['summary'] : $open['summary'];
						$open['sourcePath'] = $snap['sourcePath'] ?? $open['sourcePath'];
						$open['sourceLine'] = $snap['sourceLine'] ?? $open['sourceLine'];
					}
				}

				if ($open !== null) {
					$contracts[] = $open;
				}

				$children = $this->mergeSymbols(array_map(fn (?array $snap) => $snap === null ? null : ($snap['children'] ?? []), $appearances), $labels);
				$entry    = [
					'name'      => $name,
					'kind'      => $kind,
					'sortOrder' => $order,
					'versions'  => $contracts
				];

				if (count($children) > 0) {
					$entry['children'] = $children;
				}

				$ret[] = $entry;
			}

			return $ret;
		}

		/**
		 * Whether a snapshot's contract matches an open contract row (prose and location excluded).
		 *
		 * @param array $open Open contract row.
		 * @param array $snap Symbol snapshot.
		 * @return bool
		 */
		protected function sameContract(array $open, array $snap) : bool {
			return $open['signature'] === ($snap['signature'] ?? '')
				&& $open['status'] === ($snap['status'] ?? DocSymbolStatuses::STABLE)
				&& $this->normalize($open['params']) === $this->normalize(array_values($snap['params'] ?? []))
				&& $this->normalize($open['returns']) === $this->normalize($snap['returns'] ?? [])
				&& $this->normalize($open['throws']) === $this->normalize(array_values($snap['throws'] ?? []));
		}

		/**
		 * Canonical JSON for structural comparison.
		 *
		 * @param mixed $value Value to encode.
		 * @return string
		 */
		protected function normalize(mixed $value) : string {
			return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
		}
	}
