<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Core Component'
]); ?>

					<div id="doc-header" class="doc-header text-center">
						<h1 class="doc-title"><i class="icon fa fa-paper-plane"></i> Core Component</h1>
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
								<section id="core-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <em>Core</em> component contains the most basic pieces of Stoic:PHP.  Every other component builds on
											top of these pieces in some shape or form.  You of course aren't required to use them, but they are there
											for your convenience as much as they are for Stoic's.
										</p>
									</div>
								</section>

								<section id="core-components" class="doc-section">
									<h2 class="section-title">Included Features</h2>

									<div class="section-block">
										<p>
											The following features are included within the <em>Core</em> component:
										</p>

										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>Feature</th>
														<th>Description</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains'])?>">Chains</a></td>
														<td>Execution system for pub/sub, event, or chain of responsibility patterns</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging'])?>">Logging</a></td>
														<td>PSR-3 compliant logging system better suited for configured output options</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-returnhelper'])?>">ReturnHelper</a></td>
														<td>Utility that provides more detailed success/fail return values</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-enumbase'])?>">EnumBase</a></td>
														<td>Utility that provides some general functionality for creating enumerated class values</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains'])?>">chains</a>
											within the <em>Core</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#core-components">Components</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->