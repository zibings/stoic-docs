<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'FileHelper', 'queryVars' => ['page' => 'io-filehelper'], 'active' => false],
		['text' => 'Examples', 'queryVars' => ['page' => 'io-filehelper-examples'], 'active' => true]
	],
	'title' => 'Stoic:PHP - FileHelper Examples'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'FileHelper Examples'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="examples-logger" class="doc-section">
									<h2 class="section-title">FileHelper Examples</h2>

									<p>&nbsp;</p>

									<h6 id="examples-logger-basic">Basic Usage</h6>
									<div class="code-block">
										<pre class="language-php"><code>use Stoic\Utilities\FileHelper;

$fh = new FileHelper('./');

// the '~' prefix on a path will be intelligently replaced with './'
if ($fh->folderExists('~/files') && $fh->fileExists('~/files/myFile.txt')) {
	$contents = $fh->getContents('~/files/myFile.txt');
	$fh->putContents('~/files/myFile.txt', strrev($contents));
}
</code></pre>
									</div>
								</section>

								<section id="examples-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logconsoleappender'])?>">LogConsoleAppender</a></li>
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
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logconsoleappender'])?>">LogconsoleAppender</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#examples-logger">Logger Examples</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#examples-logger-basic">Basic Usage</a>
										<a class="nav-link scrollto" href="#examples-logger-level">Minimum Level</a>
										<a class="nav-link scrollto" href="#examples-logger-context">Interpolation</a>
									</nav>

									<a class="nav-link scrollto" href="#examples-appender">Appender Examples</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#examples-appender-basic">Basic Appender</a>
										<a class="nav-link scrollto" href="#examples-appender-adding">Adding Appenders</a>
									</nav>

									<a class="nav-link scrollto" href="#examples-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->