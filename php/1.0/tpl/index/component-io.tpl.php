<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => true]
	],
	'title' => 'Stoic:PHP - I/O Component'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'I/O Component'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="io-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <em>I/O</em> component contains a collection of utilities to simplify and/or streamline operations
											dealing with input and output in PHP.
										</p>
									</div>
								</section>

								<section id="io-components" class="doc-section">
									<h2 class="section-title">Included Features</h2>

									<div class="section-block">
										<p>
											The following features are included within the <em>I/O</em> component:
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
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></td>
														<td>Utility that generalizes some functionality around user interaction on the command line</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-filehelper'])?>">FileHelper</a></td>
														<td>Utility with helper methods for common filesystem operations</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logconsoleappender'])?>">LogConsoleAppender</a></td>
														<td>Logging appender that outputs messages to STDOUT</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logfileappender'])?>">LogFileAppender</a></td>
														<td>Logging appender that outputs messages to a file</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a></td>
														<td>Utility that provides operations common to handling input parameters, such as from GET or POST requests</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></td>
														<td>Utility that provides basic sanitation helpers and a framework for creating specialized ones</td>
													</tr>
													<tr>
														<td><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-stringhelper'])?>">StringHelper</a></td>
														<td>Utility that wraps useful string operations around PHP strings</td>
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
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a>
											within the <em>I/O</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
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