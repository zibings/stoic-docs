<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'LogFileAppender', 'queryVars' => ['page' => 'io-logfileappender'], 'active' => true]
	],
	'title' => 'Stoic:PHP - LogFileAppender'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'LogFileAppender'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="logfileappender-brief" class="doc-section">
									<div class="section-block">
										<p>
											Provides a <code>Stoic\Log\AppenderBase</code> appender to output log messages to a file.
										</p>
									</div>
								</section>

								<section id="logfileappender-properties" class="doc-section">
									<h2 class="section-title">Example</h2>

									<div class="code-block">
										<pre class="language-php"><code>use Stoic\Log\Logger;
use Stoic\Utilities\FileHelper;
use Stoic\Utilities\LogFileAppender;

$log = new Logger();
$fh  = new FileHelper('./');
$log->addAppender(new LogFileAppender($fh, '~/logs/audit.log'));

$log->info("Testing log info output");

$log->output();

/*
		Logger will now output to ./logs/audit.log (date format is displayed in UTC time):

Y-m-d G:i:s.u INFO     Testing log info output
*/
</code></pre>
									</div>
								</section>

								<section id="logfileappender-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-filehelper-examples'])?>">FileHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-logconsoleappender'])?>">LogConsoleAppender</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-stringhelper'])?>">StringHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a> class,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#logfileappender-properties">Properties</a>
									<a class="nav-link scrollto" href="#logfileappender-methods">Methods</a>
									<a class="nav-link scrollto" href="#logfileappender-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->