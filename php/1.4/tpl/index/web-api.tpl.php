<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'API Helpers', 'queryVars' => ['page' => 'web-api'], 'active' => true]
	],
	'title' => 'Stoic:PHP - API Helpers'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'API Helpers'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="api-brief" class="doc-section">
									<div class="section-block">
										<p>
											The API subsystem in the <em>Web</em> component provides tools for building RESTful API endpoints.  It
											includes a specialized <code>Api\Stoic</code> class for routing and authorization, a <code>Response</code>
											container for structured output, a <code>BaseDbApi</code> base class for database-backed endpoints, and
											an authorization dispatch system built on the chain pattern.
										</p>
									</div>
								</section>

								<section id="api-stoic" class="doc-section">
									<h2 class="section-title">Api\Stoic</h2>

									<div class="section-block">
										<p>
											The <code>Api\Stoic</code> class extends the base <code>Stoic</code> class to provide API routing
											and authorization.  Endpoints are registered with HTTP verb filters, URL patterns, and optional
											authorization roles:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\Api\Stoic as ApiStoic;
use Stoic\Web\Api\Response;
use Stoic\Web\Request;
use Stoic\Web\Resources\PageVariables;
use Stoic\Web\Resources\HttpStatusCodes;

$api = ApiStoic::getInstance(
    '/path/to/core',
    PageVariables::fromGlobals(),
    null,
    file_get_contents('php://input')
);

// Register a GET endpoint with a URL pattern
$api->registerEndpoint(
    'GET',
    '/users/(\d+)',
    function (Request $request, ?array $matches) {
        $response = new Response(HttpStatusCodes::OK, [
            'id'   => (int)$matches[1],
            'name' => 'Example User'
        ]);

        echo json_encode($response->getData());
    },
    true // requires authorization
);

// Handle the incoming request
$api->handle('request_uri');
</code></pre>
										</div>

										<p class="methods">
											public <span class="type">void</span> <span class="method">handle(<span class="type">string</span> $urlParam)</span>
											<span class="method-desc">Matches the current request against registered endpoints and dispatches it</span>

											public <span class="type">void</span> <span class="method">linkAuthorizationNode(<span class="type">NodeBase</span> &amp;$node)</span>
											<span class="method-desc">Registers a node to handle authorization checks via the chain system</span>

											public <span class="type">void</span> <span class="method">registerEndpoint(<span class="type">?string</span> $verbs, <span class="type">?string</span> $pattern, <span class="type">callable</span> $callback, <span class="type">mixed</span> $authRoles)</span>
											<span class="method-desc">Registers a callable endpoint with optional verb filtering, URL pattern matching, and authorization roles</span>

											public <span class="type">void</span> <span class="method">setHttpResponseCode(<span class="type">int</span> $code)</span>
											<span class="method-desc">Sets the HTTP response status code</span>
										</p>
									</div>
								</section>

								<section id="api-response" class="doc-section">
									<h2 class="section-title">Response</h2>

									<div class="section-block">
										<p>
											The <code>Response</code> class provides a semi-structured container for API responses with an
											HTTP status code and a data payload:
										</p>

										<p class="methods">
											public <span class="type">Response</span> <span class="method">__construct(<span class="type">?int|HttpStatusCodes</span> $status, <span class="type">mixed</span> $data)</span>
											<span class="method-desc">Creates a new response with a status code and data</span>

											public <span class="type">mixed</span> <span class="method">getData()</span>
											<span class="method-desc">Returns the response data payload</span>

											public <span class="type">HttpStatusCodes</span> <span class="method">getStatus()</span>
											<span class="method-desc">Returns the HTTP status code enum</span>

											public <span class="type">void</span> <span class="method">setAsError(<span class="type">string</span> $message, <span class="type">int|HttpStatusCodes</span> $status)</span>
											<span class="method-desc">Converts the response to an error with a message and status code</span>

											public <span class="type">void</span> <span class="method">setData(<span class="type">mixed</span> $data)</span>
											<span class="method-desc">Sets the response data payload</span>

											public <span class="type">void</span> <span class="method">setStatus(<span class="type">int|HttpStatusCodes</span> $status)</span>
											<span class="method-desc">Sets the HTTP status code</span>
										</p>
									</div>
								</section>

								<section id="api-basedbapi" class="doc-section">
									<h2 class="section-title">BaseDbApi</h2>

									<div class="section-block">
										<p>
											The abstract <code>BaseDbApi</code> class extends <code>BaseDbClass</code> from the PDO component
											and provides helper methods for building API endpoint classes that need database access:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\Api\BaseDbApi;
