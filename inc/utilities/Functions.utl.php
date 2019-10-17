<?php

	namespace Stoic\Docs\Utilities;

	use League\Plates\Engine;

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

		return $ret;
	}
