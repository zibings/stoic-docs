<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Components', 'queryVars' => ['page' => 'components'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Concepts'
]); ?>

					<div id="doc-header" class="doc-header text-center">
						<h1 class="doc-title"><i class="icon fa fa-paper-plane"></i> Concepts</h1>
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
								<section id="stoic-php-concepts" class="doc-section">
									<div class="section-block">
										<p>
											Stoic:PHP aims to be as simple as possible, but there are still some concepts we use in our codebase that may seem
											foreign to those new to the framework.  Here we'll go through our 'unique' concepts in a little detail so that the
											rest of our documentation won't contain any surprises.  It's important to note, however, that these concepts are
											simply suggestions for working with Stoic:PHP and by no means are they required in order for you to take advantage
											of the system.
										</p>
									</div>
								</section>

								<section id="concept-classes" class="doc-section">
									<h2 class="section-title">Classes</h2>

									<div class="section-block">
										<p>
											Classes, which by default live within the <code>~/inc/classes/</code> directory and have the <code>.cls.php</code>
											file extension, tend to be code classes which describe single entities within a codebase.  As an example, you might
											have the following classes in your classes directory:
										</p>

										<div class="code-block">
											<code>
												Notification.cls.php  - A class representing a single notification in the system<br />
												Record.cls.php  - A class representing a single record in the system
											</code>
										</div>

										<p>
											Though both of these classes are different, they are similar in the sense that each of them represents a single piece
											of information.
										</p>

										<p>
											All files inside the <code>~/inc/classes/</code> directory with the <code>.cls.php</code> extension are automatically
											included with every load of the Stoic:PHP core system.
										</p>

										<p>
											The directory and extension values can be changed by altering the <code>classesPath</code> and <code>classesExt</code>
											settings in your <code>siteSettings.json</code> file.
										</p>
									</div>
								</section>

								<section id="concept-repositories" class="doc-section">
									<h2 class="section-title">Repositories</h2>

									<div class="section-block">
										<p>
											Repositories, which by default live within the <code>~/inc/repositories/</code> directory and have the
											<code>.rpo.php</code> file extension, tend to be code classes that provide operations on one or more code classes.
											For example, you might have the following classes in your repositories directory:
										</p>

										<div class="code-block">
											<code>
												Notifications.rpo.php - Provides mass operations for Notification classes/objects<br />
												Records.rpo.php - Provides mass operations for Record classes/objects
											</code>
										</div>

										<p>
											In this case, both of these repository classes would provide methods such as <code>Notifications::getAll()</code> or
											<code>Records::getAllPublished()</code>.  Both examples are focused on operations that involve multiple instances of
											their associated classes.
										</p>

										<p>
											All files inside the <code>~/inc/repositories/</code> directory with the <code>.rpo.php</code> extension are
											automatically included with every load of the Stoic:PHP core system.
										</p>

										<p>
											The directory and extension values can be changed by altering the <code>reposPath</code> and <code>reposExt</code>
											settings in your <code>siteSettings.json</code> file.
										</p>
									</div>
								</section>

								<section id="concept-utilities" class="doc-section">
									<h2 class="section-title">Utilities</h2>

									<div class="section-block">
										<p>
											Utilities, which by default live within the <code>~/inc/utilities/</code> directory and have the <code>.utl.php</code>
											file extension, are code files that serve more generic purposes that don't seem to fall within the categories of
											either classes or repositories.  For example, you might have the following classes in your utilities directory:
										</p>

										<div class="code-block">
											<code>
												JiraTicket.utl.php - Provides class to submit/read tickets to/from a JIRA instance<br />
												BuildMenu.utl.php - Provides code that runs every time to build a menu structure
											</code>
										</div>

										<p>
											Each of the above files provides very different functionality, but functionality that is important to always have
											available when processing requests in a Stoic:PHP instance.  Sometimes these are classes, sometimes they are simply
											code that needs to be executed in the global scope, but either way they are important enough to always include.
										</p>

										<p>
											All files inside the <code>~/inc/utilities/</code> directory with the <code>.utl.php</code> extension are
											automatically included with every load of the Stoic:PHP core system.
										</p>

										<p>
											The directory and extension values can be changed by altering the <code>utilitiesPath</code> and
											<code>utilitiesExt</code> settings in your <code>siteSettings.json</code> file.
										</p>
									</div>
								</section>

								<section id="concept-entry-points" class="doc-section">
									<h2 class="section-title">Entry-Points</h2>

									<div class="section-block">
										<p>
											Entry-points are simply the way we refer to files that load the Stoic:PHP core system and intend to serve a
											request.  They are at your control, so they can exist anywhere you like, do whatever you like, and follow
											whatever structure you prefer.  For example, your project structure may look like:
										</p>

										<div class="code-block">
											<code>
												~/inc/classes/<br />
												~/inc/repositories/<br />
												~/inc/utilities<br />
												~/index.php<br />
												~/login.php<br />
												~/register.php
											</code>
										</div>

										<p>
											The <code>~/index.php</code>, <code>~/login.php</code>, and <code>~/register.php</code> files are all
											considered "entry-points" in Stoic:PHP, assuming they all have - at minimum - the following code:
										</p>

										<div class="code-block">
											<pre class="language-php">
<code>&lt;?php

	require("vendor/autoload.php");

	use Stoic\Web\Stoic;
	$stoic = Stoic::getInstance("./");
</code>
</pre>
										</div>
									</div>
								</section>

								<section id="concept-load-order" class="doc-section">
									<h2 class="section-title">Load Order &amp; Settings</h2>

									<div class="section-block">
										<p>
											Concept files are loaded in the following order by Stoic:PHP:
										</p>

										<div class="code-block">
											<code>
												Classes<br />
												Repositories<br />
												Utilities
											</code>
										</div>

										<p>
											The following settings are available in your <code>siteSettings.json</code> to customize how we handle concept
											files:
										</p>

										<div class="code-block">
											<code>
												"classesExt" - File extension for class concept files<br />
												"classesPath" - Directory path for class concept files<br />
												"includePath" - Directory used as root directory for concept file paths<br />
												"reposExt" - File extension for repository concept files<br />
												"reposPath" - Directory path for repository concept files<br />
												"utilitiesExt" - File extension for utility concept files<br />
												"utilitiesPath" - Directory path for utility concept files
											</code>
										</div>
									</div>
								</section>

								<section id="concept-whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<p>
										Continue to read about our <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'components'])?>">components</a> or
										visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
									</p>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#concept-classes">Classes</a>
									<a class="nav-link scrollto" href="#concept-repositories">Repositories</a>
									<a class="nav-link scrollto" href="#concept-utilities">Utilities</a>
									<a class="nav-link scrollto" href="#concept-entry-points">Entry-Points</a>
									<a class="nav-link scrollto" href="#concept-load-order">Load Order/Settings</a>
									<a class="nav-link scrollto" href="#concept-whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->