use Stoic\Web\Api\Response;
use Stoic\Web\Request;
use Stoic\Web\Resources\HttpStatusCodes;

class UserApi extends BaseDbApi {
    public function getUser(Request $request, ?array $matches) : Response {
        $response = $this->newResponse();
        $userId = (int)$matches[1];

        $stmt = $this->db->prepare(
            "SELECT * FROM `Users` WHERE `ID` = :id"
        );
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user === false) {
            $response->setAsError(
                'User not found',
                HttpStatusCodes::NOT_FOUND
            );
        } else {
            $response->setStatus(HttpStatusCodes::OK);
            $response->setData($user);
        }

        return $response;
    }
}
</code></pre>
										</div>

										<p>
											Protected helper methods available:
										</p>

										<p class="methods">
											protected <span class="type">Response</span> <span class="method">newResponse()</span>
											<span class="method-desc">Creates a new empty Response object</span>

											protected <span class="type">bool</span> <span class="method">requestHasInputVars(<span class="type">Request</span> $request, <span class="type">array</span> $keysToFind)</span>
											<span class="method-desc">Checks whether the request input contains all of the specified keys</span>
										</p>
									</div>
								</section>

								<section id="api-authorization" class="doc-section">
									<h2 class="section-title">Authorization</h2>

									<div class="section-block">
										<p>
											The API system uses the chain pattern for authorization.  When an endpoint specifies authorization
											roles, the <code>ApiAuthorizationDispatch</code> is sent through the authorization chain before the
											endpoint callback is invoked.
										</p>

										<p>
											To implement authorization, create a node that processes the dispatch:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;
use Stoic\Chain\NodeBase;
use Stoic\Web\Resources\ApiAuthorizationDispatch;

class MyAuthNode extends NodeBase {
    public function __construct() {
        $this->setKey('MyAuthNode');
        $this->setVersion('1.0.0');
    }

    public function process(
        mixed $sender,
        DispatchBase &$dispatch
    ) : void {
        if (!($dispatch instanceof ApiAuthorizationDispatch)) {
            return;
        }

        $input = $dispatch->getInput();
        $roles = $dispatch->getRequiredRoles();

        // Your authorization logic here
        if ($this->checkAuth($input, $roles)) {
            $dispatch->authorize();
        }
    }
}

// Register the authorization node
$api->linkAuthorizationNode($authNode);
</code></pre>
										</div>
									</div>
								</section>

								<section id="api-statuscodes" class="doc-section">
									<h2 class="section-title">HttpStatusCodes</h2>

									<div class="section-block">
										<p>
											The <code>HttpStatusCodes</code> enum provides constants for standard HTTP status codes, from
											informational (1xx) through server errors (5xx).  It also includes a <code>getDescription()</code>
											method that returns a human-readable description:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\Resources\HttpStatusCodes;

$code = new HttpStatusCodes(HttpStatusCodes::NOT_FOUND);
echo $code->getDescription(); // "Not Found"
</code></pre>
										</div>

										<p>
											Commonly used constants include:
										</p>

										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>Constant</th>
														<th>Value</th>
														<th>Description</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><code>OK</code></td>
														<td>200</td>
														<td>Success</td>
													</tr>
													<tr>
														<td><code>CREATED</code></td>
														<td>201</td>
														<td>Resource created</td>
													</tr>
													<tr>
														<td><code>BAD_REQUEST</code></td>
														<td>400</td>
														<td>Malformed request</td>
													</tr>
													<tr>
														<td><code>UNAUTHORIZED</code></td>
														<td>401</td>
														<td>Authentication required</td>
													</tr>
													<tr>
														<td><code>FORBIDDEN</code></td>
														<td>403</td>
														<td>Insufficient permissions</td>
													</tr>
													<tr>
														<td><code>NOT_FOUND</code></td>
														<td>404</td>
														<td>Resource not found</td>
													</tr>
													<tr>
														<td><code>INTERNAL_SERVER_ERROR</code></td>
														<td>500</td>
														<td>Server error</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>
											to explore other components.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#api-brief">Overview</a>
									<a class="nav-link scrollto" href="#api-stoic">Api\Stoic</a>
									<a class="nav-link scrollto" href="#api-response">Response</a>
									<a class="nav-link scrollto" href="#api-basedbapi">BaseDbApi</a>
									<a class="nav-link scrollto" href="#api-authorization">Authorization</a>
									<a class="nav-link scrollto" href="#api-statuscodes">Status Codes</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
