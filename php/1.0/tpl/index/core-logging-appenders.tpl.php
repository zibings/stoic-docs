<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Logging', 'queryVars' => ['page' => 'core-logging'], 'active' => false],
		['text' => 'Appenders', 'queryVars' => ['page' => 'core-logging-appenders'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Appenders'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Appenders'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="appenders-brief" class="doc-section">
									<p>
										Appenders in the logging system are nothing more than <code>NodeBase</code> classes that process
										<code>MessageDispatch</code> objects.
									</p>
								</section>

								<section id="appenders-concept" class="doc-section">
									<h2 class="section-title">Concept</h2>

									<div class="section-block">
										<p>
											In order to make the output of log messages a bit more adaptable to different scenarios, we have added the
											concept of 'appenders' to our logging system.  Through these appenders and a <code>Logger</code> instance,
											developers can customize which messages might be output and in what format, simply by changing what
											appenders are loaded.
										</p>
									</div>
								</section>

								<section id="appenders-appenderbase" class="doc-section">
									<h2 class="section-title">AppenderBase</h2>

									<div class="section-block">
										<p>
											The <code>AppenderBase</code> abstract class simply extends the <code>NodeBase</code> abstract class to
											differentiate logging appenders from other processing nodes.  Every appender implementation should still
											validate that the <code>DispatchBase</code> object received for processing is in fact a
											<code>MessageDispatch</code> object.
										</p>
									</div>
								</section>

								<section id="appenders-messagedispatch" class="doc-section">
									<h2 class="section-title">MessageDispatch</h2>

									<div class="section-block">
										<p>
											For full details, please see the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-messages'])?>">Messages</a> page.
										</p>
									</div>
								</section>

								<section id="appenders-nullappender" class="doc-section">
									<h2 class="section-title">NullAppender</h2>

									<div class="section-block">
										<p>
											The only appender included with the base logging system is the <code>NullAppender</code>, which serves as a
											null-sink for any messages it receives.
										</p>
									</div>
								</section>

								<section id="appenders-examples" class="doc-section">
									<h2 class="section-title">Examples</h2>

									<div class="section-block">
										<p>
											For examples, please see the 'Appenders' section of the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a> page.
										</p>
									</div>
								</section>

								<section id="appenders-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-logger'])?>">Logger</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-messages'])?>">Messages</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-messages'])?>">messages</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#appenders-concept">Concept</a>
									<a class="nav-link scrollto" href="#appenders-appenderbase">AppenderBase</a>
									<a class="nav-link scrollto" href="#appenders-messagedispatch">MessageDispatch</a>
									<a class="nav-link scrollto" href="#appenders-nullappender">NullAppender</a>
									<a class="nav-link scrollto" href="#appenders-examples">Examples</a>
									<a class="nav-link scrollto" href="#appenders-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->