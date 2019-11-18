<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 'On');

	require('../vendor/autoload.php');

	use Stoic\Web\PageHelper;
	use Stoic\Web\Stoic;

	$stoic = Stoic::getInstance('../');

	$req = $stoic->getRequest();
	$page = PageHelper::getPage('index.php', $req->getGet(), $req->getPost(), $req->getRequest());

	$page->redirectTo('~/1.0/');
