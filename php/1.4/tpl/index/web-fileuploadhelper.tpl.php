<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Web Component', 'queryVars' => ['page' => 'component-web'], 'active' => false],
		['text' => 'FileUploadHelper', 'queryVars' => ['page' => 'web-fileuploadhelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - FileUploadHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'FileUploadHelper'
]); ?>

					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="fileupload-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>FileUploadHelper</code> class normalizes PHP's <code>$_FILES</code> superglobal into
											a clean interface that returns <code>UploadedFile</code> objects.  PHP's file upload array structure
											can be inconsistent, especially with multiple file inputs &mdash; this helper handles those edge
											cases for you.
										</p>
									</div>
								</section>

								<section id="fileupload-usage" class="doc-section">
									<h2 class="section-title">Basic Usage</h2>

									<div class="section-block">
										<p>
											Access file uploads through the <code>Request</code> object:
										</p>

										<div class="code-block">
											<pre class="language-php"><code>
$request = $stoic->getRequest();

if ($request->hasFileUploads()) {
    $files = $request->getFiles();

    // Get files by input name
    $uploads = $files->getFile('avatar');

    foreach ($uploads as $file) {
        if ($file->isValid()) {
            move_uploaded_file(
                $file->tmpName,
                '/uploads/' . $file->name
            );
        }
    }
}
</code></pre>
										</div>
									</div>
								</section>

								<section id="fileupload-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">FileUploadHelper</span> <span class="method">__construct(<span class="type">?array</span> $files)</span>
											<span class="method-desc">Creates a new helper from the <code>$_FILES</code> superglobal</span>

											public <span class="type">int</span> <span class="method">count()</span>
											<span class="method-desc">Returns the number of file input groups</span>

											public <span class="type">UploadedFile[]</span> <span class="method">getFile(<span class="type">string</span> $key)</span>
											<span class="method-desc">Returns an array of UploadedFile objects for the given input name</span>
										</p>
									</div>
								</section>

								<section id="fileupload-uploadedfile" class="doc-section">
									<h2 class="section-title">UploadedFile</h2>

									<div class="section-block">
										<p>
											Each uploaded file is represented as an <code>UploadedFile</code> struct with the following
											properties:
										</p>

										<p class="properties">
											public <span class="type">int</span> <span class="prop">$error</span>
											<span class="prop-desc">PHP file upload error code</span>

											public <span class="type">string</span> <span class="prop">$name</span>
											<span class="prop-desc">Original filename as provided by the client</span>

											public <span class="type">int</span> <span class="prop">$size</span>
											<span class="prop-desc">File size in bytes</span>

											public <span class="type">string</span> <span class="prop">$tmpName</span>
											<span class="prop-desc">Temporary file path on the server</span>

											public <span class="type">string</span> <span class="prop">$type</span>
											<span class="prop-desc">MIME type as reported by the client</span>
										</p>

										<p class="methods">
											public <span class="type">string</span> <span class="method">getError()</span>
											<span class="method-desc">Returns a human-readable description of the upload error</span>

											public <span class="type">bool</span> <span class="method">isValid()</span>
											<span class="method-desc">Returns whether the file was uploaded successfully (no errors)</span>
										</p>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'web-htmlelementhelper'])?>">HtmlElementHelper</a>
											within the <em>Web</em> component, or visit the <a href="<?=$page->getAssetPath('~/php/1.4/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#fileupload-brief">Overview</a>
									<a class="nav-link scrollto" href="#fileupload-usage">Basic Usage</a>
									<a class="nav-link scrollto" href="#fileupload-methods">Methods</a>
									<a class="nav-link scrollto" href="#fileupload-uploadedfile">UploadedFile</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->
