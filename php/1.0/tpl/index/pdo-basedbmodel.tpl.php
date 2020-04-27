<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'PDO Component', 'queryVars' => ['page' => 'component-pdo'], 'active' => false],
		['text' => 'BaseDbModel', 'queryVars' => ['page' => 'pdo-basedbmodel'], 'active' => true]
	],
	'title' => 'Stoic:PHP - BaseDbModel'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'BaseDbModel'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="basedbmodel-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>BaseDbModel</code> class is a simplistic scaffolding system that was built to simplify/speed-up
											the creation of CRUD (Create - Read - Update - Delete) data models without extensive overhead or having to
											use something other than the SQL we all know and love/hate.
										</p>

										<p>
											The system provides the following features:
										</p>

										<ul>
											<li>Simple column/property association for CRUD functionality</li>
											<li>Ability to override CRUD guards and impose custom validation</li>
											<li>Automatic translation of database values to PHP values</li>
											<li>Query string generation</li>
										</ul>
									</div>
								</section>

								<section id="basedbmodel-crud" class="doc-section">
									<h2 class="section-title">CRUD Automation</h2>

									<div class="section-block">
										<p>
											In order to take advantage of the CRUD system within <code>BaseDbModel</code>, simply tell your model
											during initialization (inside of the <code>__setupModel()</code> method) what the class properties relate
											to and in what table:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Pdo\BaseDbModel;
use Stoic\Pdo\BaseDbTypes;

class UserModel extends BaseDbModel {
	/**
	 * Unique identifier for user.
	 *
	 * @var integer
	 */
	public $id;
	/**
	 * User's primary email address.
	 *
	 * @var string
	 */
	public $email;
	/**
	 * The date and time the user joined the site.
	 *
	 * @var DateTimeInterface
	 */
	public $dateJoined;


	protected function __setupModel() {
		$this->setTableName('User');
		$this->setColumn('id', 'ID', BaseDbTypes::INTEGER, true, false, false, false, true);
		$this->setColumn('email', 'Email', BaseDbTypes::STRING, false, true, true);
		$this->setColumn('dateJoined', 'DateJoined', BaseDbTypes::DATETIME, false, true, false);

		return;
	}
}
</code></pre>
										</div>

										<p>
											That's it!  Now any instance of the <code>UserModel</code> class comes complete with (among many other
											things) a full assortment of CRUD methods.
										</p>

										<p>
											If one of your CRUD methods, let's say 'create', needs a bit more validation before it attempts to do its
											thing, adding that validation is very easy.  All you need to is add an override for the
											<code>__canCreate()</code> method:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
protected function __canCreate() {
	if ($this->id > 0 || empty($this->email)) {
		return false;
	}

	$this->dateJoined = new DateTime('now', new DateTimeZone('UTC'));

	return true;
}
</code></pre>
										</div>

										<p>
											Similar methods exist for read, update, and delete.
										</p>
									</div>
								</section>

								<section id="basedbmodel-querygen" class="doc-section">
									<h2 class="section-title">Query Generation</h2>

									<div class="section-block">
										<p>
											Another utility method is the <code>BaseDbModel::generateClassQuery()</code> method.  This allows devs to get
											dynamically generated SQL strings for their model classes, useful if you find yourself using the models for
											things like repository methods.  This also decreases the amount of SQL you'll have to change by hand when
											you update your schema.
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Pdo\BaseDbQueryTypes;

// We'll assume we have a database connection ready to use
$user = new UserModel($db);
$sql = $user->generateClassQuery(BaseDbQueryTypes::SELECT);

// $sql contains the following string:
//    "SELECT ID, Email, DateJoined FROM User WHERE ID = :id"

$sql = $user->generateClassQuery(BaseDbQueryTypes::SELECT, false);

// $sql will now contain the following string:
//    "SELECT ID, Email, DateJoined FROM User"
</code></pre>
										</div>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'pdo-pdohelper'])?>">PdoHelper</a> class
											within the <em>PDO</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#basedbmodel-brief">BaseDbModel</a>
									<a class="nav-link scrollto" href="#basedbmodel-crud">CRUD</a>
									<a class="nav-link scrollto" href="#basedbmodel-querygen">Query Gen</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->