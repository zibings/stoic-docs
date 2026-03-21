<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'SanitationHelper', 'queryVars' => ['page' => 'io-sanitationhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - SanitationHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'SanitationHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="sanitationhelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>SanitationHelper</code> class provides some basic sanitizers for scalar types and the interfaces
											to build custom sanitatizers.
										</p>
									</div>
								</section>

								<section id="sanitationhelper-constants" class="doc-section">
									<h2 class="section-title">Built-in Sanitizer Keys</h2>

									<div class="section-block">
										<p>
											The <code>SanitationHelper</code> comes with the following sanitizers registered by default:
										</p>

										<p class="properties">
											<span class="type">string</span> <span class="prop">BOOLEAN</span> (<code>'bool'</code>)
											<span class="prop-desc">Converts values to boolean (handles string "true")</span>

											<span class="type">string</span> <span class="prop">INTEGER</span> (<code>'int'</code>)
											<span class="prop-desc">Converts values to integer</span>

											<span class="type">string</span> <span class="prop">FLOAT</span> (<code>'float'</code>)
											<span class="prop-desc">Converts values to float</span>

											<span class="type">string</span> <span class="prop">STRING</span> (<code>'string'</code>)
											<span class="prop-desc">Converts values to string</span>
										</p>
									</div>
								</section>

								<section id="sanitationhelper-example" class="doc-section">
									<h2 class="section-title">Custom Sanitizer Example</h2>

									<div class="section-block">
										<p>
											You can register custom sanitizers by implementing the <code>SanitizerInterface</code>:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>use Stoic\Utilities\SanitationHelper;
use Stoic\Utilities\Sanitizers\SanitizerInterface;

// This sanitizer turns EVERYTHING into the string "Bob"
class BobSanitizer implements SanitizerInterface {
    public function sanitize(mixed $input) : mixed {
        return "Bob";
    }
}

$sani = new SanitationHelper();
$sani->addSanitizer('bob', BobSanitizer::class);

echo($sani->sanitize(23, 'bob'));       // Bob
echo($sani->sanitize(true, 'bob'));     // Bob
echo($sani->sanitize('Robert', 'bob')); // Bob
</code></pre>
										</div>
									</div>
								</section>

								<section id="sanitationhelper-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">SanitationHelper</span> <span class="method">__construct()</span>
											<span class="method-desc">Creates a new helper with default sanitizers registered</span>

											public <span class="type">SanitationHelper</span> <span class="method">addSanitizer(<span class="type">string</span> $key, <span class="type">string|object</span> $sanitizer)</span>
											<span class="method-desc">Registers a custom sanitizer by key (class name or instance)</span>

											public <span class="type">bool</span> <span class="method">hasSanitizer(<span class="type">string</span> $key)</span>
											<span class="method-desc">Returns whether a sanitizer is registered for the given key</span>

											public <span class="type">mixed</span> <span class="method">sanitize(<span class="type">mixed</span> $input, <span class="type">string</span> $key)</span>
											<span class="method-desc">Sanitizes the input using the sanitizer registered at the given key</span>
										</p>
									</div>
								</section>

								<section id="sanitationhelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-cliscripthelper']))?>">CliScriptHelper</a></li>
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-consolehelper']))?>">ConsoleHelper</a></li>
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-filehelper-examples']))?>">FileHelper</a></li>
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-parameterhelper']))?>">ParameterHelper</a></li>
											<li><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-stringhelper']))?>">StringHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'io-stringhelper']))?>">StringHelper</a> class,
											or visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#sanitationhelper-brief">Overview</a>
									<a class="nav-link scrollto" href="#sanitationhelper-constants">Sanitizers</a>
									<a class="nav-link scrollto" href="#sanitationhelper-example">Custom Example</a>
									<a class="nav-link scrollto" href="#sanitationhelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#sanitationhelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->