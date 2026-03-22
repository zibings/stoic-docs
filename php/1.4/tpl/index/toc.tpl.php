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
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'quick-start']))?>">Quick Start</a></li>
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'information']))?>">General Information</a></li>
											<li>
												<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'concepts']))?>">Concepts</a>

												<ul>
													<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'concepts']))?>#concept-classes">Classes</a></li>
													<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'concepts']))?>#concept-repositories">Repositories</a></li>
													<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'concepts']))?>#concept-utilities">Utilities</a></li>
													<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'concepts']))?>#concept-entry-points">Entry-Points</a></li>
													<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'concepts']))?>#concept-load-order">Load Order/Settings</a></li>
												</ul>
											</li>
											<li>
												<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'components']))?>">Components</a>
												<ul>
													<li>
														<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'component-core']))?>">Core</a>
														
														<ul>
															<li>
																<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-chains']))?>">Chains</a>

																<ul>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-chains-dispatches']))?>">Dispatches</a></li>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-chains-nodes']))?>">Nodes</a></li>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-chains-chainhelper']))?>">ChainHelper</a></li>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-chains-examples']))?>">Examples</a></li>
																</ul>
															</li>
															<li>
																<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-logging']))?>">Logging</a>

																<ul>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-logging-logger']))?>">Logger</a></li>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-logging-appenders']))?>">Appenders</a></li>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-logging-messages']))?>">Messages</a></li>
																	<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-logging-examples']))?>">Examples</a></li>
																</ul>
															</li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-returnhelper']))?>">ReturnHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-enumbase']))?>">EnumBase</a></li>
														</ul>
													</li>
													<li>
														<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'component-io']))?>">I/O</a>
														
														<ul>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-cliscripthelper']))?>">CliScriptHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-consolehelper']))?>">ConsoleHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-filehelper']))?>">FileHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-logconsoleappender']))?>">LogConsoleAppender</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-logfileappender']))?>">LogFileAppender</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-parameterhelper']))?>">ParameterHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-sanitationhelper']))?>">SanitationHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-stringhelper']))?>">StringHelper</a></li>
														</ul>
													</li>
													<li>
														<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'component-pdo']))?>">PDO</a>
														
														<ul>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbclass']))?>">BaseDbClass</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbmodel']))?>">BaseDbModel</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-pdohelper']))?>">PdoHelper</a></li>
														</ul>
													</li>
													<li>
														<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'component-web']))?>">Web</a>
														
														<ul>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-stoic']))?>">Stoic</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-request']))?>">Request</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-fileuploadhelper']))?>">FileUploadHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-htmlelementhelper']))?>">HtmlElementHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-pagehelper']))?>">PageHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-paginatehelper']))?>">PaginateHelper</a></li>
															<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-api']))?>">API Helpers</a></li>
														</ul>
													</li>
												</ul>
											</li>
											<li>
												<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'tutorials']))?>">Tutorials</a>

												<ul>
													<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'tutorial-fizzbuzz-api']))?>">Building a FizzBuzz API</a></li>
												</ul>
											</li>
										</ul>
									</div>
								</section><!--//doc-section-->
							</div><!--//content-inner-->
						</div><!--//doc-content-->
					</div><!--//doc-body-->