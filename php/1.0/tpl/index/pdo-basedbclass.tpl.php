<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'PDO Component', 'queryVars' => ['page' => 'component-pdo'], 'active' => false],
		['text' => 'BaseDbClass', 'queryVars' => ['page' => 'pdo-basedbclass'], 'active' => true]
	],
	'title' => 'Stoic:PHP - BaseDbClass'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'BaseDbClass'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="basedbclass-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>BaseDbClass</code> is an abstract class that offers three major pieces of functionality:
										</p>

										<ol>
											<li>
												Enforces the requirement that any derived classes must have PDO and Logger objects injected into them at
												instantiation
											</li>
											<li>
												An optional initialization method devs can override to perform their init steps knowing they have the PDO
												&amp; Logger dependencies
											</li>
											<li>
												A utility method, <code>tryPdoExcept()</code>, which takes care of catching and logging
												<em>PDOException's</em> that happen while executing queries against the <code>PDO</code> dependency
											</li>
										</ol>
									</div>
								</section>

								<section id="basedbclass-init" class="doc-section">
									<h2 class="section-title">Initialization</h2>

									<div class="section-block">
										<p>
											The <code>BaseDbClass::__initialize()</code> method can be overridden by child classes to execute code as
											though it were being done in a constructor, but will only be called if the <code>BaseDbClass</code>
											constructor has received appropriate <code>PDO</code> and <code>Logger</code> dependencies:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
using Stoic\Pdo\BaseDbClass;

class MyDbClass extends BaseDbClass {
	public $isInitialized = false;


	protected function __initialize() : void {
		// If we are here, we have $this->db and $this->log
		$this->isInitialized = true;

		$this->log->info("MyDbClass was initialized successfully");

		return;
	}
}
</code></pre>
										</div>
									</div>
								</section>

								<section id="basedbclass-trypdoexcept" class="doc-section">
									<h2 class="section-title">tryPdoExcept</h2>

									<div class="section-block">
										<p>
											Whether using the normal <code>PDO</code> OR <code>PdoHelper</code> classes, a common set of code is the
											following:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
public function getAllSomethings() {
	// some stuff to prepare a query....

	try {
		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':var', $var, \PDO::PARAM_STR);
		$stmt->execute();

		// gather results, etc
	} catch (\PDOException $ex) {
		$this->log->error("Issue executing query: {ERROR}", ['ERROR' => $ex]);
	}

	return $ret;
}
</code></pre>
										</div>
									</div>

									<p>
										Since it's very likely that this code will be used periodically within a codebase, the
										<code>BaseDbClass</code> abstract provides a utility method, <code>tryPdoExcept()</code>.  This allows us to
										refactor the above into this:
									</p>

									<div class="code-block">
										<pre class="language-php"><code>
public function getAllSomethings() {
	// some stuff to prepare a query

	$this->tryPdoExcept(function () use ($sql, &$ret) {
		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':var', $var, \PDO::PARAM_STR);
		$stmt->execute();

		// gather results, etc
	}, "Issue executing query");

	return $ret;
}
</code></pre>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-basedbmodel'])?>">BaseDbModel</a>
											within the <em>PDO</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#basedbclass-brief">BaseDbClass</a>
									<a class="nav-link scrollto" href="#basedbclass-init">Initialization</a>
									<a class="nav-link scrollto" href="#basedbclass-trypdoexcept">tryPdoExcept</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->