<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Chains', 'queryVars' => ['page' => 'core-chains'], 'active' => false],
		['text' => 'Examples', 'queryVars' => ['page' => 'core-chains-examples'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Chain Examples'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Chain Examples'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="dispatch-examples" class="doc-section">
									<h2 class="section-title">Dispatch Examples</h2>

									<p>&nbsp;</p>

									<h6 id="dispatch-examples-consumable">Consumable Dispatch</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;

// A simple dispatch that can be consumed
class ConsumableDispatch extends DispatchBase {
    // Implement the abstract method with no real use of $input
    public function initialize($input) {
        // Mark the dispatch as consumable, this way chain traversal
        // can be stopped by a node
        $this->makeConsumable();

        // Exclude this if you have something about your $input that
        // isn't valid for this dispatch's type
        $this->makeValid();

        return;
    }
}
</code></pre>
									</div>

									<h6 id="dispatch-examples-stateful">Stateful Dispatch</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;

// A simple dispatch that is stateful
class StatefulDispatch extends DispatchBase {
    // Implement the abstract method with no real use of $input
    public function initialize($input) {
        // Mark the dispatch as stateful so multiple results can be
        // stored in the dispatch by a node (or nodes)
        $this->makeStateful();

        // Exclude this if you have something about your $input that
        // isn't valid for this dispatch's type
        $this->makeValid();

        return;
    }
}
</code></pre>
									</div>

									<h6 id="dispatch-examples-increment">An Increment Dispatch</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;

// A simple dispatch that can be consumed
class IncrementDispatch extends DispatchBase {
    // Private integer for our increment
    private $counter = 0;

    // Implement abstract with override for increment number
    public function initialize($input) {
        // Mark the dispatch as consumable
        $this->makeConsumable();

        // if we have input, check if it's valid
        if ($input !== null) {
            if (is_int($input) === true) {
                // it's valid, so set it and prepare for traversal
                $this->counter = $input;
                $this->makeValid();
            }
        } else {
            // we aren't modifying our counter, so prepare for traversal
            $this->makeValid();
        }

        return;
    }

    // So we can see what our counter is at currently
    public function getCounterValue() {
        return $this->counter;
    }

    // A public method to let a node trigger incrementing the counter
    public function incrementCount() {
        $this->counter++;

        return;
    }
}
</code></pre>
									</div>
								</section>

								<section id="node-examples" class="doc-section">
									<h2 class="section-title">Node Examples</h2>

									<p>&nbsp;</p>

									<h6 id="node-examples-incrementor">An Incrementor Node</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;

// A node that only calls an increment method on the dispatch
class IncrementorNode extends NodeBase {
    // Need to instantiate with key/version info to be valid
    public function __construct() {
        $this->setKey('incrementerNode');
        $this->setVersion('0.0.1');

        return;
    }

    // Implement this to actually perform processing in a chain
    public function process($sender, DispatchBase &$dispatch) {
        if (!($dispatch instanceof IncrementDispatch)) {
            return;
        }

        // Now we're sure it's the dispatch we want, so increment
        // and simply return so the next node in the chain can do
        // its job
        $dispatch->incrementCount();

        return;
    }
}
</code></pre>
									</div>

									<h6 id="node-examples-consumer">A Consumer Node</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;

// A node that simply attempts to consume a dispatch
class ConsumerNode extends NodeBase {
    // Need to instantiate with key/version info to be valid
    public function __construct() {
        $this->setKey('consumerNode');
        $this->setVersion('0.0.1');

        return;
    }

    // Implement this to actually perform processing in a chain
    public function process($sender, DispatchBase &$dispatch) {
        // Since we don't care what kind of dispatch (all should
        // have the consume method), just consume and return
        $dispatch->consume();

        return;
    }
}
</code></pre>
									</div>

									<h6 id="node-examples-chatty">A Chatty Node</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;

// An IncrementerNode that yells out what it's doing
class ChattyNode extends IncrementerNode {
    public function __construct() {
        $this->setKey('chattyNode');
        $this->setVersion('0.0.1');

        return;
    }

    public function process($sender, DispatchBase &$dispatch) {
        if (!($dispatch instanceof IncrementDispatch)) {
            return;
        }

        // Call the IncrementerNode process so it does its job
        parent::process($sender, $dispatch);

        // Echo the current counter value after incrementing
        echo($dispatch->getCounterValue() . " ");

        return;
    }
}
</code></pre>
									</div>
								</section>

								<section id="examples-chain" class="doc-section">
									<h2 class="section-title">Chain Example</h2>

									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\ChainHelper;

// A plain IncrementDispatch
$rawDispatch = new IncrementDispatch();
$rawDispatch->initialize(null);

// An IncrementDispatch that starts at 10
$offsetDispatch = new IncrementDispatch();
$offsetDispatch->initialize(10);

// First chain to execute a few chatty nodes and continue
$simpleChain = new ChainHelper();
$simpleChain->linkNode(new ChattyNode());
$simpleChain->linkNode(new ChattyNode());
$simpleChain->linkNode(new ChattyNode());

// Traverse plain dispatch in loop
for ($i = 0; $i < 3; ++$i) {
    $simpleChain->traverse($rawDispatch);
}

// Should output:
// 1 2 3 4 5 6 7 8 9

// Second chain to execute chatty nodes and a consumer
$stopChain = new ChainHelper();
$stopChain->linkNode(new ChattyNode());
$stopChain->linkNode(new ChattyNode());
$stopChain->linkNode(new ConsumerNode());
$stopChain->linkNode(new ChattyNode());

$stopChain->traverse($offsetDispatch);

// Should output:
// 11 12
</code></pre>
									</div>
								</section>

								<section id="examples-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-dispatches'])?>">Dispatches</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-nodes'])?>">Nodes</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-chains-chainhelper'])?>">ChainHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging'])?>">logging</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#dispatch-examples">Dispatch Examples</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#dispatch-examples-consumable">Consumable</a>
										<a class="nav-link scrollto" href="#dispatch-examples-stateful">Stateful</a>
										<a class="nav-link scrollto" href="#dispatch-examples-increment">Increment</a>
									</nav>

									<a class="nav-link scrollto" href="#node-examples">Node Examples</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#node-examples-incrementor">Incrementor</a>
										<a class="nav-link scrollto" href="#node-examples-consumer">Consumer</a>
										<a class="nav-link scrollto" href="#node-examples-chatty">Chatty</a>
									</nav>

									<a class="nav-link scrollto" href="#examples-chain">Chain Example</a>
									<a class="nav-link scrollto" href="#examples-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->