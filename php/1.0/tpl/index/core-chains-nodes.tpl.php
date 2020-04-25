<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Chains', 'queryVars' => ['page' => 'core-chains'], 'active' => false],
		['text' => 'Nodes', 'queryVars' => ['page' => 'core-chains-nodes'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Nodes'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Nodes'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="node-brief" class="doc-section">
									<div class="section-block">
										<p>
											Nodes in the <em>Chain</em> system exist to process data (dispatches).  They are connected to each other to
											form chains using the <em>ChainHelper</em> class, and receive a <em>DispatchBase</em> when they are asked
											to execute their <code>process()</code> method by the <em>ChainHelper</em>.
										</p>
									</div>
								</section>

								<section id="node-nodebase" class="doc-section">
									<h2 class="section-title">DispatchBase</h2>

									<p>
										All nodes used with the provided <em>ChainHelper</em> must implement the <code>NodeBase</code> abstract
										class.  This provides meta information for the node and reqiures developers to implement a
										<code>process()</code> method.  The following properties and methods are available on the
										<code>NodeBase</code> abstract:
									</p>

									<h5>Properties</h5>
									<p class="properties">
										protected <span class="type">string</span> <span class="prop">$_key</span>
										<span class="prop-desc">String identifier for node, must be set for node to be usable</span>

										protected <span class="type">string</span> <span class="prop">$_version</span>
										<span class="prop-desc">String version for node, must be set for node to be usable</span>
									</p>

									<h5>Methods</h5>
									<p class="methods">
										public <span class="type">string</span> <span class="method">__toString()</span>
										<span class="method-desc">Converts the class into a readable string</span>

										public <span class="type">string</span> <span class="method">getKey()</span>
										<span class="method-desc">Returns the node key value</span>

										public <span class="type">string</span> <span class="method">getVersion()</span>
										<span class="method-desc">Returns the node version value</span>

										public <span class="type">boolean</span> <span class="method">isValid()</span>
										<span class="method-desc">Returns whether or not the node is considered valid for processing (both key and version set, at minimum)</span>

										abstract public <span class="type">void</span> <span class="method">process(<span class="type">mixed</span> $sender, <span class="type">DispatchBase</span> &$dispatch)</span>
										<span class="method-desc">Abstract method for processing dispatch; Only called if node is valid</span>

										protected <span class="type">NodeBase</span> <span class="method">setKey(<span class="type">string</span> $key)</span>
										<span class="method-desc">Sets the node's key value</span>

										protected <span class="type">NodeBase</span> <span class="method">setVersion(<span class="type">string</span> $version)</span>
										<span class="method-desc">Sets the node's version value</span>
									</p>
								</section>

								<section id="node-example" class="doc-section">
									<h2 class="section-title">Examples</h2>

									<div class="section-block">
										<p>
											For examples, see the 'Node' section of the
											<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a> page.
										</p>
									</div>
								</section>

								<section id="node-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-dispatches'])?>">Dispatches</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-chainhelper'])?>">ChainHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-chainhelper'])?>">ChainHelper</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#node-nodebase">NodeBase</a>
									<a class="nav-link scrollto" href="#node-example">Examples</a>
									<a class="nav-link scrollto" href="#node-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->