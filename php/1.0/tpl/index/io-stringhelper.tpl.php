<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'StringHelper', 'queryVars' => ['page' => 'io-stringhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - StringHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'StringHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="stringhelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>SanitationHelper</code> class provides some basic sanitizers for scalar types and the interfaces
											to build custom sanitatizers.
										</p>
									</div>
								</section>

								<section id="stringhelper-properties" class="doc-section">
									<h2 class="section-title">Example</h2>

									<div class="code-block">
										<pre class="language-php"><code>use Stoic\Utilities\StringHelper;

$str = new StringHelper('Robert');
$str->replaceOnce('R', 'B'); // string is now 'Bobert'
echo($str->subString(0, 3); // prints out 'Bob'
</code></pre>
									</div>
								</section>

								<section id="stringhelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-filehelper-examples'])?>">FileHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logconsoleappender'])?>">LogConsoleAppender</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logfileappender'])?>">LogFileAppender</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-pdo'])?>">PDO</a> component,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#stringhelper-properties">Properties</a>
									<a class="nav-link scrollto" href="#stringhelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#stringhelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->