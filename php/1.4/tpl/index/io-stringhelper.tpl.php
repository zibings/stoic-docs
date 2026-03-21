<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'StringHelper', 'queryVars' => ['page' => 'io-stringhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - StringHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'StringHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="stringhelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>StringHelper</code> class provides basic helper methods for working with strings.  It wraps
											a string value and exposes common operations like searching, comparing, replacing, and substring extraction
											as chainable or in-place methods.
										</p>
									</div>
								</section>

								<section id="stringhelper-example" class="doc-section">
									<h2 class="section-title">Example</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>use Stoic\Utilities\StringHelper;

$str = new StringHelper('Robert');
$str->replaceOnce('R', 'B'); // string is now 'Bobert'
echo($str->subString(0, 3)); // prints out 'Bob'

// Static join method
$result = StringHelper::join(' - ', 'one', 'two', 'three');
echo($result); // "one - two - three"

// Checking content
$str = new StringHelper('Hello World');
$str->startsWith('Hello');        // true
$str->endsWith('World');          // true
$str->find('World');              // 6
$str->isEmptyOrNull();            // false
</code></pre>
										</div>
									</div>
								</section>

								<section id="stringhelper-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">StringHelper</span> <span class="method">__construct(<span class="type">mixed</span> $source = null)</span>
											<span class="method-desc">Creates a new StringHelper wrapping the given value</span>

											public <span class="type">void</span> <span class="method">append(<span class="type">string|StringHelper</span> $string)</span>
											<span class="method-desc">Appends the given string to the internal value</span>

											public <span class="type">string</span> <span class="method">at(<span class="type">int</span> $position)</span>
											<span class="method-desc">Returns the character at the given position</span>

											public <span class="type">void</span> <span class="method">clear()</span>
											<span class="method-desc">Clears the internal string value</span>

											public <span class="type">bool|int</span> <span class="method">compare(<span class="type">string|StringHelper</span> $string, <span class="type">?int</span> $length, <span class="type">bool</span> $caseInsensitive)</span>
											<span class="method-desc">Compares the string to another, optionally up to a given length</span>

											public <span class="type">StringHelper</span> <span class="method">copy()</span>
											<span class="method-desc">Returns a new StringHelper with a copy of the internal value</span>

											public <span class="type">?string</span> <span class="method">data()</span>
											<span class="method-desc">Returns the raw internal string value</span>

											public <span class="type">bool</span> <span class="method">endsWith(<span class="type">string|StringHelper</span> $string, <span class="type">bool</span> $caseInsensitive)</span>
											<span class="method-desc">Returns whether the string ends with the given suffix</span>

											public <span class="type">bool|int</span> <span class="method">find(<span class="type">string|StringHelper</span> $string, <span class="type">?int</span> $position, <span class="type">bool</span> $caseInsensitive)</span>
											<span class="method-desc">Finds the position of the given substring</span>

											public <span class="type">?string</span> <span class="method">firstChar()</span>
											<span class="method-desc">Returns the first character of the string</span>

											public <span class="type">bool</span> <span class="method">isEmptyOrNull()</span>
											<span class="method-desc">Returns whether the string is empty or null</span>

											public <span class="type">bool</span> <span class="method">isEmptyOrNullOrWhitespace()</span>
											<span class="method-desc">Returns whether the string is empty, null, or only whitespace</span>

											public <span class="type">?string</span> <span class="method">lastChar()</span>
											<span class="method-desc">Returns the last character of the string</span>

											public <span class="type">int</span> <span class="method">length()</span>
											<span class="method-desc">Returns the length of the string</span>

											public <span class="type">void</span> <span class="method">replace(<span class="type">mixed</span> $search, <span class="type">mixed</span> $replace, <span class="type">mixed</span> &amp;$count)</span>
											<span class="method-desc">Replaces all occurrences of search with replace</span>

											public <span class="type">bool</span> <span class="method">replaceContained(<span class="type">string|StringHelper</span> $start, <span class="type">string|StringHelper</span> $end, <span class="type">string|StringHelper</span> $replace, <span class="type">bool</span> $caseInsensitive)</span>
											<span class="method-desc">Replaces content between start and end markers</span>

											public <span class="type">bool</span> <span class="method">replaceOnce(<span class="type">string|StringHelper</span> $search, <span class="type">string|StringHelper</span> $replace, <span class="type">mixed</span> $position, <span class="type">bool</span> $caseInsensitive)</span>
											<span class="method-desc">Replaces only the first occurrence of a substring</span>

											public <span class="type">bool</span> <span class="method">startsWith(<span class="type">string|StringHelper</span> $string, <span class="type">bool</span> $caseInsensitive)</span>
											<span class="method-desc">Returns whether the string starts with the given prefix</span>

											public <span class="type">string</span> <span class="method">subString(<span class="type">int</span> $start, <span class="type">?int</span> $length)</span>
											<span class="method-desc">Returns a substring starting at the given position</span>

											public <span class="type">void</span> <span class="method">toLower()</span>
											<span class="method-desc">Converts the string to lowercase in place</span>

											public <span class="type">void</span> <span class="method">toUpper()</span>
											<span class="method-desc">Converts the string to uppercase in place</span>

											public static <span class="type">StringHelper</span> <span class="method">join()</span>
											<span class="method-desc">Joins multiple strings with a glue string (variadic)</span>
										</p>
									</div>
								</section>

								<section id="stringhelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-cliscripthelper'])?>">CliScriptHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-filehelper-examples'])?>">FileHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-parameterhelper'])?>">ParameterHelper</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'io-sanitationhelper'])?>">SanitationHelper</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'component-pdo'])?>">PDO</a> component,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#stringhelper-brief">Overview</a>
									<a class="nav-link scrollto" href="#stringhelper-example">Example</a>
									<a class="nav-link scrollto" href="#stringhelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#stringhelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->