<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 'On');

	require('../vendor/autoload.php');

	use function Stoic\Docs\Utilities\getTemplateEngine;
	use Stoic\Web\PageHelper;
	use Stoic\Web\Stoic;

	$stoic = Stoic::getInstance('../');

	$req = $stoic->getRequest();
	$tpl = getTemplateEngine(['shared' => '../tpl/shared', 'page' => './tpl/index']);
	$page = PageHelper::getPage('php/index.php', $req->getGet(), $req->getPost(), $req->getRequest());

	$tplFile = 'landing';
	$tplVars = [
		'page' => $page,
		'pageSection' => \Stoic\Docs\Utilities\SectionStrings::PHP
	];

	echo($tpl->render("page::{$tplFile}", $tplVars));
