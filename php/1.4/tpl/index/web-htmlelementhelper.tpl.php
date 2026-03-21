<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'HtmlElementHelper', 'queryVars' => ['page' => 'web-htmlelementhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - HtmlElementHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'HtmlElementHelper'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="htmlelement-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>HtmlElementHelper</code> class aids in the programmatic generation of basic HTML elements.
											It allows you to build elements with attributes and content, then render them as strings.
										</p>
									</div>
								</section>

								<section id="htmlelement-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\HtmlElementHelper;

// Create a meta tag
$meta = new HtmlElementHelper('meta');
$meta->addAttribute('name', 'description');
$meta->addAttribute('content', 'My page description');
echo $meta->render(true);
// &lt;meta name="description" content="My page description" /&gt;

// Create a div with content
$div = new HtmlElementHelper('div');
$div->addAttribute('class', 'container');
$div->setContents('Hello, world!');
echo $div->render(true);
// &lt;div class="container"&gt;Hello, world!&lt;/div&gt;
</code></pre>
										</div>
									</div>
								</section>

								<section id="htmlelement-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">HtmlElementHelper</span> <span class="method">__construct(<span class="type">string</span> $tag)</span>
											<span class="method-desc">Creates a new helper for the given HTML tag</span>

											public <span class="type">void</span> <span class="method">addAttribute(<span class="type">string</span> $name, <span class="type">string</span> $value)</span>
											<span class="method-desc">Adds an attribute to the element</span>

											public <span class="type">void</span> <span class="method">appendContents(<span class="type">string</span> $contents)</span>
											<span class="method-desc">Appends to the element's inner content</span>

											public <span class="type">array</span> <span class="method">getAttributes()</span>
											<span class="method-desc">Returns all configured attributes</span>

											public <span class="type">StringHelper</span> <span class="method">getContents()</span>
											<span class="method-desc">Returns the element's inner content</span>

											public <span class="type">void</span> <span class="method">setContents(<span class="type">string|StringHelper</span> $contents)</span>
											<span class="method-desc">Sets the element's inner content, replacing any existing content</span>

											public <span class="type">StringHelper</span> <span class="method">render(<span class="type">bool</span> $return)</span>
											<span class="method-desc">Renders the element as an HTML string; if <code>$return</code> is true, returns the string instead of echoing</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'web-pagehelper'])?>">PageHelper</a>
											within the <em>Web</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#htmlelement-brief">Overview</a>
									<a class="nav-link scrollto" href="#htmlelement-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#htmlelement-methods">Methods</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
