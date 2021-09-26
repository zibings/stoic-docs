<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'LogConsoleAppender', 'queryVars' => ['page' => 'io-logconsoleappender'], 'active' => true]
	],
	'title' => 'Stoic:PHP - LogConsoleAppender'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'LogConsoleAppender'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="logconsoleappender-brief" class="doc-section">
									<div class="section-block">
										<p>
											Provides a <code>Stoic\Log\AppenderBase</code> appender to output log messages to STDOUT in the console.
										</p>
									</div>
								</section>

								<section id="logconsoleappender-properties" class="doc-section">
									<h2 class="section-title">Example</h2>

									<div class="code-block">
										<pre class="language-php"><code>use Stoic\Log\Logger;
use Stoic\Utilities\ConsoleHelper;
use Stoic\Utilities\LogConsoleAppender;

$log = new Logger();
$ch  = new ConsoleHelper();
$log->addAppender(new LogConsoleAppender($ch));

$log->info("Testing log info output");

$log->output();

/*
		Logger will now output to STDOUT (date format is displayed in UTC time):

Y-m-d G:i:s.u INFO     Testing log info output
*/
</code></pre>
									</div>
								</section>

								<section id="logconsoleappender-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-filehelper-examples'])?>">FileHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logfileappender'])?>">LogFileAppender</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-stringhelper'])?>">StringHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logfileappender'])?>">LogFileAppender</a> class,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#logconsoleappender-filehelperglobs">FileHelperGlobs</a>
									<a class="nav-link scrollto" href="#logconsoleappender-properties">Properties</a>
									<a class="nav-link scrollto" href="#logconsoleappender-methods">Methods</a>
									<a class="nav-link scrollto" href="#logconsoleappender-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->