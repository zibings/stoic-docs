<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Logger', 'queryVars' => ['page' => 'core-logging'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Logger'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Logging'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="logging-brief" class="doc-section">
									<p>
										A logging system that is PSR-3 compliant but includes better functionality for controlling output.
									</p>
								</section>

								<section id="logging-concept" class="doc-section">
									<h2 class="section-title">Concept</h2>

									<div class="section-block">
										<p>
											Building on top of the <em>Chain</em> system, the <code>Logger</code> provides a PSR-3 compliant interface
											as well as a way to configure the way messages are output on a per-instance basis.
										</p>

										<p>
											Since the system is PSR-3 compliant, we'll focus only on our usage of logging <em>Appenders</em> and how
											they provide easy configurable output options for your system.  For information on PSR-3 logging, please
											refer to the <a href="https://www.php-fig.org/psr/psr-3" target="_blank">PHP FIG website</a>.
										</p>
									</div>
								</section>

								<section id="logging-example" class="doc-section">
									<h2 class="section-title">End-to-End Example</h2>

									<div class="section-block">
										<p>
											A fully-functional (and very simplistic) example of a new appender:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;
use Stoic\Log\AppenderBase;
use Stoic\Log\Logger;
use Stoic\Log\MessageDispatch;

class EchoAppender extends AppenderBase {
    public function __construct() {
        $this->setKey('EchoAppender');
        $this->setVersion('1.0.0');

        return;
    }

    public function process($sender, DispatchBase &$dispatch) {
        if (!($dispatch instanceof MessageDispatch)) {
            return;
        }

        if (count($dispatch->messages) > 0) {
            foreach (array_values($dispatch->messages) as $message) {
                echo("{$message}\n");
            }
        }

        return;
    }
}

$log = new Logger();
$log->addAppender(new EchoAppender());

$log->info("Testing log info output.");
$log->critical("Testing log critical output.");

$log->output();
</code></pre>
										</div>
									</div>
								</section>

								<section id="logging-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-logger'])?>">Logger</a></li>
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
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-logger'])?>">Logger</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#logging-concept">Concept</a>
									<a class="nav-link scrollto" href="#logging-example">Example</a>
									<a class="nav-link scrollto" href="#logging-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->