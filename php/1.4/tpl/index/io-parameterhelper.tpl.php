<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'ParameterHelper', 'queryVars' => ['page' => 'io-parameterhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - ParameterHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'ParameterHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="parameterhelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>ParameterHelper</code> class provides some structure to dealing with array-based parameter sets,
											such as those from super globals like <code>$_GET</code> or <code>$_POST</code>.
										</p>
									</div>
								</section>

								<section id="parameterhelper-example" class="doc-section">
									<h2 class="section-title">Example</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>use Stoic\Utilities\ParameterHelper;

$ph = new ParameterHelper($_POST);

if ($ph->has('someVar')) {
    echo($ph->get('someVar')); // retrieves the raw parameter
}

if ($ph->hasAll('stringVar', 'intVar')) {
    echo($ph->getString('stringVar'));
    echo($ph->getInt('intVar'));
}

// Immutable operations return new instances
$ph2 = $ph->withParameter('newKey', 'newValue');
$ph3 = $ph->withoutParameter('removeMe');
</code></pre>
										</div>
									</div>
								</section>

								<section id="parameterhelper-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">ParameterHelper</span> <span class="method">__construct(<span class="type">array</span> $params = [], <span class="type">?SanitationHelper</span> $sanitizer = null)</span>
											<span class="method-desc">Creates a new helper wrapping the given parameters with optional sanitation</span>

											public <span class="type">int</span> <span class="method">count()</span>
											<span class="method-desc">Returns the number of parameters in the collection</span>

											public <span class="type">mixed</span> <span class="method">get(<span class="type">?string</span> $key, <span class="type">mixed</span> $default = null, <span class="type">?string</span> $sanitizer = null)</span>
											<span class="method-desc">Returns the value for the given key with optional default and sanitizer</span>

											public <span class="type">?bool</span> <span class="method">getBool(<span class="type">string</span> $key, <span class="type">?bool</span> $default = null)</span>
											<span class="method-desc">Returns the parameter as a boolean value</span>

											public <span class="type">?float</span> <span class="method">getFloat(<span class="type">string</span> $key, <span class="type">?float</span> $default = null)</span>
											<span class="method-desc">Returns the parameter as a float value</span>

											public <span class="type">?int</span> <span class="method">getInt(<span class="type">string</span> $key, <span class="type">?int</span> $default = null)</span>
											<span class="method-desc">Returns the parameter as an integer value</span>

											public <span class="type">mixed</span> <span class="method">getJson(<span class="type">string</span> $key, <span class="type">bool</span> $asArray = false, <span class="type">mixed</span> $default = null)</span>
											<span class="method-desc">Returns the parameter as decoded JSON</span>

											public <span class="type">array</span> <span class="method">getSource()</span>
											<span class="method-desc">Returns the raw source array</span>

											public <span class="type">?string</span> <span class="method">getString(<span class="type">string</span> $key, <span class="type">mixed</span> $default = null)</span>
											<span class="method-desc">Returns the parameter as a string value</span>

											public <span class="type">bool</span> <span class="method">has(<span class="type">string</span> $key)</span>
											<span class="method-desc">Returns whether a parameter exists in the collection</span>

											public <span class="type">bool</span> <span class="method">hasAll(<span class="type">string</span> ...$keys)</span>
											<span class="method-desc">Returns whether all of the given keys exist</span>

											public <span class="type">bool</span> <span class="method">hasAny(<span class="type">string</span> ...$keys)</span>
											<span class="method-desc">Returns whether any of the given keys exist</span>

											public <span class="type">ParameterHelper</span> <span class="method">withParameter(<span class="type">mixed</span> $parameter, <span class="type">mixed</span> $value)</span>
											<span class="method-desc">Returns a new ParameterHelper with the given parameter added</span>

											public <span class="type">ParameterHelper</span> <span class="method">withParameters(<span class="type">array</span> $parameters)</span>
											<span class="method-desc">Returns a new ParameterHelper with the given parameters added</span>

											public <span class="type">ParameterHelper</span> <span class="method">withoutParameter(<span class="type">mixed</span> $parameter)</span>
											<span class="method-desc">Returns a new ParameterHelper with the given parameter removed</span>

											public <span class="type">ParameterHelper</span> <span class="method">withoutParameters(<span class="type">array</span> $parameters)</span>
											<span class="method-desc">Returns a new ParameterHelper with the given parameters removed</span>
										</p>
									</div>
								</section>

								<section id="parameterhelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-cliscripthelper'])?>">CliScriptHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-filehelper-examples'])?>">FileHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-stringhelper'])?>">StringHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a> class,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#parameterhelper-brief">Overview</a>
									<a class="nav-link scrollto" href="#parameterhelper-example">Example</a>
									<a class="nav-link scrollto" href="#parameterhelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#parameterhelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->