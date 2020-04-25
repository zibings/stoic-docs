<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Chains', 'queryVars' => ['page' => 'core-chains'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Chains'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Chains'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="core-brief" class="doc-section">
									<div class="section-block">
										<p>
											An execution system inspired by the chain-of-responsibility design pattern.
										</p>
									</div>
								</section>

								<section id="chain-concept" class="doc-section">
									<h2 class="section-title">Concept</h2>

									<div class="section-block">
										<p>
											Similar to the chain-of-responsibility pattern, chains are groups of one (1) or more processing objects -
											called <em>nodes</em> - which receive data as it passes along the group, or chain.
										</p>

										<p>
											The entire system revolves around the <code>ChainHelper</code> class.  It facilitates the linking of
											processing nodes together and sending data along the 'chain' of nodes to be processed in order.  Nodes must
											all derive from the <code>NodeBase</code> abstract class, and the data sent traversing a chain must derive
											from the <code>DispatchBase</code> abstract class.
										</p>
									</div>
								</section>

								<section id="chain-example" class="doc-section">
									<h2 class="section-title">Quick Example</h2>

									<div class="section-block">
										<p>
											Before going any further, let's see a (very simplistic) chain in action:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>&lt;?php

	use Stoic\Chain\DispatchBase;
	use Stoic\Chain\NodeBase;
	use Stoic\Chain\ChainHelper;

	class IncrementDispatch extends DispatchBase {
		public function initialize($input) {
			$this->makeValid();

			return;
		}

		public function increment($number) {
			return ++$number;
		}
	}

	class IncrementNode extends NodeBase {
		public function __construct() {
			$this->setKey('incrementNode');
			$this->setVersion('1.0.0');

			return;
		}

		public function process($sender, DispatchBase &$dispatch) {
			if (!($dispatch instanceof IncrementDispatch)) {
				return;
			}

			$dispatch->setResult($dispatch->increment(1));

			return;
		}
	}

	$chain = new ChainHelper();
	$chain->linkNode(new IncrementNode());

	$dispatch = new IncrementDispatch();
	$dispatch->initialize(null);

	$success = $chain->traverse($dispatch);
	$results = $dispatch->getResults();

	print_r($results);
</code>
</pre>
										</div>

										<p>
											The above example creates two classes:
										</p>

										<ol>
											<li><code>IncrementDispatch</code> - A dispatch class that can increment a number</li>
											<li><code>IncrementNode</code> - A node class that asks the dispatch to increment and store a number</li>
										</ol>

										<p>
											This example isn't very useful, but it does exercise all parts of the Chain system.  For more details on
											the system, read on!
										</p>
									</div>
								</section>

								<section id="chain-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-dispatches'])?>">Dispatches</a></li>
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
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-dispatches'])?>">dispatches</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#chain-concept">Concept</a>
									<a class="nav-link scrollto" href="#chain-example">Example</a>
									<a class="nav-link scrollto" href="#chain-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->