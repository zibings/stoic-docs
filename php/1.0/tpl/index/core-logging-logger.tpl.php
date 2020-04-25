<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Logging', 'queryVars' => ['page' => 'core-logging'], 'active' => false],
		['text' => 'Logger', 'queryVars' => ['page' => 'core-logging-logger'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Logger'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Logger'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="logger-brief" class="doc-section">
									<p>
										The <code>Logger</code> class provides all of the basic PSR-3 functionality guaranteed by the
										<code>Psr\Log\AbstractLogger</code> abstract class.  Additionally it provides a mechanism to attach one or
										more <em>appenders</em> that implement the <code>Stoic\Log\AppenderBase</code> abstract class and are used
										during log output.
									</p>
								</section>

								<section id="logger-psr3" class="doc-section">
									<h2 class="section-title">PSR-3 Methods</h2>

									<div class="section-block">
										<p>
											This class, by implementing the <code>Psr\Log\AbstractLogger</code> abstract class, guarantees the
											following methods:
										</p>

										<p class="methods">
											public <span class="type">void</span> <span class="method">emergency(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">System is unusable</span>

											public <span class="type">void</span> <span class="method">alert(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Action must be taken immediately</span>

											public <span class="type">void</span> <span class="method">critical(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Critical conditions</span>

											public <span class="type">void</span> <span class="method">error(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Runtime errors that do not require immediate action but should typically be logged and monitored</span>

											public <span class="type">void</span> <span class="type">warning(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Exceptional occurrences that are not errors</span>

											public <span class="type">void</span> <span class="method">notice(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Normal but significant events</span>

											public <span class="type">void</span> <span class="method">info(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Interesting events</span>

											public <span class="type">void</span> <span class="method">debug(<span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Detailed debug information</span>
										</p>
									</div>
								</section>

								<section id="logger-properties" class="doc-section">
									<h2 class="section-title">Properties</h2>

									<div class="section-block">
										<p class="properties">
											private <span class="type">Stoic\Chain\ChainHelper</span> <span class="prop">$appenders</span>
											<span class="prop-desc">Internal instance of a <em>ChainHelper</em> to manage appenders for the object</span>

											private <span class="type">Message[]</span> <span class="prop">$messages</span>
											<span class="prop-desc">Collection of log messages for the object</span>

											protected static <span class="type">string[]</span> <span class="prop">$levels</span>
											<span class="prop-desc">Numerically indexed collection of log levels for 'minimum level' comparisons</span>
										</p>
									</div>
								</section>

								<section id="logger-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">Logger</span> <span class="method">__construct(<span class="type">null|string</span> $minimumLevel = null, <span class="type">null|AppenderBase[]</span> $appenders = null)</span>
											<span class="method-desc">Constructor with optional arguments to set minimum log level and provide collection of <em>AppenderBase</em> appenders to add immediately</span>

											public <span class="type">void</span> <span class="method">addAppender(<span class="type">AppenderBase</span> $appender)</span>
											<span class="method-desc">Attempts to add (link) an appender to the internal <em>ChainHelper</em> for output appenders</span>

											protected <span class="type">string</span> <span class="method">interpolate(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Interpolates context values into a log message</span>

											public <span class="type">void</span> <span class="method">log(<span class="type">string</span> $level, <span class="type">string</span> $message, <span class="type">array</span> $context = array())</span>
											<span class="method-desc">Stores an arbitrary message &amp; log level into the internal collection of <em>Message</em> objects</span>

											protected <span class="type">boolean</span> <span class="method">meetsMinimumLevel(<span class="type">string</span> $level)</span>
											<span class="method-desc">Determines whether or not the given level is at least as 'high' as the object's minimum level</span>

											public <span class="type">void</span> <span class="method">output()</span>
											<span class="method-desc">Triggers a dump of all stored log messages to any configured appenders; Clears internal collection after processing</span>
										</p>
									</div>
								</section>

								<section id="logger-examples" class="doc-section">
									<h2 class="section-title">Examples</h2>

									<div class="section-block">
										<p>
											For examples, please see the 'Logger' section of the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a> page.
										</p>
									</div>
								</section>

								<section id="logger-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-appenders'])?>">Appenders</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-messages'])?>">Messages</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-appenders'])?>">appenders</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#logger-psr3">PSR-3</a>
									<a class="nav-link scrollto" href="#logger-properties">Properties</a>
									<a class="nav-link scrollto" href="#logger-methods">Methods</a>
									<a class="nav-link scrollto" href="#logger-examples">Examples</a>
									<a class="nav-link scrollto" href="#logger-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->