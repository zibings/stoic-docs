<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Logging', 'queryVars' => ['page' => 'core-logging'], 'active' => false],
		['text' => 'Messages', 'queryVars' => ['page' => 'core-logging-messages'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Messages'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Messages'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="messages-brief" class="doc-section">
									<p>
										<em>Messages</em> in the Stoic:PHP logging system are the basic structure that represents logging
										information.  The PSR-3 methods all produce messages behind the scenes which are then fed to any appenders
										so they can be output.
									</p>
								</section>

								<section id="messages-message" class="doc-section">
									<h2 class="section-title">Message Class</h2>

									<div class="section-block">
										<p>
											The <code>Message</code> class provides log message, level, and timestamp information to give necessary
											context for an entry in the logging system.
										</p>

										<h6>Properties</h6>

										<p class="properties">
											public <span class="type">string</span> <span class="prop">$level</span>
											<span class="prop-desc">String value of message's log level</span>

											public <span class="type">string</span> <span class="prop">$message</span>
											<span class="prop-desc">Interpolated string value of log message</span>

											private <span class="type">DateTimeImmutable</span> <span class="prop">$timestamp</span>
											<span class="prop-desc">Immutable DateTime value marking when the message was generated (incl. microseconds)</span>

											private static <span class="type">array</span> <span class="prop">$validLevels</span>
											<span class="prop-desc">Collection of valid level values for checking initialization values</span>
										</p>

										<h6>Methods</h6>

										<p class="methods">
											public <span class="type">Message</span> <span class="method">__construct(<span class="type">string</span> $level, <span class="type">string</span> $message)</span>
											<span class="method-desc">Instantiates a new Message object, validating the log level and marking the timestamp</span>

											public <span class="type">DateTimeImmutable</span> <span class="method">getTimestamp()</span>
											<span class="method-desc">Returns the immutable timestamp value so it can't be overwritten</span>

											public <span class="type">string[]</span> <span class="method">__toArray()</span>
											<span class="method-desc">Returns the Message information as a simplified array</span>

											public <span class="type">string</span> <span class="method">__toJson()</span>
											<span class="method-desc">Returns the Message information as a simplified JSON string</span>

											public <span class="type">string[]</span> <span class="method">jsonSerialize()</span>
											<span class="method-desc">Returns array of Message information for <em>json_encode</em></span>

											public <span class="type">string</span> <span class="method">__toString()</span>
											<span class="method-desc">Returns the Message information as a simplified string</span>
										</p>
									</div>
								</section>

								<section id="messages-messagedispatch" class="doc-section">
									<h2 class="section-title">MessageDispatch Class</h2>

									<div class="section-block">
										<p>
											The <code>MessageDispatch</code> class is used to provide collections of <em>Message</em> objects for
											consumption by appenders.
										</p>

										<h6>Properties</h6>
										<p class="properties">
											public <span class="type">Message[]</span> <span class="prop">$messages</span>
											<span class="prop-desc">Collection of any messages returned by the <em>Logger</em> instance</span>
										</p>

										<h6>Methods</h6>
										<p class="methods">
											public <span class="type">void</span> <span class="method">initialize(<span class="type">Message[]</span> $input)</span>
											<span class="method-desc">Initializes the dispatch message collection</span>
										</p>
									</div>
								</section>

								<section id="messages-examples" class="doc-section">
									<h2 class="section-title">Examples</h2>

									<div class="section-block">
										<p>
											For examples, please see the 'Messages' section of the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a> page.
										</p>
									</div>
								</section>

								<section id="messages-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-logger'])?>">Logger</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-appenders'])?>">Appenders</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">Examples</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-examples'])?>">examples</a>,
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