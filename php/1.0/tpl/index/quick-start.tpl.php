<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Quick Start', 'queryVars' => ['page' => 'quick-start'], 'active' => true]
	]
]); ?>

					<div id="doc-header" class="doc-header text-center">
						<h1 class="doc-title"><i class="icon fa fa-paper-plane"></i> Quick Start</h1>
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
								<section id="installation-section" class="doc-section">
									<h2 class="section-title">Installation</h2>

									<div class="section-block">
										<p>
											Installation can be done using <a href="//getcomposer.org" target="_blank">Composer</a>/<a href="//packagist.org" target="_blank">Packagist</a>.  Simply enter the following commands in your shell
											and all of the Stoic components will be installed for a website to be built:
										</p>

										<div class="code-block">
											<h6>Require 'Web' component with Composer:</h6>

											<p>
												<code>composer require stoic/web</code>
											</p>
										</div>

										<div class="code-block">
											<h6>Initialize the default Stoic directories:</h6>

											<p>
												<code>vendor/bin/stoic-create</code>
											</p>
										</div>
									</div><!--//section-block-->
								</section><!--//doc-section-->

								<section id="folder-structure" class="doc-section">
									<h2 class="section-title">Folder Structure</h2>

									<div class="section-block">
										<p>
											Stoic will have created the following folder structure for your project:
										</p>

										<div class="code-block">
											<code>
												~/inc/classes/<br />
												~/inc/repositories/<br />
												~/inc/utilities/<br />
												~/index.php<br />
												~/siteSettings.json
											</code>
										</div>
									</div>

									<div id="classes-folder" class="section-block">
										<h6>Classes Folder</h6>

										<p>
											This folder should contain any class definitions.  Any files with the extension <code>.cls.php</code> in this folder will be automatically included when Stoic is loaded.
										</p>

										<p>
											For more information on classes, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-classes'])?>">here</a>.
										</p>
									</div>

									<div id="repositories-folder" class="section-block">
										<h6>Repositories Folder</h6>

										<p>
											This folder should contain any repository classes, which normally provide methods for returning data in the form of classes.
											Any files with the extension <code>.rpo.php</code> in this folder will be automatically included when Stoic is loaded.
										</p>

										<p>
											For more information on repositories, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-repositories'])?>">here</a>.
										</p>
									</div>

									<div id="utilities-folder" class="section-block">
										<h6>Utilities Folder</h6>

										<p>
											This folder should contain any files that provide utility functionality for your site.
											Any files with the extension <code>.utl.php</code> in this folder will be automatically included when Stoic is loaded.
										</p>

										<p>
											For more information on utility files, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-utilities'])?>">here</a>.
										</p>
									</div>

									<div id="index-file" class="section-block">
										<h6>Index File</h6>

										<p>
											This file is a quick example of how Stoic can be loaded and used as an entry-point for a website.
										</p>

										<p>
											For more information on entry-points and Stoic, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-entry-points'])?>">here</a>.
										</p>
									</div>

									<div id="settings-file" class="section-block">
										<h6>Settings File</h6>

										<p>
											The settings file <code>siteSettings.json</code> contains the default configuration values for the site.
											The settings file is setup using the <a href="//github.com/AndyM84/config-migration" target="_blank">Config Migration</a> library.
										</p>
									</div>
								</section>

								<section id="example-app" class="doc-section">
									<h2 class="section-title">Quick Example Webpage</h2>

									<div class="section-block">
										<p>
											Replace the contents of the <i>index.php</i> file:
										</p>

										<pre><code class="language-php">&lt;?php

  // Pull in all files via Composer autoload
  require(&quot;vendor/autoload.php&quot;);

  // Grab the Stoic class for use, setting the 'root' directory to the current directory
  use Stoic\Web\Stoic;
  $stoic = Stoic::getInstance(&quot;./&quot;);

  // Retrieve the ParameterHelper for $_GET from the request
  $get = $stoic->getRequest()->getGet();

  // Retrieve the ParameterHelper for $_SERVER from the request
  $server = $stoic->getRequest()->getServer();

  // Output some random information
  echo(&quot;Hello, World!&lt;br /&gt;&quot;);
  echo(&quot;I am {$server->get(&apos;REMOTE_ADDR&apos;)}&quot;);

  // If we have a magical $_GET variable, show it
  if ($get-&gt;has(&apos;magic_var&apos;)) {
    echo(&quot;&lt;br /&gt;Secret Message: {$get->get(&apos;magic_var&apos;)}&quot;);
  }

</code></pre>

										<p>
											Which will produce something similar to:
										</p>

										<code>
											Hello, World!<br />
											I am ::1
										</code>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts'])?>">concepts</a> used within Stoic, or visit the
											<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#installation-section">Installation</a>
									<a class="nav-link scrollto" href="#folder-structure">Folder Structure</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#classes-folder">Classes</a>
										<a class="nav-link scrollto" href="#repositories-folder">Repositories</a>
										<a class="nav-link scrollto" href="#utilities-folder">Utilities</a>
										<a class="nav-link scrollto" href="#index-file">Index</a>
										<a class="nav-link scrollto" href="#settings-file">Settings</a>
									</nav>

									<a class="nav-link scrollto" href="#example-app">Example</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->