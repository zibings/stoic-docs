<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'PaginateHelper', 'queryVars' => ['page' => 'web-paginatehelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - PaginateHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'PaginateHelper'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="paginate-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>PaginateHelper</code> class performs common pagination math operations.  Given a current page,
											total number of entries, and entries per page, it calculates offsets, total pages, and provides a
											convenient method for generating page number ranges.
										</p>
									</div>
								</section>

								<section id="paginate-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>
use Stoic\Web\PaginateHelper;

$paginator = new PaginateHelper(
    currentPage:    3,
    totalEntries:   150,
    entriesPerPage: 10
);

echo $paginator->totalPages;   // 15
echo $paginator->entryOffset;  // 20
echo $paginator->nextPage;     // 4
echo $paginator->lastPage;     // 2

// Get a range of page numbers around the current page
$pages = $paginator->getPages(5);
// [1, 2, 3, 4, 5]
</code></pre>
										</div>
									</div>
								</section>

								<section id="paginate-properties" class="doc-section">
									<h2 class="section-title">Properties</h2>

									<div class="section-block">
										<p class="properties">
											public <span class="type">int</span> <span class="prop">$currentPage</span>
											<span class="prop-desc">The current page number</span>

											public <span class="type">int</span> <span class="prop">$entriesPerPage</span>
											<span class="prop-desc">Number of entries displayed per page</span>

											public <span class="type">int</span> <span class="prop">$entryOffset</span>
											<span class="prop-desc">Calculated offset for database queries (e.g. LIMIT/OFFSET)</span>

											public <span class="type">int</span> <span class="prop">$lastPage</span>
											<span class="prop-desc">The previous page number (current - 1, minimum 1)</span>

											public <span class="type">int</span> <span class="prop">$nextPage</span>
											<span class="prop-desc">The next page number (current + 1, capped at total pages)</span>

											public <span class="type">int</span> <span class="prop">$totalEntries</span>
											<span class="prop-desc">The total number of entries across all pages</span>

											public <span class="type">int</span> <span class="prop">$totalPages</span>
											<span class="prop-desc">The total number of pages calculated from entries and entries per page</span>
										</p>
									</div>
								</section>

								<section id="paginate-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">PaginateHelper</span> <span class="method">__construct(<span class="type">int</span> $currentPage, <span class="type">int</span> $totalEntries, <span class="type">int</span> $entriesPerPage)</span>
											<span class="method-desc">Creates a new paginator and calculates all pagination values</span>

											public <span class="type">int[]</span> <span class="method">getPages(<span class="type">int</span> $numPages)</span>
											<span class="method-desc">Returns an array of page numbers centered around the current page, useful for generating page number links</span>
										</p>
									</div>
								</section>

								<section id="paginate-example" class="doc-section">
									<h2 class="section-title">Database Query Example</h2>

									<div class="section-block">
										<div class="code-block">
											<pre class="language-php"><code>
$paginator = new PaginateHelper(
    $request->getGet()->getInt('page', 1),
    $totalRecordCount,
    25
);

$stmt = $db->prepare(
    "SELECT * FROM `Users` LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', $paginator->entriesPerPage, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $paginator->entryOffset, \PDO::PARAM_INT);
$stmt->execute();
</code></pre>
										</div>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'web-api'])?>">API Helpers</a>
											within the <em>Web</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#paginate-brief">Overview</a>
									<a class="nav-link scrollto" href="#paginate-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#paginate-properties">Properties</a>
									<a class="nav-link scrollto" href="#paginate-methods">Methods</a>
									<a class="nav-link scrollto" href="#paginate-example">DB Example</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
