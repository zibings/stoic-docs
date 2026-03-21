<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'PageHelper', 'queryVars' => ['page' => 'web-pagehelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - PageHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'PageHelper'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="pagehelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>PageHelper</code> class holds basic information for a web page &mdash; its title, meta tags,
											root path, and provides methods for generating asset paths and performing redirects.  It uses a
											singleton-like pattern, caching instances by page path.
										</p>
									</div>
								</section>

								<section id="pagehelper-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\PageHelper;

$page = PageHelper::getPage(
    'index.php',
    $request->getGet(),
    $request->getPost(),
    $request->getRequest()
);

$page->setTitle('My Page');
$page->setTitlePrefix('Stoic:PHP', ' - ');
$page->addMetaTag('description', 'A sample page');

// Generate asset paths
$cssPath = $page->getAssetPath('~/assets/css/styles.css');
$linkPath = $page->getAssetPath('~/php/1.4/', ['page' => 'toc']);
</code></pre>
										</div>
									</div>
								</section>

								<section id="pagehelper-static" class="doc-section">
									<h2 class="section-title">Static Methods</h2>

									<div class="section-block">
										<p class="methods">
											public static <span class="type">PageHelper</span> <span class="method">getPage(<span class="type">string</span> $pagePath, <span class="type">?ParameterHelper</span> $get, <span class="type">?ParameterHelper</span> $post, <span class="type">?ParameterHelper</span> $request)</span>
											<span class="method-desc">Returns a PageHelper instance for the given path, creating one if it doesn't exist</span>

											public static <span class="type">StringHelper</span> <span class="method">getRootPath(<span class="type">string</span> $pagePath)</span>
											<span class="method-desc">Calculates the root path for a given page path</span>
										</p>
									</div>
								</section>

								<section id="pagehelper-title" class="doc-section">
									<h2 class="section-title">Title Management</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">StringHelper</span> <span class="method">getTitle()</span>
											<span class="method-desc">Returns the full page title (prefix + separator + title)</span>

											public <span class="type">void</span> <span class="method">setTitle(<span class="type">string</span> $title)</span>
											<span class="method-desc">Sets the page title</span>

											public <span class="type">void</span> <span class="method">setTitlePrefix(<span class="type">string</span> $prefix, <span class="type">string</span> $separator)</span>
											<span class="method-desc">Sets a prefix and separator for the page title</span>
										</p>
									</div>
								</section>

								<section id="pagehelper-meta" class="doc-section">
									<h2 class="section-title">Meta Tags</h2>

									<div class="section-block">
										<p>
											Meta tags are stored as <code>HtmlElementHelper</code> objects and can be added and retrieved:
										</p>

										<p class="methods">
											public <span class="type">void</span> <span class="method">addMetaTag(<span class="type">string</span> $name, <span class="type">string</span> $content)</span>
											<span class="method-desc">Adds a meta tag with the given name and content</span>

											public <span class="type">HtmlElementHelper[]</span> <span class="method">getMetaTags()</span>
											<span class="method-desc">Returns all registered meta tag elements</span>
										</p>
									</div>
								</section>

								<section id="pagehelper-paths" class="doc-section">
									<h2 class="section-title">Path Generation</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">StringHelper</span> <span class="method">getAssetPath(<span class="type">string</span> $path, <span class="type">?array</span> $queryVars, <span class="type">bool</span> $includeDomain, <span class="type">int</span> $flags, <span class="type">string</span> $encoding, <span class="type">bool</span> $doubleEncode)</span>
											<span class="method-desc">Generates a URL path for an asset, optionally with query variables</span>

											public <span class="type">StringHelper</span> <span class="method">getName()</span>
											<span class="method-desc">Returns the page name</span>

											public <span class="type">StringHelper</span> <span class="method">getRoot()</span>
											<span class="method-desc">Returns the root path</span>

											public <span class="type">StringHelper</span> <span class="method">getRootUrlPath(<span class="type">bool</span> $includeDomain)</span>
											<span class="method-desc">Returns the root URL path, optionally including the domain</span>

											public <span class="type">StringHelper</span> <span class="method">pathJoin(<span class="type">string</span> ...$paths)</span>
											<span class="method-desc">Joins path segments relative to the root</span>

											public <span class="type">void</span> <span class="method">setRoot(<span class="type">string</span> $root)</span>
											<span class="method-desc">Overrides the calculated root path</span>
										</p>
									</div>
								</section>

								<section id="pagehelper-redirect" class="doc-section">
									<h2 class="section-title">Redirects</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">void</span> <span class="method">redirectTo(<span class="type">string</span> $destination, <span class="type">bool</span> $permanent, <span class="type">bool</span> $includeDomain)</span>
											<span class="method-desc">Sends a redirect header to the given destination; use <code>$permanent</code> for 301 vs 302</span>
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
// Temporary redirect
$page->redirectTo('~/dashboard/', false, false);

// Permanent redirect (301)
$page->redirectTo('~/new-location/', true, false);
</code></pre>
										</div>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-paginatehelper']))?>">PaginateHelper</a>
											within the <em>Web</em> component, or visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#pagehelper-brief">Overview</a>
									<a class="nav-link scrollto" href="#pagehelper-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#pagehelper-static">Static Methods</a>
									<a class="nav-link scrollto" href="#pagehelper-title">Titles</a>
									<a class="nav-link scrollto" href="#pagehelper-meta">Meta Tags</a>
									<a class="nav-link scrollto" href="#pagehelper-paths">Paths</a>
									<a class="nav-link scrollto" href="#pagehelper-redirect">Redirects</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
