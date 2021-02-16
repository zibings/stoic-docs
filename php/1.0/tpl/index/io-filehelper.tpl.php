<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'I/O Component', 'queryVars' => ['page' => 'component-io'], 'active' => false],
		['text' => 'FileHelper', 'queryVars' => ['page' => 'io-filehelper'], 'active' => true]
	],
	'title' => 'Stoic:PHP - FileHelper'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'FileHelper'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="filehelper-brief" class="doc-section">
									<div class="section-block">
										<p>
											The <code>FileHelper</code> class provides methods for common filesystem operations.
										</p>
									</div>
								</section>

								<section id="filehelper-filehelperglobs" class="doc-section">
									<h2 class="section-title">FileHelperGlobs Enum</h2>

									<div class="section-block">
										<p class="properties">
											<span class="type">integer</span> <span class="prop">GLOB_ALL</span>
											<span class="prop-desc">Instructs helper to return BOTH files and folders</span>

											<span class="type">integer</span> <span class="prop">GLOB_FOLDERS</span>
											<span class="prop-desc">Instructs helper to return ONLY folders</span>

											<span class="type">integer</span> <span class="prop">GLOB_FILES</span>
											<span class="prop-desc">Instructs helper to return ONLY files</span>
										</p>
									</div>
								</section>

								<section id="filehelper-properties" class="doc-section">
									<h2 class="section-title">Properties</h2>

									<div class="section-block">
										<p class="properties">
											protected <span class="type">string</span> <span class="prop">$relativePath</span>
											<span class="prop-desc">Relative path for this <em>FileHelper</em> instance, replaces '~/' in path strings</span>
										</p>
									</div>
								</section>

								<section id="filehelper-methods" class="doc-section">
									<h2 class="section-title">Methods</h2>

									<div class="section-block">
										<p class="methods">
											public <span class="type">FileHelper</span> <span class="method">__construct(<span class="type">string</span> $relativePath[, <span class="type">string[]</span> $preIncludes = null])</span>
											<span class="method-desc">Instantiates a new <em>FileHelper</em> object, optionally registering files already included in the application</span>

											public <span class="type">void</span> <span class="method">copyFile(<span class="type">string</span> $source, <span class="type">string</span> $destination)</span>
											<span class="method-desc">Copies a single file between paths</span>

											public <span class="type">void</span> <span class="method">copyFolder(<span class="type">string</span> $source, <span class="type">string</span> $destination)</span>
											<span class="method-desc">Copies an entire folder between paths</span>

											public <span class="type">bool</span> <span class="method">fileExists(<span class="type">string</span> $path)</span>
											<span class="method-desc">Determines if a file exists</span>

											public <span class="type">bool</span> <span class="method">folderExists(<span class="type">string</span> $path)</span>
											<span class="method-desc">Determines if a folder exists</span>

											public <span class="type">string</span> <span class="method">getContents(<span class="type">string</span> $path)</span>
											<span class="method-desc">Retrieves contents of the given file</span>

											public <span class="type">null|array</span> <span class="method">getFolderFiles(<span class="type">string</span> $path)</span>
											<span class="method-desc">Retrieves all file names in a folder non-recursively</span>

											public <span class="type">null|array</span> <span class="method">getFolderFolders(<span class="type">string</span> $path)</span>
											<span class="method-desc">Retrieves all folder names in a folder non-recursively</span>

											public <span class="type">null|array</span> <span class="method">getFolderItems(<span class="type">string</span> $path)</span>
											<span class="method-desc">Retrieves all item names in a folder non-recursively</span>

											public <span class="type">string</span> <span class="method">getRelativePath()</span>
											<span class="method-desc">Retrieves the stored relative path</span>

											protected <span class="type">null|array</span> <span class="method">globFolder(<span class="type">string</span> $path, <span class="type">integer</span> $globType[, <span class="type">bool</span> $recursive = false])</span>
											<span class="method-desc">Internal method to traverse a folder's contents and return the requested item types</span>

											public <span class="type">string</span> <span class="method">load(<span class="type">string</span> $path[, <span class="type">bool</span> $allowReload = false])</span>
											<span class="method-desc">Attempts to load file at given path, optionally allowing for file to be loaded more than once</span>

											public <span class="type">string[]</span> <span class="method">loadGroup(<span class="type">string[]</span> $paths[, <span class="type">bool</span> $allowReload = false])</span>
											<span class="method-desc">Attempts to load multiple files, optionally allowing for files to be loaded more than once</span>

											public <span class="type">bool</span> <span class="method">makeFolder(<span class="type">string</span> $path[, <span class="type">integer</span> $mode = 0777[, <span class="type">bool</span> $recursive = false]])</span>
											<span class="method-desc">Attempts to create a folder if it doesn't exist, optional permission mode and recursive toggle</span>

											public <span class="type">string</span> <span class="method">pathJoin(<span class="type">string</span> $start[, <span class="type">string</span> ...$parts)</span>
											<span class="method-desc">Joins paths together using UNIX directory separator</span>

											protected <span class="type">string</span> <span class="method">processRoot(<span class="type">string</span> $path)</span>
											<span class="method-desc">Internal method to replace '~/' token in a path string</span>

											public <span class="type">mixed</span> <span class="method">putContents(<span class="type">string</span> $path, <span class="type">mixed</span> $data[, <span class="type">integer</span> $flags = 0[, <span class="type">resource</span> $context = null]])</span>
											<span class="method-desc">Attempts to write data to the file given by the path</span>

											protected <span class="type">void</span> <span class="method">recursiveCopy(<span class="type">string</span> $source, <span class="type">string</span> $dest)</span>
											<span class="method-desc">Internal method to recursively copy items in a folder</span>

											public <span class="type">bool</span> <span class="method">removeFile(<span class="type">string</span> $path)</span>
											<span class="method-desc">Deleted file at the given path</span>

											public <span class="type">bool</span> <span class="method">removeFolder(<span class="type">string</span> $path)</span>
											<span class="method-desc">Deletes folder at the given path non-recursively</span>

											public <span class="type">bool</span> <span class="method">touchFile(<span class="type">string</span> $path[, <span class="type">?integer</span> $time = null[, <span class="type">?integer</span> $atime = null]])</span>
											<span class="method-desc">Sets access and modification time of file at given path</span>
										</p>
									</div>
								</section>

								<section id="filehelper-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-consolehelper'])?>">ConsoleHelper</a></li>
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
											Continue to read about the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'io-logconsoleappender'])?>">LogConsoleAppender</a> class,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#filehelper-filehelperglobs">FileHelperGlobs</a>
									<a class="nav-link scrollto" href="#filehelper-properties">Properties</a>
									<a class="nav-link scrollto" href="#filehelper-methods">Methods</a>
									<a class="nav-link scrollto" href="#filehelper-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->