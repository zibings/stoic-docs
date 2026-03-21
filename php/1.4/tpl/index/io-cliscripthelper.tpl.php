<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'CliScriptHelper', 'queryVars' => ['page' => 'io-cliscripthelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - CliScriptHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'CliScriptHelper'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="cliscript-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>CliScriptHelper</code> class combines common actions for CLI scripts into one convenient
											helper.  It manages script options (with short and long names), validates required parameters,
											and can display formatted help output and usage examples.
										</p>
									</div>
								</section>

								<section id="cliscript-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Utilities\CliScriptHelper;
use Stoic\Utilities\ConsoleHelper;

$ch = new ConsoleHelper();
$script = new CliScriptHelper('my-script', 'Processes data files', $ch);

$script->addOption(
    'input',      // name
    '-i',         // short name
    '--input',    // long name
    'Input file', // short description
    'Path to the input data file to process', // long description
    true,         // required
    null          // default value
);

$script->addOption(
    'verbose',
    '-v',
    '--verbose',
    'Verbose output',
    'Enable verbose logging during processing',
    false,
    false
);

$script->addExample('php my-script.php -i data.csv');
$script->addExample('php my-script.php --input data.csv --verbose');

// Start the script (checks requirements automatically)
$script->startScript();

// Access parsed options
$options = $script->getOptions();
$inputFile = $options['-i'] ?? $options['--input'] ?? null;
</code></pre>
										</div>
									</div>
								</section>

								<section id="cliscript-properties" class="doc-section">
									<h2 class="section-title">Properties</h2>

									<div class="section-block">
										<p class="properties">
											public <span class="type">string</span> <span class="prop">$name</span>
											<span class="prop-desc">Name of the script</span>

											public <span class="type">string</span> <span class="prop">$description</span>
											<span class="prop-desc">Description of the script</span>
										</p>
									</div>
								</section>

								<section id="cliscript-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">CliScriptHelper</span> <span class="method">__construct(<span class="type">string</span> $name, <span class="type">string</span> $description, <span class="type">ConsoleHelper</span> $ch)</span>
											<span class="method-desc">Creates a new CLI script helper</span>

											public <span class="type">static</span> <span class="method">addExample(<span class="type">string</span> $example)</span>
											<span class="method-desc">Adds a usage example to the help output</span>

											public <span class="type">static</span> <span class="method">addOption(<span class="type">string</span> $name, <span class="type">string</span> $shortName, <span class="type">string</span> $longName, <span class="type">string</span> $shortDescription, <span class="type">string</span> $longDescription, <span class="type">bool</span> $required, <span class="type">mixed</span> $defaultValue)</span>
											<span class="method-desc">Registers a named option with short/long flags, description, and optional default value</span>

											public <span class="type">void</span> <span class="method">checkRequirements()</span>
											<span class="method-desc">Checks if all required options are present; exits with help message if not</span>

											public <span class="type">array</span> <span class="method">getOptions()</span>
											<span class="method-desc">Returns all option values keyed by both short and long names</span>

											public <span class="type">bool</span> <span class="method">satisfiesRequirements()</span>
											<span class="method-desc">Returns whether all required options have been provided</span>

											public <span class="type">static</span> <span class="method">showBasicHelp(<span class="type">?string</span> $message)</span>
											<span class="method-desc">Displays the script name, description, and optional message</span>

											public <span class="type">static</span> <span class="method">showOptionHelp()</span>
											<span class="method-desc">Displays detailed help for all registered options</span>

											public <span class="type">static</span> <span class="method">startScript(<span class="type">bool</span> $checkRequirements)</span>
											<span class="method-desc">Starts script execution, optionally checking required options first</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a>
											within the <em>I/O</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#cliscript-brief">Overview</a>
									<a class="nav-link scrollto" href="#cliscript-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#cliscript-properties">Properties</a>
									<a class="nav-link scrollto" href="#cliscript-methods">Methods</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
