<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Tutorials', 'queryVars' => ['page' => 'tutorials'], 'active' => false],
		['text' => 'Building a FizzBuzz API', 'queryVars' => ['page' => 'tutorial-fizzbuzz-api'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Tutorial: Building a FizzBuzz API'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Tutorial: Building a FizzBuzz API'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="tutorial-intro" class="doc-section">
									<div class="section-block">
										<p>
											In this tutorial we'll build a small FizzBuzz REST API from scratch using the <em>Web</em>,
											<em>PDO</em>, and <em>Core</em> components.  By the end you'll have an endpoint that accepts a
											number, runs it through FizzBuzz logic, and returns a JSON response &mdash; complete with logging
											and database history.
										</p>

										<div class="callout-block callout-info">
											<div class="icon-holder">
												<i class="fas fa-info-circle"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Prerequisites</h4>
												<p>
													This tutorial assumes you have already completed the
													<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'quick-start']))?>">Quick Start</a>
													guide and have a working Stoic:PHP installation with Composer.
												</p>
											</div><!--//content-->
										</div><!--//callout-block-->
									</div>
								</section>

								<section id="tutorial-overview" class="doc-section">
									<h2 class="section-title">Overview</h2>

									<div class="section-block">
										<p>
											Here's what we'll build step-by-step:
										</p>

										<ol>
											<li>A <code>FizzBuzzResult</code> model to persist results to the database</li>
											<li>A repository class to query historical results</li>
											<li>An API endpoint that accepts a number and returns the FizzBuzz value</li>
											<li>Logging and error handling throughout</li>
										</ol>

										<p>
											The final project structure will look like this:
										</p>

										<div class="code-block">
											<h6>Project Structure</h6>

											<pre><code class="language-markup">~/
  vendor/
  inc/
    classes/
      FizzBuzzResult.cls.php
    repositories/
      FizzBuzzResults.rpo.php
    utilities/
      FizzBuzzRoutes.utl.php
  api.php
  siteSettings.json
  composer.json</code></pre>
										</div><!--//code-block-->
									</div>
								</section>

								<section id="tutorial-model" class="doc-section">
									<h2 class="section-title">Step 1: The Model</h2>

									<div class="section-block">
										<p>
											First, create <code>FizzBuzzResult.cls.php</code> inside <code>~/inc/classes/</code>.  This model
											extends <code>BaseDbModel</code> and maps to a database table:
										</p>

										<div class="code-block">
											<h6>inc/classes/FizzBuzzResult.cls.php</h6>

											<pre class="language-php"><code>
use Stoic\Pdo\BaseDbModel;
use Stoic\Pdo\BaseDbTypes;
use Stoic\Pdo\BaseDbColumnFlags;

class FizzBuzzResult extends BaseDbModel {
    public int $id = 0;
    public int $inputNumber = 0;
    public string $result = '';
    public ?\DateTimeInterface $dateCreated = null;


    protected function __setupModel() : void {
        $this->setTableName('FizzBuzzResult');
        $this->setColumn(
            'id', 'ID', BaseDbTypes::INTEGER,
            BaseDbColumnFlags::IS_KEY | BaseDbColumnFlags::AUTO_INCREMENT
        );
        $this->setColumn(
            'inputNumber', 'InputNumber', BaseDbTypes::INTEGER,
            BaseDbColumnFlags::SHOULD_INSERT
        );
        $this->setColumn(
            'result', 'Result', BaseDbTypes::STRING,
            BaseDbColumnFlags::SHOULD_INSERT
        );
        $this->setColumn(
            'dateCreated', 'DateCreated', BaseDbTypes::DATETIME,
            BaseDbColumnFlags::SHOULD_INSERT
        );

        return;
    }

