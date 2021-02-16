<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'PDO Component', 'queryVars' => ['page' => 'component-pdo'], 'active' => false],
		['text' => 'PdoHelper', 'queryVars' => ['page' => 'pdo-pdohelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - PdoHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'PdoHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="pdohelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>PdoHelper</code> class is a wrapper for PHP's <code>PDO</code> class that adds several pieces of
											functionality:
										</p>

										<ul>
											<li>Better error logging</li>
											<li>Query logging/meta information</li>
											<li>Better connected/disconnected display</li>
											<li>Stored queries</li>
										</ul>

										<p>
											The logging/meta information improvements are in place just by using the <code>PdoHelper</code> class.  In
											order to view them you can use the following methods:
										</p>

										<p class="methods">
											public <span class="type">PdoDrivers</span> <span class="method">getDriver()</span>
											<span class="method-desc">Returns the currently configured PDO driver</span>

											public <span class="type">PdoError[]</span> <span class="method">getErrors()</span>
											<span class="method-desc">Returns all previous PDOExceptions with their triggering queries</span>

											public <span class="type">PdoQuery[]</span> <span class="method">getQueries()</span>
											<span class="method-desc">Returns all queries that have been run through the helper</span>

											public <span class="type">integer</span> <span class="method">getQueryCount()</span>
											<span class="method-desc">Returns the number of queries that have been run through the helper</span>
										</p>
									</div>
								</section>

								<section id="pdohelper-stored" class="doc-section">
									<h2 class="section-title">Stored Queries</h2>

									<div class="section-block">
										<p>
											Stored queries are a way to re-use queries (per database engine) without any extra code.  As a quick
											example, let's create some for both MySQL and MSSQL:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
using Stoic\Pdo\PdoDrivers;
using Stoic\Pdo\PdoHelper;

PdoHelper::storeQuery(
	PdoDrivers::PDO_MSSQL,
	'sel-user-email',
	"SELECT [Email] FROM [dbo].[User] WHERE [ID] = :id",
	[':id' => \PDO::PARAM_INT]);
PdoHelper::storeQuery(
	PdoDrivers::PDO_MYSQL,
	'sel-user-email',
	"SELECT `Email` FROM `User` WHERE `ID` = :id",
	[':id' => \PDO::PARAM_INT]);

$db = new PdoHelper('mysql:dbname=testdb;host=127.0.0.1');
$stmt = $db->prepareStored('sel-user-email', [':id' => 1]); // calls up the MySQL version
$stmt->execute();                                           // because of the connection's driver
</code></pre>
										</div>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'component-web'])?>">web</a> component,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#pdohelper-brief">BaseDbModel</a>
									<a class="nav-link scrollto" href="#pdohelper-crud">CRUD</a>
									<a class="nav-link scrollto" href="#pdohelper-querygen">Query Gen</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->