<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Components', 'queryVars' => ['page' => 'components'], 'active' => true]
	]
]); ?>

					<div id="doc-header" class="doc-header text-center">
						<h1 class="doc-title"><i class="icon fa fa-paper-plane"></i> Quick Start</h1>
						<div class="meta btn-group">
							<div class="pt-1 mr-4 my-auto" style="height: 32px">
								<i class="far fa-clock"></i> Last updated: <?=date('F jS, Y', filemtime(__FILE__))?>
							</div>

							<div class="pt-1 mr-1 my-auto" style="height: 32px">
								Release:
							</div>

							<div class="h-100">
								<div class="dropdown">
									<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="versionDropdownButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										v1.0
									</button>
								</div>
							</div>
						</div>
					</div><!--//doc-header-->
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="stoic-php-core" class="doc-section">
									<h2 class="section-title">Core</h2>

									<div class="section-block">
										<p>
											The core components for Stoic:PHP.  These provide functionality that is common and often required for
											the other components in the framework.
										</p>

										<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-core'])?>" class="btn btn-blue"><i class="fas fa-book-open"></i> Documentation</a>
										<a href="https://github.com/zibings/stoic-php-core" class="btn btn-green" target="_blank"><i class="fab fa-github"></i> Github Pages</a>
									</div>
								</section>

								<section id="stoic-php-io" class="doc-section">
									<h2 class="section-title">I/O</h2>

									<div class="section-block">
										<p>
											Components that focus on simplifying common input/output operations.
										</p>

										<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-io'])?>" class="btn btn-blue"><i class="fas fa-book-open"></i> Documentation</a>
										<a href="https://github.com/zibings/stoic-php-io" class="btn btn-green" target="_blank"><i class="fab fa-github"></i> Github Pages</a>
									</div>
								</section>

								<section id="stoic-php-pdo" class="doc-section">
									<h2 class="section-title">PDO</h2>

									<div class="section-block">
										<p>
											Components that provide wrappers for common PDO operations in PHP.
										</p>

										<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-pdo'])?>" class="btn btn-blue"><i class="fas fa-book-open"></i> Documentation</a>
										<a href="https://github.com/zibings/stoic-php-pdo" class="btn btn-green" target="_blank"><i class="fab fa-github"></i> Github Pages</a>
									</div>
								</section>

								<section id="stoic-php-web" class="doc-section">
									<h2 class="section-title">Web</h2>

									<div class="section-block">
										<p>
											Components that provide simple web functionality without dictating program structure/flow.
										</p>

										<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-web'])?>" class="btn btn-blue"><i class="fas fa-book-open"></i> Documentation</a>
										<a href="https://github.com/zibings/stoic-php-web" class="btn btn-green" target="_blank"><i class="fab fa-github"></i> Github Pages</a>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#stoic-php-core">Core</a>
									<a class="nav-link scrollto" href="#stoic-php-io">I/O</a>
									<a class="nav-link scrollto" href="#stoic-php-pdo">PDO</a>
									<a class="nav-link scrollto" href="#stoic-php-web">Web</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->