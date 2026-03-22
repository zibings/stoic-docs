<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Tutorials', 'queryVars' => ['page' => 'tutorials'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Tutorials'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Tutorials'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="tutorials-brief" class="doc-section">
									<div class="section-block">
										<p>
											Tutorials provide step-by-step walkthroughs for common tasks with Stoic:PHP.  Whether you're building
											your first website, setting up an API, or integrating with a database, these guides will help you get
											up and running.
										</p>

										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>Tutorial</th>
														<th>Description</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'tutorial-fizzbuzz-api']))?>">Building a FizzBuzz API</a></td>
														<td>A sample tutorial demonstrating various documentation layout elements</td>
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
											Visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>
											to explore the full documentation.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#tutorials-brief">Overview</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
