<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Chains', 'queryVars' => ['page' => 'core-chains'], 'active' => false],
		['text' => 'ChainHelper', 'queryVars' => ['page' => 'core-chains-chainhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - ChainHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'ChainHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="chainhelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>ChainHelper</code> class provides the basic functionality for the <em>Chain</em> system in
											Stoic:PHP.  Specifically, it allows developers to link nodes together and pass a <code>DispatchBase</code>
											object to the <code>traverse()</code> method, so that the nodes can attempt to process the data.
										</p>
									</div>
								</section>

								<section id="chainhelper-events" class="doc-section">
									<h2 class="section-title">Events vs Chains</h2>

									<p>
										The <code>ChainHelper</code> class was built to operate with multiple linked nodes at any given time, but
										easily lends itself to use as an event dispatcher.  By insantiating a <code>ChainHelper</code> and telling
										it to function as an 'event' chain, the instance will only allow a single node to be linked at all times.
										Additional nodes that are linked to the chain will simply overwrite the previously linked node and take its
										place as the event processor.
									</p>
								</section>

								<section id="chainhelper-class" class="doc-section">
									<p>
										The <code>ChainHelper</code> class provides the following properties and methods:
									</p>

									<h5>Properties</h5>
									<p class="properties">
										protected <span class="type">array</span> <span class="prop">$_nodes</span>
										<span class="prop-desc">Internal collection of linked nodes</span>

										protected <span class="type">boolean</span> <span class="prop">$_isEvent</span>
										<span class="prop-desc">Whether or not the instance is an 'event' chain</span>

										protected <span class="type">boolean</span> <span class="prop">$_doDebug</span>
										<span class="prop-desc">Whether or not the instance should record debug messages</span>

										protected <span class="type">callable</span> <span class="prop">$_logger</span>
										<span class="prop-desc">Optional callback that receives debug messages</span>
									</p>

									<h5>Methods</h5>
									<p class="methods">
										public <span class="type">ChainHelper</span> <span class="method">__construct(<span class="type">boolean</span> $isEvent = false, <span class="type">boolean</span> $doDebug = false)</span>
										<span class="method-desc">Constructor with ability to set event and debug flags for new instance</span>

										public <span class="type">ChainHelper</span> <span class="method">toggleDebug(<span class="type">boolean</span> $doDebug)</span>
										<span class="method-desc">Toggles use of debug messages/callback for instance</span>

										public <span class="type">array</span> <span class="method">getNodeList()</span>
										<span class="method-desc">Returns full list of nodes linked to chain</span>

										public <span class="type">void</span> <span class="method">hookLogger(<span class="type">callable</span> $callback)</span>
										<span class="method-desc">Attaches the given callback to the instance so it can receive debug messages, if toggled</span>

										public <span class="type">boolean</span> <span class="method">isEvent()</span>
										<span class="method-desc">Returns whether or not the instance is setup as an 'event' chain</span>

										public <span class="type">ChainHelper</span> <span class="method">linkNode(<span class="type">NodeBase</span> $node)</span>
										<span class="method-desc">Attaches a valid node to the chain</span>

										public <span class="type">boolean</span> <span class="method">traverse(<span class="type">DispatchBase</span> &$dispatch, <span class="type">mixed</span> $sender = null)</span>
										<span class="method-desc">Begins to distribute the given dispatch to every linked node on the chain, optionally with a sender argument for additional context</span>

										protected <span class="type">void</span> <span class="method">log(<span class="type">string</span> $message)</span>
										<span class="method-desc">Conditionally sends the message to the registered logger callback</span>
									</p>
								</section>

								<section id="chainhelper-example" class="doc-section">
									<h2 class="section-title">Examples</h2>

									<div class="section-block">
										<p>
											For examples, see the 'ChainHelper' section of the
											<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a> page.
										</p>
									</div>
								</section>

								<section id="chainhelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-dispatches'])?>">Dispatches</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-nodes'])?>">Nodes</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">examples</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#chainhelper-events">Events vs Chains</a>
									<a class="nav-link scrollto" href="#chainhelper-class">Properties/Methods</a>
									<a class="nav-link scrollto" href="#chainhelper-example">Examples</a>
									<a class="nav-link scrollto" href="#chainhelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->