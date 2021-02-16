<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Web Component'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Web Component'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="web-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <em>web</em> component utilizes the Core, I/O, and PDO components and provides a simple set of tools to
											enable the creation of websites.
										</p>
									</div>
								</section>

								<section id="web-components" class="doc-section">
									<h2 class="section-title">Included Features</h2>

									<div class="section-block">
										<p>
											The following features are included within the <em>web</em> component:
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
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-stoic'])?>">Stoic</a></td>
														<td>The primary class that brings the pieces of the web component together</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-request'])?>">Request</a></td>
														<td>Utility with helper methods for common filesystem operations</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-pdohelper'])?>">PdoHelper</a></td>
														<td>Wrapper for PHP's PDO class that adds some metrics, logging, and other useful features</td>
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
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-consolehelper'])?>">BaseDbClass</a>
											within the <em>PDO</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
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