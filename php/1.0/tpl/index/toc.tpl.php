<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => [], 'active' => false],
		['text' => 'Table of Contents', 'queryVars' => ['page' => 'toc'], 'active' => true]
	]
]); ?>

					<div id="doc-header" class="doc-header text-center">
						<h1 class="doc-title"><i class="icon fa fa-paper-plane"></i> Table of Contents</h1>
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
						<div class="doc-content col-md-12 col-12 order-1">
							<div class="content-inner">
								<section id="table-of-contents" class="doc-section">
									<h2 class="section-title">Contents</h2>
									
									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'quick-start'])?>">Quick Start</a></li>
											<li>
												<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'components'])?>">Components</a>
												<ul>
													<li>
														<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-core'])?>">Core</a>
														
														<ul>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains'])?>">Chains</a></li>
															<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging'])?>">Logging</a></li>
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