    protected function __canCreate() : bool {
        if ($this->inputNumber < 1) {
            return false;
        }

        $this->dateCreated = new \DateTime('now', new \DateTimeZone('UTC'));

        return true;
    }
}
</code></pre>
										</div><!--//code-block-->

										<div class="callout-block callout-success">
											<div class="icon-holder">
												<i class="fas fa-thumbs-up"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Tip: Column Flags</h4>
												<p>
													Using <code>BaseDbColumnFlags</code> bitmasks (introduced in v1.4) is the recommended way to
													configure columns.  See the
													<a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbmodel']))?>">BaseDbModel</a>
													docs for the full list of flags.
												</p>
											</div><!--//content-->
										</div><!--//callout-block-->

										<p>
											The <code>__canCreate()</code> hook runs before every <code>create()</code> call.  Here we use it
											to reject invalid input and stamp the creation date.  The available CRUD hooks are:
										</p>

										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>Hook</th>
														<th>Runs Before</th>
														<th>Return</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><code>__canCreate()</code></td>
														<td><code>create()</code></td>
														<td><code>bool|ReturnHelper</code></td>
													</tr>
													<tr>
														<td><code>__canRead()</code></td>
														<td><code>read()</code></td>
														<td><code>bool|ReturnHelper</code></td>
													</tr>
													<tr>
														<td><code>__canUpdate()</code></td>
														<td><code>update()</code></td>
														<td><code>bool|ReturnHelper</code></td>
													</tr>
													<tr>
														<td><code>__canDelete()</code></td>
														<td><code>delete()</code></td>
														<td><code>bool|ReturnHelper</code></td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</section>

								<section id="tutorial-repository" class="doc-section">
									<h2 class="section-title">Step 2: The Repository</h2>

									<div class="section-block">
										<p>
											Next, create <code>FizzBuzzResults.rpo.php</code> inside <code>~/inc/repositories/</code>.  This
											class extends <code>StoicDbClass</code> so it receives a <code>PdoHelper</code> automatically:
										</p>

										<div class="code-block">
											<h6>inc/repositories/FizzBuzzResults.rpo.php</h6>

											<pre class="language-php"><code>
use Stoic\Pdo\StoicDbClass;

class FizzBuzzResults extends StoicDbClass {
    public function getRecent(int $limit = 10) : array {
        $results = [];

        $this->tryPdoExcept(function () use ($limit, &$results) {
            $stmt = $this->db->prepare(
                "SELECT * FROM `FizzBuzzResult`
                 ORDER BY `DateCreated` DESC
                 LIMIT :limit"
            );

            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $results[] = FizzBuzzResult::fromArray(
                    $row, $this->db, $this->log
                );
            }
        }, "Failed to retrieve recent FizzBuzz results");

        return $results;
    }
}
</code></pre>
										</div><!--//code-block-->

										<div class="callout-block callout-warning">
											<div class="icon-holder">
												<i class="fas fa-bug"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Watch Out</h4>
												<p>
													The <code>tryPdoExcept()</code> method catches <code>PDOException</code> and logs it for you.
													If you need to handle errors differently, use a standard try/catch block instead.
												</p>
											</div><!--//content-->
										</div><!--//callout-block-->
									</div>
								</section>

								<section id="tutorial-endpoint" class="doc-section">
									<h2 class="section-title">Step 3: The API Endpoint</h2>

									<div class="section-block">
										<p>
											Create the route registration file <code>FizzBuzzRoutes.utl.php</code> in
											<code>~/inc/utilities/</code>, then wire it up in your <code>api.php</code> entry-point:
										</p>

										<div class="code-block">
											<h6>inc/utilities/FizzBuzzRoutes.utl.php</h6>

											<pre class="language-php"><code>
use Stoic\Web\Api\Response;
use Stoic\Web\Request;
use Stoic\Web\Resources\HttpStatusCodes;

