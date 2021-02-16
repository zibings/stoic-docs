<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'ConsoleHelper', 'queryVars' => ['page' => 'io-consolehelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - ConsoleHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'ConsoleHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="consolehelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>ConsoleHelper</code> class provides some general functionality that is commonly required when
											dealing with command line (terminal) input/output.
										</p>
									</div>
								</section>

								<section id="consolehelper-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">ConsoleHelper</span> <span class="method">__construct(<span class="type">array</span> $argv = null, <span class="type">boolean</span> $forceCli = false)</span>
											<span class="method-desc">Instantiates a new ConsoleHelper object with the option to provide arguments and force 'CLI' mode</span>

											public <span class="type">boolean</span> <span class="method">compareArg(<span class="type">string</span> $key, <span class="type">string</span> $value, <span class="type">boolean</span> $caseInsensitive = false)</span>
											<span class="method-desc">Compares an argument by key optionally without case sensitivity</span>

											public <span class="type">boolean</span> <span class="method">compareArgAt(<span class="type">integer</span> $index, <span class="type">string</span> $value, <span class="type">boolean</span> $caseInsensitive = false)</span>
											<span class="method-desc">Compares an argument at the given index optionally without case sensitivity</span>

											public <span class="type">null|string</span> <span class="method">get(<span class="type">integer</span> $characters = 1)</span>
											<span class="method-desc">Retrieves the given number of characters from STDIN</span>

											public <span class="type">string</span> <span class="method">getLine()</span>
											<span class="method-desc">Retrieves an entire line from STDIN</span>

											public <span class="type">mixed</span> <span class="method">getParameterWithDefault(<span class="type">string</span> $short,
												<span class="type">string</span> $long,
												<span class="type">mixed</span> $default = null,
												<span class="type">boolean</span> $caseInsensitive = false)</span>
											<span class="method-desc">Attempts to retrieve an argument by short or long key name, returning a default value if not found</span>

											public <span class="type">ReturnHelper</span> <span class="method">getQueriedInput(<span class="type">string</span> $query,
												<span class="type">mixed</span> $defaultValue,
												<span class="type">string</span> $errorMessage,
												<span class="type">integer</span> $maxTries = 5,
												<span class="type">null|callable</span> $validation = null,
												<span class="type">null|callable</span> $sanitation = null)</span>
											<span class="method-desc">Queries a user repeatedly for input</span>

											public <span class="type">mixed</span> <span class="method">getSelf()</span>
											<span class="method-desc">Returns the script being called according to the passed arguments (first arg in $argv)</span>

											public <span class="type">boolean</span> <span class="method">hasArg(<span class="type">string</span> $key, <span class="type">boolean</span> $caseInsensitive = false)</span>
											<span class="method-desc">Checks if the given key exists in the argument list</span>

											public <span class="type">boolean</span> <span class="method">hasShortLongArg(<span class="type">string</span> $short, <span class="type">string</span> $long, <span class="type">boolean</span> $caseInsensitive = false)</span>
											<span class="method-desc">Checks if the given key exists in the argument list</span>

											public <span class="type">boolean</span> <span class="method">isCLI()</span>
											<span class="method-desc">Returns whether or not the PHP invocation is via CLI or is emulating CLI</span>

											public <span class="type">boolean</span> <span class="method">isNaturalCLI()</span>
											<span class="method-desc">Returns whether or not the PHP invocation is via CLI, ignoring any emulation</span>

											public <span class="type">integer</span> <span class="method">numArgs()</span>
											<span class="method-desc">Returns the number of arguments</span>

											public <span class="type">array</span> <span class="method">parameters(<span class="type">boolean</span> $asAssociative = false, <span class="type">boolean</span> $caseSensitive = false)</span>
											<span class="method-desc">Returns the argument collection</span>

											protected <span class="type">array</span> <span class="method">parseParams(<span class="type">array</span> $args, <span class="type">boolean</span> $caseInsensitive = false)</span>
											<span class="method-desc">Parses a collection of arguments into an organized collection</span>

											public <span class="type">void</span> <span class="method">put(<span class="type">string</span> $buf</span>
											<span class="method-desc">Outputs the buffer to STDIN</span>

											public <span class="type">void</span> <span class="method">putLine(<span class="type">string</span> $buf</span>
											<span class="method-desc">Outputs the buffer to STDIN followed by a newline</span>
										</p>
									</div>
								</section>

								<section id="consolehelper-queried" class="doc-section">
									<h2 class="section-title">Queried Input</h2>

									<div class="section-block">
										<p>
											The <code>ConsoleHelper::getQueriedInput()</code> method encapsulates a common scenario of having to prompt
											an end-user for input that is then validated/sanitized.  For example, if you need to ask the user for their
											name:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Utilities\ConsoleHelper;

$ch = new ConsoleHelper($argv);
$name = $ch->qetQueriedInput(
	"Please enter your name", // $query
	null, // $defaultValue
	"You didn't provide a name", // $errorMessage
	3, // $maxTries
	function ($input) { return !empty($input) && strlen($input) > 3; }, // validation callable
	function ($input) { return trim($input); } // sanitation callable
);

/*
	* Will prompt the user up to 3 times to enter their non-empty 4+ character long name...
	*
	*  Please enter your name: Andrew
	*  Hello, Andrew!
	*
	*/

if ($name->isGood()) {
	$ch->putLine("Hello, {$name->getResults()[0]}!");
}
</code></pre>
										</div>
									</div>
								</section>

								<section id="consolehelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-filehelper'])?>">FileHelper</a></li>
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
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-filehelper'])?>">FileHelper</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#consolehelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#consolehelper-queried">Queried Input</a>
									<a class="nav-link scrollto" href="#consolehelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->