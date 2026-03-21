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
											The <em>Web</em> component utilizes the Core, I/O, and PDO components and provides a simple set of tools to
											enable the creation of websites and APIs.
										</p>
									</div>
								</section>

								<section id="web-components" class="doc-section">
									<h2 class="section-title">Included Features</h2>

									<div class="section-block">
										<p>
											The following features are included within the <em>Web</em> component:
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
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-stoic']))?>">Stoic</a></td>
														<td>The primary class that orchestrates page-level operations and manages core services</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-request']))?>">Request</a></td>
														<td>Represents a single HTTP request with typed access to superglobals</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-fileuploadhelper']))?>">FileUploadHelper</a></td>
														<td>Normalizes uploaded file information from the <code>$_FILES</code> superglobal</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-htmlelementhelper']))?>">HtmlElementHelper</a></td>
														<td>Aids in programmatic generation of HTML elements</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-pagehelper']))?>">PageHelper</a></td>
														<td>Holds page information such as title, meta tags, and asset path generation</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-paginatehelper']))?>">PaginateHelper</a></td>
														<td>Performs common pagination math for page offsets and totals</td>
													</tr>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-api']))?>">API Helpers</a></td>
														<td>Tools for building RESTful APIs including routing, responses, and authorization</td>
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
											Continue to read about <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-stoic']))?>">Stoic</a>
											within the <em>Web</em> component, or visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#web-components">Components</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
