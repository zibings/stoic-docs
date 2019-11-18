<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 'On');

	define('CORE_PATH', '../../');
	define('PAGE_ROOT_PATH', '~/php/1.0/');
	require(CORE_PATH . 'vendor/autoload.php');

	use function Stoic\Docs\Utilities\getTemplateEngine;
	use Stoic\Web\PageHelper;
	use Stoic\Web\Stoic;

	$stoic = Stoic::getInstance(CORE_PATH);

	$validPages = [];
	$tplFile = 'error';
	$req = $stoic->getRequest();
	$currPage = $req->getGet()->getString('page', 'landing');
	$tpl = getTemplateEngine(['shared' => CORE_PATH . 'tpl/shared', 'page' => './tpl/index']);
	$page = PageHelper::getPage('php/1.0/index.php', $req->getGet(), $req->getPost(), $req->getRequest());

	foreach ($stoic->getFileHelper()->getFolderFiles('~/php/1.0/tpl/index/') as $file) {
		if (substr($file, -8) == '.tpl.php') {
			$validPages[str_replace(['.tpl.php', CORE_PATH . 'php/1.0/tpl/index/'], '', $file)] = true;
		}
	}

	if (array_key_exists($currPage, $validPages) !== false) {
		$tplFile = $currPage;
	}

	$tplVars = [
		'page' => $page,
		'pageSection' => \Stoic\Docs\Utilities\SectionStrings::PHP
	];

	echo($tpl->render("page::{$tplFile}", $tplVars));
