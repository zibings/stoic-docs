<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'ReturnHelper', 'queryVars' => ['page' => 'core-returnhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - ReturnHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'ReturnHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="returnhelper-concept" class="doc-section">
									<p>
										The <code>ReturnHelper</code> class provides a mechanism for creating methods and functions that return more
										information than a simple scalar value to indicate success.  Think of the <code>ReturnHelper</code> as a more
										specialized <em>tuple</em>, in that it focuses on situations that have clear success/fail conditions.
									</p>
								</section>

								<section id="returnhelper-example">
									<h2 class="section-title">An Example</h2>

									<div class="section-block">
										<p>
											Given the following function:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
function myTest($inputA, $inputB) {
    if ($inputA === null || $inputB === null) {
        return false;
    }

    if ($inputA < $inputB) {
        return false;
    }

    return true;
}

if (myTest($varA, $varB) === false) {
    // deal with false
}
</code></pre>
										</div>

										<p>
											This function returns a boolean value indicating some kind of success, but it also returns the same
											<code>false</code> value as a result of the guard against null values.
										</p>

										<p>
											Returning <code>false</code> as a result of the guard may be sufficient, but sometimes you'd like to know
											why something has failed for reporting purposes.  One approach is, of course, to utilize exceptions, but
											that is not always appropriate.  As another alternative, you can use a <code>ReturnHelper</code>:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
function myTest($inputA, $inputB) {
    $ret = new ReturnHelper();

    if ($inputA === null || $inputB === null) {
        $ret->makeBad();
        $ret->addMessage("myTest() received a null valuie");

        return $ret;
    }

    if ($inputA < $inputB) {
        $ret->makeBad();
        $ret->addMessage("inputA is less than inputB");

        return $ret;
    }

    $ret->makeGood();

    return $ret;
}

$ret = myTest($varA, $varB);

if ($ret->isBad()) {
    // deal with false & messages here
}
</code></pre>
										</div>

										<p>
											Now you have the same simple pass/fail approach as before, but with more information if it's useful, and without
											having to wrap all of your code in a series of try/catches.
										</p>
									</div>
								</section>

								<section id="returnhelper-properties" class="doc-section">
									<h2 class="section-title">Properties</h2>

									<div class="section-block">
										<p class="properties">
											protected <span class="type">string[]</span> <span class="prop">$_messages</span>
											<span class="prop-desc">Collection of messages for this return</span>

											protected <span class="type">mixed[]</span> <span class="prop">$_results</span>
											<span class="prop-desc">Collection of results for this return</span>

											protected <span class="type">integer</span> <span class="prop">$_status</span>
											<span class="prop-desc">Integer value of this return's current status</span>
										</p>
									</div>
								</section>

								<section id="returnhelper-constants" class="doc-section">
									<h2 class="section-title">Constants</h2>

									<div class="section-block">
										<p class="properties">
											<span class="type">integer</span> <span class="prop">STATUS_BAD</span>
											<span class="prop-desc">Internal status for 'bad' returns</span>

											<span class="type">integer</span> <span class="prop">STATUS_GOOD</span>
											<span class="prop-desc">Internal status for 'good' returns</span>
										</p>
									</div>
								</section>

								<section id="returnhelper-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">ReturnHelper</span> <span class="method">__construct()</span>
											<span class="method-desc">Instantiates a new ReturnHelper object set to 'bad'</span>

											public <span class="type">void</span> <span class="method">addMessage(<span class="type">string</span> $message)</span>
											<span class="method-desc">Adds a message to the return</span>

											public <span class="type">void</span> <span class="method">addMessages(<span class="type">string[]</span> $messages)</span>
											<span class="method-desc">Adds an array of messages to the return</span>

											public <span class="type">void</span> <span class="method">addResult(<span class="type">mixed</span> $result)</span>
											<span class="method-desc">Adds a result value to the return</span>

											public <span class="type">void</span> <span class="method">addResults(<span class="type">mixed[]</span> $results)</span>
											<span class="method-desc">Adds an array of results to the return</span>

											public <span class="type">boolean</span> <span class="method">isBad()</span>
											<span class="method-desc">Returns whether or not the return's status is 'bad'</span>

											public <span class="type">boolean</span> <span class="method">isGood()</span>
											<span class="method-desc">Returns whether or not the return's status is 'good'</span>

											public <span class="type">string[]</span> <span class="method">getMessages()</span>
											<span class="method-desc">Returns the collection of messages for the return</span>

											public <span class="type">mixed[]</span> <span class="method">getResults()</span>
											<span class="method-desc">Returns the collection of results for the return</span>

											public <span class="type">boolean</span> <span class="method">hasMessages()</span>
											<span class="method-desc">Returns whether or not the return has messages</span>

											public <span class="type">boolean</span> <span class="method">hasResults()</span>
											<span class="method-desc">Returns whether or not the return has messages</span>

											public <span class="type">void</span> <span class="method">makeBad()</span>
											<span class="method-desc">Sets the return's status as 'bad'</span>

											public <span class="type">void</span> <span class="method">makeGood()</span>
											<span class="method-desc">Sets the return's status as 'good'</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-enumbase'])?>">EnumBase</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#returnhelper-concept">Concept</a>
									<a class="nav-link scrollto" href="#returnhelper-example">Example</a>
									<a class="nav-link scrollto" href="#returnhelper-properties">Properties</a>
									<a class="nav-link scrollto" href="#returnhelper-constants">Constants</a>
									<a class="nav-link scrollto" href="#returnhelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->