<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'Request', 'queryVars' => ['page' => 'web-request'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Request'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Request'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="request-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>Request</code> class represents a single HTTP request and wraps PHP's superglobals into
											a clean, typed interface.  Rather than accessing <code>$_GET</code>, <code>$_POST</code>, and other
											superglobals directly, the <code>Request</code> class provides <code>ParameterHelper</code> instances
											for each, giving you typed access with default values and sanitation.
										</p>
									</div>
								</section>

								<section id="request-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<p>
											A <code>Request</code> is typically created for you by the <code>Stoic</code> class, but can also
											be created directly:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\Request;
use Stoic\Web\Resources\PageVariables;

$request = new Request(
    PageVariables::fromGlobals(),
    file_get_contents('php://input')
);

// Access superglobals as ParameterHelper instances
$userId = $request->getGet()->getInt('user_id');
$name   = $request->getPost()->getString('name');
$token  = $request->getServer()->getString('HTTP_AUTHORIZATION');

// Check the request method
$method = $request->getRequestType(); // RequestType enum
</code></pre>
										</div>
									</div>
								</section>

								<section id="request-accessors" class="doc-section">
									<h2 class="section-title">Parameter Accessors</h2>

									<div class="section-block">
										<p>
											Each PHP superglobal is accessible as a <code>ParameterHelper</code> instance, which gives you
											typed getters with defaults and optional sanitation:
										</p>

										<p class="methods">
											public <span class="type">ParameterHelper</span> <span class="method">getCookies()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_COOKIE</code></span>

											public <span class="type">ParameterHelper</span> <span class="method">getEnv()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_ENV</code></span>

											public <span class="type">ParameterHelper</span> <span class="method">getGet()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_GET</code></span>

											public <span class="type">ParameterHelper</span> <span class="method">getInput()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping the parsed request body (JSON)</span>

											public <span class="type">ParameterHelper</span> <span class="method">getPost()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_POST</code></span>

											public <span class="type">ParameterHelper</span> <span class="method">getRequest()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_REQUEST</code></span>

											public <span class="type">ParameterHelper</span> <span class="method">getServer()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_SERVER</code></span>

											public <span class="type">ParameterHelper</span> <span class="method">getSession()</span>
											<span class="method-desc">Returns a ParameterHelper wrapping <code>$_SESSION</code></span>
										</p>
									</div>
								</section>

								<section id="request-metadata" class="doc-section">
									<h2 class="section-title">Request Metadata</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">string</span> <span class="method">getContentType()</span>
											<span class="method-desc">Returns the content type of the request</span>

											public <span class="type">FileUploadHelper</span> <span class="method">getFiles()</span>
											<span class="method-desc">Returns the file uploads helper for this request</span>

											public <span class="type">mixed</span> <span class="method">getRawInput()</span>
											<span class="method-desc">Returns the raw, unparsed request input</span>

											public <span class="type">RequestType</span> <span class="method">getRequestType()</span>
											<span class="method-desc">Returns the HTTP method as a RequestType enum</span>

											public <span class="type">PageVariables</span> <span class="method">getVariables()</span>
											<span class="method-desc">Returns the raw PageVariables used to construct the request</span>

											public <span class="type">bool</span> <span class="method">hasFileUploads()</span>
											<span class="method-desc">Returns whether the request includes uploaded files</span>

											public <span class="type">bool</span> <span class="method">isValid()</span>
											<span class="method-desc">Returns whether the request was successfully parsed</span>
										</p>
									</div>
								</section>

								<section id="request-pagevariables" class="doc-section">
									<h2 class="section-title">PageVariables</h2>

									<div class="section-block">
										<p>
											The <code>PageVariables</code> struct holds the raw PHP superglobal arrays.  In most cases you'll use
											<code>PageVariables::fromGlobals()</code> to capture the current request state, but you can also
											construct it manually for testing:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\Resources\PageVariables;

// From live request
$vars = PageVariables::fromGlobals();

// For testing
$vars = new PageVariables(
    cookie:  [],
    env:     [],
    files:   [],
    get:     ['page' => 'home'],
    post:    [],
    request: ['page' => 'home'],
    server:  ['REQUEST_METHOD' => 'GET'],
    session: []
);
</code></pre>
										</div>
									</div>
								</section>

								<section id="request-types" class="doc-section">
									<h2 class="section-title">RequestType Enum</h2>

									<div class="section-block">
										<p>
											The <code>RequestType</code> enum represents HTTP request methods:
										</p>

										<p class="properties">
											<span class="type">int</span> <span class="prop">DELETE</span>
											<span class="prop-desc">HTTP DELETE method</span>

											<span class="type">int</span> <span class="prop">ERROR</span>
											<span class="prop-desc">Error/unknown request type</span>

											<span class="type">int</span> <span class="prop">GET</span>
											<span class="prop-desc">HTTP GET method</span>

											<span class="type">int</span> <span class="prop">HEAD</span>
											<span class="prop-desc">HTTP HEAD method</span>

											<span class="type">int</span> <span class="prop">OPTIONS</span>
											<span class="prop-desc">HTTP OPTIONS method</span>

											<span class="type">int</span> <span class="prop">PATCH</span>
											<span class="prop-desc">HTTP PATCH method</span>

											<span class="type">int</span> <span class="prop">POST</span>
											<span class="prop-desc">HTTP POST method</span>

											<span class="type">int</span> <span class="prop">PUT</span>
											<span class="prop-desc">HTTP PUT method</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-fileuploadhelper']))?>">FileUploadHelper</a>
											within the <em>Web</em> component, or visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#request-brief">Overview</a>
									<a class="nav-link scrollto" href="#request-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#request-accessors">Accessors</a>
									<a class="nav-link scrollto" href="#request-metadata">Metadata</a>
									<a class="nav-link scrollto" href="#request-pagevariables">PageVariables</a>
									<a class="nav-link scrollto" href="#request-types">RequestType</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
