<?php

	if (!defined('PAGE_ROOT_PATH')) {
		throw new \Exception("Page root path not defined, cannot render template");
	}

?>
<!DOCTYPE html>

<html lang="en">
	<head>
		<title><?=$page->getTitle()?></title>

		<!-- Meta -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php foreach ($page->getMetaTags() as $tag): ?>		<?=$tag->render()?>
<?php endforeach; ?>

		<!-- Base CSS -->
		<link rel="shortcut icon" href="favicon.ico">  
		<link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
		
		<!-- FontAwesome JS -->
		<script defer src="https://use.fontawesome.com/releases/v5.8.2/js/all.js" integrity="sha384-DJ25uNYET2XCl5ZF++U8eNxPWqcKohUUBUpKGlNLMchM7q4Wjg2CUpjHLaL8yYPH" crossorigin="anonymous"></script>
		
		<!-- Global CSS -->
		<link rel="stylesheet" href="<?=$page->getAssetPath('~/assets/plugins/bootstrap/css/bootstrap.min.css')?>">
		
		<!-- Plugins CSS -->    
		<link rel="stylesheet" href="<?=$page->getAssetPath('~/assets/plugins/prism/prism.css')?>">
		<link rel="stylesheet" href="<?=$page->getAssetPath('~/assets/plugins/elegant_font/css/style.css')?>">

		<!-- Theme CSS -->
		<link id="theme-style" rel="stylesheet" href="<?=$page->getAssetPath('~/assets/css/styles.css')?>">

		<!-- Page CSS -->
<?=$this->section('stylesheets')?>
	</head>

	<body class="body-green">
		<div class="page-wrapper">
			<!-- ******Header****** -->
			<header id="header" class="header">
				<div class="container">
					<div class="branding">
						<h1 class="logo">
							<a href="<?=$page->getAssetPath('~/index.php')?>">
								<span aria-hidden="true" class="icon_documents_alt icon"></span>
<?php if (isset($pageSection) && $pageSection !== null): ?>								<span class="text-highlight">Stoic:</span><span class="text-bold"><?=$pageSection?></span>
<?php else: ?>								<span class="text-highlight">Sto</span><span class="text-bold">ic</span>
<?php endif; ?>
							</a>
						</h1>
					</div><!--//branding-->

					<ol class="breadcrumb">
<?php if (isset($breadcrumbs) && count($breadcrumbs) > 0): ?><?php foreach ($breadcrumbs as $crumb): ?>						<li class="breadcrumb-item<?php if ($crumb['active']): ?> active<?php endif; ?>"><a href="<?=$page->getAssetPath(PAGE_ROOT_PATH, $crumb['queryVars'])?>"><?=$crumb['text']?></a></li>
<?php endforeach; ?><?php endif; ?>

					</ol>
				</div><!--//container-->
			</header><!--//header-->
			<div class="doc-wrapper">
				<div class="container">
<?=$this->section('content')?>
				</div><!--//container-->
			</div><!--//doc-wrapper-->
		</div><!--//page-wrapper-->
    
		<footer id="footer" class="footer text-center">
			<div class="container">
				<small class="copyright">Copyright &copy; <?php if (date('Y') != '2019') { echo('2019-' . date('Y')); } else { echo('2019'); } ?> Stoic Framework</small> |
				<!--/* This template is released under the Creative Commons Attribution 3.0 License. Please keep the attribution link below when using for your own project. Thank you for your support. :) If you'd like to use the template without the attribution, you can buy the commercial license via our website: themes.3rdwavemedia.com */-->
				<small class="copyright">Designed with <i class="fas fa-heart"></i> by <a href="https://themes.3rdwavemedia.com/" target="_blank">Xiaoying Riley</a> for developers</small>
			</div><!--//container-->
		</footer><!--//footer-->

		<!-- Main Javascript -->          
		<script type="text/javascript" src="<?=$page->getAssetPath('~/assets/plugins/jquery-3.3.1.min.js')?>"></script>
		<script type="text/javascript" src="<?=$page->getAssetPath('~/assets/plugins/bootstrap/js/bootstrap.bundle.min.js')?>"></script>
		<script type="text/javascript" src="<?=$page->getAssetPath('~/assets/plugins/prism/prism.js')?>"></script>    
		<script type="text/javascript" src="<?=$page->getAssetPath('~/assets/plugins/jquery-scrollTo/jquery.scrollTo.min.js')?>"></script>      
		<script type="text/javascript" src="<?=$page->getAssetPath('~/assets/plugins/stickyfill/dist/stickyfill.min.js')?>"></script>                                                             
		<script type="text/javascript" src="<?=$page->getAssetPath('~/assets/js/main.js')?>"></script>
	</body>
</html>