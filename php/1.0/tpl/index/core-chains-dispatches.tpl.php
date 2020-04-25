<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Chains', 'queryVars' => ['page' => 'core-chains'], 'active' => false],
		['text' => 'Dispatches', 'queryVars' => ['page' => 'core-chains-dispatches'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Dispatches'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Dispatches'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="dispatch-brief" class="doc-section">
									<div class="section-block">
										<p>
											Dispatches in the <em>Chain</em> system are primarily data carriers.  Any information that should be
											carried along a chain to its nodes should be stored within the dispatch.  During traversal, nodes can read
											from the dispatch and write to it for later nodes to see, or for the end result.
										</p>
									</div>
								</section>

								<section id="dispatch-concept" class="doc-section">
									<h2 class="section-title">Concept</h2>

									<div class="section-block">
										<p>
											Dispatches by default have three 'states' that can be queried.  Each defaults to <code>false</code> and
											must be set by any dispatch during initialization.  The states are as follows:
										</p>

										<ul>
											<li>Consumable</li>
											<li>Stateful</li>
											<li>Valid</li>
										</ul>

										<h6>Consumable</h6>
										<p>
											When a dispatch is marked as consumable, it tells the <em>ChainHelper</em> to stop traversing the chain if
											the dispatched is marked as 'consumed' by a node.  This can be helpful for building chains that resemble
											the chain-of-responsibility pattern.
										</p>

										<h6>Stateful</h6>
										<p>
											All nodes are given the ability to store results in dispatches, and the dispatch's stateful setting will
											determine if only one result is allowed or multiple are kept.  If multiple are set, they are returned as an
											array.  A single result is returned if only one result is stored, and null is returned if there are no
											stored results.
										</p>

										<h6>Valid</h6>
										<p>
											In order for the <em>ChainHelper</em> to traverse a chain it must have both nodes and a valid dispatch.
											This gives developers the ability to enforce custom restrictions on data validity at initialization in
											order to stop chain traversal.
										</p>
									</div>
								</section>

								<section id="dispatch-dispatchbase" class="doc-section">
									<h2 class="section-title">DispatchBase</h2>

									<p>
										All dispatches must implement the <code>DispatchBase</code> abstract class.  This provides some minimum
										functionality and requires developers to implement an <code>initialize()</code> method.  The following are
										the properties and methods defined in <code>DispatchBase</code>:
									</p>

									<h5>Properties</h5>
									<p class="properties">
										protected <span class="type">boolean</span> <span class="prop">$_isConsumable</span>
										<span class="prop-desc">Whether or not the dispatch is 'consumable'</span>

										protected <span class="type">boolean</span> <span class="prop">$_isStateful</span>
										<span class="prop-desc">Whether or not the dispatch should retain multiple results</span>

										protected <span class="type">boolean</span> <span class="prop">$_isConsumed</span>
										<span class="prop-desc">Whether or not the dispatch has been consumed by a node</span>

										protected <span class="type">array&lt;mixed&gt;</span> <span class="prop">$_results</span>
										<span class="prop-desc">Collection of results from processing nodes</span>

										protected <span class="type">boolean</span> <span class="prop">$_isValid</span>
										<span class="prop-desc">Whether or not the dispatch is valid for processing</span>
									</p>

									<h5>Methods</h5>
									<p class="methods">
										public <span class="type">string</span> <span class="method">__toString()</span>
										<span class="method-desc">Converts the class into a readable string</span>

										public <span class="type">boolean</span> <span class="method">consume()</span>
										<span class="method-desc">Marks dispatch as having been consumed</span>

										public <span class="type">DateTime</span> <span class="method">getCalledDateTime()</span>
										<span class="method-desc">Returns the date and time the dispatch was marked valid</span>

										public <span class="type">array&lt;mixed&gt;|null</span> <span class="method">getResults()</span>
										<span class="method-desc">Returns any results stored in dispatch, null if none</span>

										abstract public <span class="type">void</span> <span class="method">initialize(<span class="type">mixed</span> $input)</span>
										<span class="method-desc">Abstract method that dispatch classes should use to handle initialization</span>

										public <span class="type">boolean</span> <span class="method">isConsumable()</span>
										<span class="method-desc">Returns whether or not the dispatch can be consumed</span>

										public <span class="type">boolean</span> <span class="method">isConsumed()</span>
										<span class="method-desc">Returns whether or not the dispatch has been consumed</span>

										public <span class="type">boolean</span> <span class="method">isStateful()</span>
										<span class="method-desc">Returns whether or not the dispatch will hold multiple results</span>

										public <span class="type">boolean</span> <span class="method">isValid()</span>
										<span class="method-desc">Returns whether or not the dispatch is valid for traversal</span>

										protected <span class="type">DispatchBase</span> <span class="method">makeConsumable()</span>
										<span class="method-desc">Sets a dispatch as consumable</span>

										protected <span class="type">DispatchBase</span> <span class="method">makeStateful()</span>
										<span class="method-desc">Sets a dispatch as stateful</span>

										protected <span class="type">DispatchBase</span> <span class="method">makeValid()</span>
										<span class="method-desc">Sets dispatch as valid and sets 'called' date and time in UTC offset</span>

										public <span class="type">integer</span> <span class="method">numResults()</span>
										<span class="method-desc">Returns the number of results stored in dispatch</span>

										public <span class="type">DispatchBase</span> <span class="method">setResult(<span class="type">mixed</span> $result)</span>
										<span class="method-desc">Sets a result in the dispatch</span>
									</p>
								</section>

								<section id="dispatch-example" class="doc-section">
									<h2 class="section-title">Examples</h2>

									<div class="section-block">
										<p>
											For examples, see the 'Dispatch' section of the
											<a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a> page.
										</p>
									</div>
								</section>

								<section id="dispatch-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-nodes'])?>">Nodes</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-chainhelper'])?>">ChainHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-examples'])?>">Examples</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-nodes'])?>">nodes</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#dispatch-concept">Concept</a>
									<a class="nav-link scrollto" href="#dispatch-dispatchbase">DispatchBase</a>
									<a class="nav-link scrollto" href="#dispatch-example">Examples</a>
									<a class="nav-link scrollto" href="#dispatch-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->