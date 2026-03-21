<?php

	namespace Stoic\Docs\Utilities;

	use League\Plates\Engine;

	/**
	 * Attempts to find the index of the last instance of the given $needle character.  If $needle contains more than one
	 * character, the last character of the string will be used.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return int|bool
	 */
	function getLastPosition(string $haystack, string $needle) : int|bool {
		$needle = strlen($needle) > 1 ? $needle[-1] : $needle;

		if (!str_contains($haystack, $needle)) {
			return false;
		}

		for ($i = strlen($haystack) - 1; $i >= 0; $i--) {
			if (substr($haystack, $i, 1) === $needle) {
				return $i;
			}
		}

		return false;
	}

	/**
	 * Returns a template Engine object with the given settings.
	 *
	 * @param null|array $folders Optional set of folders to add to Engine instance, use format ['key' => 'path'].
	 * @param null|array $data Optional data to add to Engine instance.
	 * @param null|string $extension Optional template file extension for Engine instance, defaults to 'tpl.php'.
	 * @return Engine
	 */
	function getTemplateEngine(?array $folders = null, array $data = null, string $extension = null) : Engine {
		$ret = new Engine(null, $extension ?? 'tpl.php');

		if ($folders !== null) {
			foreach ($folders as $name => $path) {
				$ret->addFolder($name, $path);
			}
		}

		if ($data !== null) {
			$ret->addData($data);
		}

		$ret->registerFunction('localUrl', function ($string) { return localUrl($string); });

		return $ret;
	}

	/**
	 * Adds 'index.php' after the last found '/' for a URL if the server is running on port 8088.
	 *
	 * @param string $url
	 * @return string
	 */
	function localUrl(string $url) : string {
		if ($_SERVER['SERVER_PORT'] != 8088) {
			return $url;
		}

		$lastSlash = getLastPosition($url, '/');

		if ($lastSlash === false) {
			return $url;
		}

		$lastSlash = intval($lastSlash);

		return substr($url, 0, $lastSlash) . '/index.php' . substr($url, $lastSlash + 1);
	}
