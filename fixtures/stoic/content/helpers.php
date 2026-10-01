<?php

	/**
	 * Small constructors for bundle records so the content files stay readable. Every page in this bundle is PHP-only,
	 * so samples default to the `php` language and no package-manager variant.
	 */

	/**
	 * A code sample record.
	 *
	 * @param string $key Embed key (`<<sample:key>>`).
	 * @param string $code Source text.
	 * @param null|string $title File name or `terminal`; null for an untitled card.
	 * @param null|string $variant Package-manager key for install lines.
	 * @return array
	 */
	function sample(string $key, string $code, ?string $title = null, ?string $variant = null) : array {
		return [
			'key'          => $key,
			'language'     => 'php',
			'variant'      => $variant,
			'code'         => rtrim($code) . "\n",
			'title'        => $title,
			'tested'       => false,
			'lastTestPass' => null
		];
	}

	/**
	 * A page record. Symbol refs are strings; a leading `!` marks the subject of a reference page.
	 *
	 * @param string $mode Page mode.
	 * @param string $slug Slug within the mode.
	 * @param string $title Title.
	 * @param string $summary One sentence.
	 * @param string $body Markdown body.
	 * @param string[] $symbols Symbol refs, `!ref` for the subject.
	 * @param array $samples Sample records.
	 * @param null|int $minutes Reading or doing time.
	 * @param string $introduced First version the page applies to.
	 * @return array
	 */
	function page(string $mode, string $slug, string $title, string $summary, string $body, array $symbols = [], array $samples = [], ?int $minutes = null, string $introduced = 'v1.3') : array {
		return [
			'mode'       => $mode,
			'slug'       => $slug,
			'title'      => $title,
			'summary'    => $summary,
			'body'       => trim($body) . "\n",
			'introduced' => $introduced,
			'removed'    => null,
			'minutes'    => $minutes,
			'symbols'    => array_map(fn (string $ref) => str_starts_with($ref, '!') ? ['ref' => substr($ref, 1), 'role' => 'subject'] : ['ref' => $ref, 'role' => 'mentions'], $symbols),
			'samples'    => $samples
		];
	}

	/**
	 * A reference page for one symbol. The slug follows the site convention `module/path/Parent.Child`.
	 *
	 * @param string $ref Subject symbol ref (`module/path#Parent.Child`).
	 * @param string $summary One sentence.
	 * @param string $body Markdown body.
	 * @param string[] $mentions Other symbol refs the body links.
	 * @param array $samples Sample records.
	 * @param string $introduced First version the page applies to.
	 * @return array
	 */
	function refPage(string $ref, string $summary, string $body, array $mentions = [], array $samples = [], string $introduced = 'v1.3') : array {
		[$module, $name] = explode('#', $ref, 2);

		return page('reference', "{$module}/{$name}", $name, $summary, $body, array_merge(["!{$ref}"], $mentions), $samples, null, $introduced);
	}
