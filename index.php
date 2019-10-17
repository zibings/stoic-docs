<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 'On');

	require('vendor/autoload.php');

	use function Stoic\Docs\Utilities\getTemplateEngine;
	use Stoic\Web\PageHelper;
	use Stoic\Web\Stoic;

	$stoic = Stoic::getInstance('./');

	$req = $stoic->getRequest();
	$tpl = getTemplateEngine(['shared' => './tpl/shared']);
	$page = PageHelper::getPage('index.php', $req->getGet(), $req->getPost(), $req->getRequest());

	echo($tpl->render('shared::master', ['page' => $page]));