function registerFizzBuzzRoutes(\Stoic\Web\Api\Stoic $api, \PDO $db) : void {
    $api->registerEndpoint(
        'GET',
        '/fizzbuzz/(\d+)',
        function (Request $request, ?array $matches) use ($db) {
            $number   = (int) $matches[1];
            $response = new Response(HttpStatusCodes::OK);

            // Classic FizzBuzz
            if ($number % 15 === 0) {
                $value = 'FizzBuzz';
            } elseif ($number % 3 === 0) {
                $value = 'Fizz';
            } elseif ($number % 5 === 0) {
                $value = 'Buzz';
            } else {
                $value = (string) $number;
            }

            // Persist to history
            $model = new FizzBuzzResult($db);
            $model->inputNumber = $number;
            $model->result      = $value;

            $ret = $model->create();

            if ($ret->isBad()) {
                $response->setAsError(
                    'Failed to save result',
                    HttpStatusCodes::INTERNAL_SERVER_ERROR
                );

                echo json_encode($response->getData());

                return;
            }

            $response->setData([
                'input'  => $number,
                'output' => $value,
                'id'     => $model->id
            ]);

            echo json_encode($response->getData());
        },
        false
    );
}
</code></pre>
										</div><!--//code-block-->

										<div class="code-block">
											<h6>api.php</h6>

											<pre class="language-php"><code>
&lt;?php

require('vendor/autoload.php');

use Stoic\Web\Api\Stoic as ApiStoic;
use Stoic\Web\Resources\PageVariables;

$api = ApiStoic::getInstance(
    './',
    PageVariables::fromGlobals(),
    null,
    file_get_contents('php://input')
);

$db = $api->getDb();

registerFizzBuzzRoutes($api, $db);

$api->handle('request_uri');
</code></pre>
										</div><!--//code-block-->
									</div>
								</section>

								<section id="tutorial-testing" class="doc-section">
									<h2 class="section-title">Step 4: Testing It Out</h2>

									<div class="section-block">
										<p>
											Start your local PHP server and hit the endpoint:
										</p>

										<div class="code-block">
											<h6>Shell Commands</h6>

											<pre><code class="language-git">$ php -S localhost:8080
$ curl http://localhost:8080/api.php?request_uri=/fizzbuzz/15</code></pre>
										</div><!--//code-block-->

										<p>
											You should receive a response like:
										</p>

										<div class="code-block">
											<h6>JSON Response</h6>

											<pre><code class="language-javascript">{
    "input": 15,
    "output": "FizzBuzz",
    "id": 1
}</code></pre>
										</div><!--//code-block-->

										<p>
											Here's a quick reference of expected outputs for common inputs:
										</p>

										<div class="table-responsive">
											<table class="table table-striped">
												<thead>
													<tr>
														<th>Input</th>
														<th>Output</th>
														<th>Reason</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><code>1</code></td>
														<td><code>1</code></td>
														<td>Not divisible by 3 or 5</td>
													</tr>
													<tr>
														<td><code>3</code></td>
														<td><code>Fizz</code></td>
														<td>Divisible by 3</td>
													</tr>
													<tr>
														<td><code>5</code></td>
														<td><code>Buzz</code></td>
														<td>Divisible by 5</td>
													</tr>
													<tr>
														<td><code>15</code></td>
														<td><code>FizzBuzz</code></td>
														<td>Divisible by both 3 and 5</td>
													</tr>
													<tr>
														<td><code>30</code></td>
														<td><code>FizzBuzz</code></td>
														<td>Divisible by both 3 and 5</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</section>

								<section id="tutorial-logging" class="doc-section">
									<h2 class="section-title">Step 5: Adding Logging</h2>

									<div class="section-block">
										<p>
											Stoic:PHP includes a PSR-3 logging system.  Let's add a file appender so every FizzBuzz request
											is logged to disk:
										</p>

										<div class="code-block">
											<h6>Adding a Log Appender in api.php</h6>

											<pre class="language-php"><code>
use Stoic\Log\Logger;
use Stoic\IO\LogFileAppender;

