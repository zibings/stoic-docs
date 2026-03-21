<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'Stoic', 'queryVars' => ['page' => 'web-stoic'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Stoic'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Stoic'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="stoic-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>Stoic</code> class is the primary orchestrator for page-level operations in the framework.  It manages
											the core services &mdash; configuration, database connections, file operations, logging, and request handling &mdash;
											and makes them available throughout your application.
										</p>

										<p>
											The class follows a singleton-like pattern via <code>getInstance()</code>, ensuring a consistent set of services
											is available for each page request.
										</p>
									</div>
								</section>

								<section id="stoic-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<p>
											Typically you create a <code>Stoic</code> instance at the start of your entry-point script:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\Stoic;
use Stoic\Web\Resources\PageVariables;

$stoic = Stoic::getInstance(
    '/path/to/core',
    PageVariables::fromGlobals()
);

$request = $stoic->getRequest();
$log     = $stoic->getLog();
$fh      = $stoic->getFileHelper();
$db      = $stoic->getDb('main');
</code></pre>
										</div>

										<p>
											Once created, you can retrieve the instance stack at any time using <code>Stoic::getInstanceStack()</code>.
										</p>
									</div>
								</section>

								<section id="stoic-services" class="doc-section">
									<h2 class="section-title">Available Services</h2>

									<div class="section-block">
										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>Method</th>
														<th>Returns</th>
														<th>Description</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><code>getConfig()</code></td>
														<td><code>ConfigContainer</code></td>
														<td>Returns the application configuration container</td>
													</tr>
													<tr>
														<td><code>getCorePath()</code></td>
														<td><code>string</code></td>
														<td>Returns the configured core path for the application</td>
													</tr>
													<tr>
														<td><code>getDb(?string $key)</code></td>
														<td><code>PdoHelper</code></td>
														<td>Returns a database connection by key</td>
													</tr>
													<tr>
														<td><code>getFileHelper()</code></td>
														<td><code>FileHelper</code></td>
														<td>Returns the file helper for filesystem operations</td>
													</tr>
													<tr>
														<td><code>getLog()</code></td>
														<td><code>Logger</code></td>
														<td>Returns the PSR-3 logger instance</td>
													</tr>
													<tr>
														<td><code>getRequest()</code></td>
														<td><code>Request</code></td>
														<td>Returns the current HTTP request object</td>
													</tr>
													<tr>
														<td><code>getSession()</code></td>
														<td><code>ParameterHelper</code></td>
														<td>Returns the session parameter helper</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</section>

								<section id="stoic-files" class="doc-section">
									<h2 class="section-title">File Loading</h2>

									<div class="section-block">
										<p>
											The <code>Stoic</code> class provides a convenient way to load groups of PHP files by extension from
											a directory:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
// Load all .routes.php files from the routes directory
$stoic->loadFilesByExtension(
    '~/routes/',
    '.routes.php',
    true,   // case insensitive
    false   // don't allow reloads
);
</code></pre>
										</div>
									</div>
								</section>

								<section id="stoic-headers" class="doc-section">
									<h2 class="section-title">Header Management</h2>

									<div class="section-block">
										<p>
											You can set HTTP headers through the <code>Stoic</code> instance:
										</p>

										<p class="methods">
											public <span class="type">void</span> <span class="method">setHeader(<span class="type">string</span> $name, <span class="type">string</span> $value, <span class="type">bool</span> $replace, <span class="type">?int</span> $code)</span>
											<span class="method-desc">Sets a named HTTP response header</span>

											public <span class="type">void</span> <span class="method">setRawHeader(<span class="type">string</span> $value)</span>
											<span class="method-desc">Sets a raw HTTP response header string</span>
										</p>
									</div>
								</section>

								<section id="stoic-methods" class="doc-section">
									<h2 class="section-title">Static Methods</h2>

									<div class="section-block">
										<p class="methods">
											public static <span class="type">static</span> <span class="method">getInstance(<span class="type">?string</span> $corePath, <span class="type">?PageVariables</span> $variables, <span class="type">?Logger</span> $log, <span class="type">mixed</span> $input)</span>
											<span class="method-desc">Returns a Stoic instance, creating one if necessary</span>

											public static <span class="type">Stoic[]</span> <span class="method">getInstanceStack()</span>
											<span class="method-desc">Returns the stack of all created Stoic instances</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'web-request'])?>">Request</a>
											within the <em>Web</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#stoic-brief">Overview</a>
									<a class="nav-link scrollto" href="#stoic-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#stoic-services">Services</a>
									<a class="nav-link scrollto" href="#stoic-files">File Loading</a>
									<a class="nav-link scrollto" href="#stoic-headers">Headers</a>
									<a class="nav-link scrollto" href="#stoic-methods">Static Methods</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
