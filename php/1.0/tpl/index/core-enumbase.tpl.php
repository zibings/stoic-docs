<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'EnumBase', 'queryVars' => ['page' => 'core-enumbase'], 'active' => true]
	],
	'title' => 'Stoic:PHP - EnumBase'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'EnumBase'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="enumbase-concept" class="doc-section">
									<p>
										The <code>EnumBase</code> abstract class provides some general functionality for creating enumerated
										classes/values within PHP.
									</p>
								</section>

								<section id="enumbase-example">
									<h2 class="section-title">An Example</h2>

									<div class="section-block">
										<p>
											Creating and using a class of enumerated values is easy:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Utilities\EnumBase;

class Numbers extends EnumBase {
	const ONE = 1;
	const TWO = 2;
	const THREE = 3;
}

$num = new Numbers(Numbers::ONE);

if ($num->is(Numbers::TWO)) {
	echo("This is number 2");
} else {
	echo("The number is: {$num}");
}
</code></pre>
										</div>

										<p>
											Since enumerated values aren't supported in PHP, using a class with constants allows for type-safety.
											Usage is simple and both string and JSON serialization are built into the class.
										</p>
									</div>
								</section>

								<section id="enumbase-properties" class="doc-section">
									<h2 class="section-title">Properties</h2>

									<div class="section-block">
										<p class="properties">
											protected static <span class="type">array</span> <span class="prop">$constCache</span>
											<span class="prop-desc">Static internal cache of const lookups</span>

											protected <span class="type">string</span> <span class="prop">$name</span>
											<span class="prop-desc">Internal storage for name</span>

											protected <span class="type">integer</span> <span class="prop">$value</span>
											<span class="prop-desc">Internal storage for value</span>

											protected <span class="type">boolean</span> <span class="prop">$serializeAsName</span>
											<span class="prop-desc">Determines whether or not to serialize as name instead of value, defaults to true</span>
										</p>
									</div>
								</section>

								<section id="enumbase-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public static <span class="type">EnumBase</span> <span class="method">fromString(<span class="type">string</span> $string, <span class="type">boolean</span> $serializeAsName = true)</span>
											<span class="method-desc">Returns a new object of the called class using the constant name instead of the value for initialization</span>

											public static <span class="type">array</span> <span class="method">getConstList()</span>
											<span class="method-desc">Returns the const lookup for the called class</span>

											public static <span class="type">EnumBase</span> <span class="method">tryGetEnum(<span class="type">integer|EnumBase</span> $value, <span class="type">string</span> $className)</span>
											<span class="method-desc">Returns a new object of type <em>$className</em> either using an existing object or integer value (throw exception on failure)</span>

											public static <span class="type">boolean</span> <span class="method">validName(<span class="type">string</span> $name)</span>
											<span class="method-desc">Validates a name against the const lookup for the called class</span>

											public static <span class="type">boolean</span> <span class="method">validValue(<span class="type">integer</span> $value)</span>
											<span class="method-desc">Validates a value against the const lookup for the called class</span>

											public <span class="type">EnumBase</span> <span class="method">__construct(<span class="type">null|integer</span> $value = null, <span class="type">boolean</span> $serializeAsName = true)</span>
											<span class="method-desc">Instantiates a new object of the called class with option value/serialization settings</span>

											public <span class="type">string</span> <span class="method">__toString()</span>
											<span class="method-desc">Serializes the object to its string representation (name or value based on EnumBase::$serializeAsName)</span>

											public <span class="type">string</span> <span class="method">getName()</span>
											<span class="method-desc">Retrieves the set name for the object</span>

											public <span class="type">integer</span> <span class="method">getValue()</span>
											<span class="method-desc">Retrieves the set value for the object</span>

											public <span class="type">boolean</span> <span class="method">is(<span class="type">integer</span> $value)</span>
											<span class="method-desc">Determines if the set value for the object is the same as the supplied value</span>

											public <span class="type">boolean</span> <span class="method">isIn(<span class="type">integer</span> ...$values)</span>
											<span class="method-desc">Determines if the current value is equal to any of the supplied values</span>

											public <span class="type">string</span> <span class="method">jsonSerialize()</span>
											<span class="method-desc">Serializes the object to its string representation for a json_encode() call (name or value based on EnumBase::$serializeAsName)</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-io'])?>">I/O component</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#enumbase-concept">Concept</a>
									<a class="nav-link scrollto" href="#enumbase-example">Example</a>
									<a class="nav-link scrollto" href="#enumbase-properties">Properties</a>
									<a class="nav-link scrollto" href="#enumbase-methods">Methods</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->