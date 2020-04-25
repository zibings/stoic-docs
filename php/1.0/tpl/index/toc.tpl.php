<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => [], 'active' => false],
		['text' => 'Table of Contents', 'queryVars' => ['page' => 'toc'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Table of Contents'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Table of Contents'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-12 col-12 order-1">
							<div class="content-inner">
								<section id="table-of-contents" class="doc-section">
									<h2 class="section-title">Contents</h2>
									
									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'quick-start'])?>">Quick Start</a></li>
											<li>
												<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>">Concepts</a>

												<ul>
													<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>#concept-classes">Classes</a></li>
													<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>#concept-repositories">Repositories</a></li>
													<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>#concept-utilities">Utilities</a></li>
													<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>#concept-entry-points">Entry-Points</a></li>
													<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>#concept-load-order">Load Order/Settings</a></li>
												</ul>
											</li>
											<li>
												<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'components'])?>">Components</a>
												<ul>
													<li>
														<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-core'])?>">Core</a>
														
														<ul>
															<li>
																<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains'])?>">Chains</a>

																<ul>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-dispatches'])?>">Dispatches</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-nodes'])?>">Nodes</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-chainhelper'])?>">ChainHelper</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a></li>
																</ul>
															</li>
															<li>
																<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging'])?>">Logging</a>

																<ul>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-logger'])?>">Logger</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-appenders'])?>">Appenders</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-messages'])?>">Messages</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a></li>
																</ul>
															</li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-returnhelper'])?>">ReturnHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-enumbase'])?>">EnumBase</a></li>
														</ul>
													</li>
													<li>
														<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-io'])?>">I/O</a>
														
														<ul>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-filehelper'])?>">FileHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logconsolehelper'])?>">LogConsoleHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logfileappender'])?>">LogFileAppender</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-stringhelper'])?>">StringHelper</a></li>
														</ul>
													</li>
													<li>
														<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-pdo'])?>">PDO</a>
														
														<ul>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-basedbclass'])?>">BaseDbClass</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-basedbmodel'])?>">BaseDbModel</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-basedbmodel'])?>">PdoHelper</a></li>
														</ul>
													</li>
													<li>
														<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-web'])?>">Web</a>
														
														<ul>
															<li>
																<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-api'])?>">API Helpers</a>

																<ul>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-api-basedbapi'])?>">BaseDbApi</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-api-response'])?>">Response</a></li>
																	<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-api-stoic'])?>">Stoic</a></li>
																</ul>
															</li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-fileuploadhelper'])?>">FileUploadHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-htmlelementhelper'])?>">HtmlElementHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-pagehelper'])?>">PageHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-paginatehelper'])?>">PaginateHelper</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-request'])?>">Request</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'web-stoic'])?>">Stoic</a></li>
														</ul>
													</li>
												</ul>
											</li>
										</ul>
									</div>
								</section><!--//doc-section-->
							</div><!--//content-inner-->
						</div><!--//doc-content-->
					</div><!--//doc-body-->