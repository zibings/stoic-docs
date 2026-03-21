<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'PDO Component', 'queryVars' => ['page' => 'component-pdo'], 'active' => true]
	],
	'title' => 'Stoic:PHP - PDO Component'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'PDO Component'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="pdo-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <em>PDO</em> component contains a collection of utilities to simplify and streamline database
											operations using PHP's PDO extension.
										</p>
									</div>
								</section>

								<section id="pdo-components" class="doc-section">
									<h2 class="section-title">Included Features</h2>

									<div class="section-block">
										<p>
											The following features are included within the <em>PDO</em> component:
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
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbclass']))?>">BaseDbClass</a></td>
														<td>Abstract base class providing PDO and Logger injection with utility methods for exception handling</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbmodel']))?>">BaseDbModel</a></td>
														<td>Simplistic ORM scaffolding for CRUD operations with automatic query generation</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-pdohelper']))?>">PdoHelper</a></td>
														<td>Wrapper for PHP's PDO class that adds query logging, error tracking, and stored queries</td>
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
											Continue to read about <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbclass']))?>">BaseDbClass</a>
											within the <em>PDO</em> component, or visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#pdo-components">Components</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