$log = $api->getLog();

$fileAppender = new LogFileAppender('./logs/fizzbuzz.log');
$log->addAppender($fileAppender);

$log->info("FizzBuzz API started");
</code></pre>
										</div><!--//code-block-->

										<div class="callout-block callout-danger">
											<div class="icon-holder">
												<i class="fas fa-exclamation-triangle"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Important</h4>
												<p>
													Make sure the <code>~/logs/</code> directory exists and is writable by your web server.
													The <code>LogFileAppender</code> will not create directories for you.
												</p>
											</div><!--//content-->
										</div><!--//callout-block-->

										<p>
											The logging system supports all standard PSR-3 levels:
										</p>

										<p class="methods">
											public <span class="type">void</span> <span class="method">emergency(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">System is unusable</span>

											public <span class="type">void</span> <span class="method">alert(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Action must be taken immediately</span>

											public <span class="type">void</span> <span class="method">critical(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Critical conditions</span>

											public <span class="type">void</span> <span class="method">error(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Runtime errors</span>

											public <span class="type">void</span> <span class="method">warning(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Exceptional occurrences that are not errors</span>

											public <span class="type">void</span> <span class="method">info(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Interesting events</span>

											public <span class="type">void</span> <span class="method">debug(<span class="type">string</span> $message, <span class="type">array</span> $context)</span>
											<span class="method-desc">Detailed debug information</span>
										</p>
									</div>
								</section>

								<section id="tutorial-summary" class="doc-section">
									<h2 class="section-title">Summary</h2>

									<div class="section-block">
										<p>
											In this tutorial you learned how to:
										</p>

										<ul>
											<li>Create a <code>BaseDbModel</code> with column flags and CRUD hooks</li>
											<li>Write a repository using <code>StoicDbClass</code> and <code>tryPdoExcept()</code></li>
											<li>Register API endpoints with <code>Api\Stoic</code></li>
											<li>Return structured responses with <code>Response</code> and <code>HttpStatusCodes</code></li>
											<li>Wire up file-based logging with <code>LogFileAppender</code></li>
										</ul>

										<p>
											For more details on any of the classes used here, visit their reference pages:
										</p>

										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>Class</th>
														<th>Component</th>
														<th>Documentation</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><code>BaseDbModel</code></td>
														<td>PDO</td>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbmodel']))?>">Reference</a></td>
													</tr>
													<tr>
														<td><code>StoicDbClass</code></td>
														<td>PDO</td>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-basedbclass']))?>">Reference</a></td>
													</tr>
													<tr>
														<td><code>PdoHelper</code></td>
														<td>PDO</td>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'pdo-pdohelper']))?>">Reference</a></td>
													</tr>
													<tr>
														<td><code>Api\Stoic</code></td>
														<td>Web</td>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'web-api']))?>">Reference</a></td>
													</tr>
													<tr>
														<td><code>Logger</code></td>
														<td>Core</td>
														<td><a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'core-logging-logger']))?>">Reference</a></td>
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
											Return to the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'tutorials']))?>">Tutorials</a>
											index, or visit the <a href="<?=$this->localUrl($page->getAssetPath('~/php/1.4/', ['page' => 'toc']))?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#tutorial-intro">Introduction</a>
									<a class="nav-link scrollto" href="#tutorial-overview">Overview</a>
									<a class="nav-link scrollto" href="#tutorial-model">Step 1: Model</a>
									<a class="nav-link scrollto" href="#tutorial-repository">Step 2: Repository</a>
									<a class="nav-link scrollto" href="#tutorial-endpoint">Step 3: Endpoint</a>
									<a class="nav-link scrollto" href="#tutorial-testing">Step 4: Testing</a>
									<a class="nav-link scrollto" href="#tutorial-logging">Step 5: Logging</a>
									<a class="nav-link scrollto" href="#tutorial-summary">Summary</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
