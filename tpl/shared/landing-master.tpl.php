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
		
		<link rel="shortcut icon" href="favicon.ico">
		<link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
		
		<!-- FontAwesome JS -->
		<script defer src="https://use.fontawesome.com/releases/v5.8.2/js/all.js" integrity="sha384-DJ25uNYET2XCl5ZF++U8eNxPWqcKohUUBUpKGlNLMchM7q4Wjg2CUpjHLaL8yYPH" crossorigin="anonymous"></script>
		
		<!-- Global CSS -->
		<link rel="stylesheet" href="<?=$page->getAssetPath('~/assets/plugins/bootstrap/css/bootstrap.min.css')?>">
		
		<!-- Plugins CSS -->
		<link rel="stylesheet" href="<?=$page->getAssetPath('~/assets/plugins/elegant_font/css/style.css')?>">
		
		<!-- Theme CSS -->
		<link id="theme-style" rel="stylesheet" href="<?=$page->getAssetPath('~/assets/css/styles.css')?>">
	</head>

	<body class="landing-page">
		<div class="page-wrapper">
			<!-- ******Header****** -->
			<header class="header text-center">
				<div class="container">
					<div class="branding">
						<h1 class="logo">
							<span aria-hidden="true" class="icon_documents_alt icon"></span>
<?php if (isset($pageSection) && $pageSection !== null): ?>							<span class="text-highlight">Stoic:</span><span class="text-bold"><?=$pageSection?></span>
<?php else: ?>							<span class="text-highlight">Stoic</span><span class="text-bold">Docs</span>
<?php endif; ?>
						</h1>
					</div><!--//branding-->
					
<?=$this->section('tagline')?>
				</div><!--//container-->
			</header><!--//header-->
			
			<section class="cards-section text-center">
				<div class="container">
<?=$this->section('content')?>
				</div><!--//container-->
			</section><!--//cards-section-->
		</div><!--//page-wrapper-->
		
		<footer class="footer text-center">
			<div class="container">
				<small class="copyright">Copyright &copy; <?php if (date('Y') != '2019') { echo('2019-' . date('Y')); } else { echo('2019'); } ?> Stoic Framework</small> |
				<!--/* This template is released under the Creative Commons Attribution 3.0 License. Please keep the attribution link below when using for your own project. Thank you for your support. :) If you'd like to use the template without the attribution, you can buy the commercial license via our website: themes.3rdwavemedia.com */-->
				<small class="copyright">Designed with <i class="fas fa-heart"></i> by <a href="https://themes.3rdwavemedia.com/" target="_blank">Xiaoying Riley</a> for developers</small>
			</div><!--//container-->
		</footer><!--//footer-->
		
		<!-- Main Javascript -->
		<script type="text/javascript" src="assets/plugins/jquery-3.3.1.min.js"></script>
		<script type="text/javascript" src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="assets/plugins/stickyfill/dist/stickyfill.min.js"></script>
		<script type="text/javascript" src="assets/js/main.js"></script>
	</body>
</html>