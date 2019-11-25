<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Quick Start', 'queryVars' => ['page' => 'quick-start'], 'active' => true]
	]
]); ?>

					<div id="doc-header" class="doc-header text-center">
						<h1 class="doc-title"><i class="icon fa fa-paper-plane"></i> Quick Start</h1>
						<div class="meta btn-group">
							<div class="pt-1 mr-4 my-auto" style="height: 32px">
								<i class="far fa-clock"></i> Last updated: <?=date('F jS, Y', filemtime(__FILE__))?>
							</div>

							<div class="pt-1 mr-1 my-auto" style="height: 32px">
								Release:
							</div>

							<div class="h-100">
								<div class="dropdown">
									<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="versionDropdownButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										v1.0
									</button>
								</div>
							</div>
						</div>
					</div><!--//doc-header-->
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="installation-section" class="doc-section">
									<h2 class="section-title">Installation</h2>

									<div class="section-block">
										<p>
											Installation can be done using <a href="//getcomposer.org" target="_blank">Composer</a>/<a href="//packagist.org" target="_blank">Packagist</a>.  Simply enter the following commands in your shell
											and all of the Stoic components will be installed for a website to be built:
										</p>

										<div class="code-block">
											<h6>Require 'Web' component with Composer:</h6>

											<p>
												<code>composer require stoic/web</code>
											</p>
										</div>

										<div class="code-block">
											<h6>Initialize the default Stoic directories:</h6>

											<p>
												<code>vendor/bin/stoic-create</code>
											</p>
										</div>
									</div><!--//section-block-->
								</section><!--//doc-section-->

								<section id="folder-structure" class="doc-section">
									<h2 class="section-title">Folder Structure</h2>

									<div class="section-block">
										<p>
											Stoic will have created the following folder structure for your project:
										</p>

										<div class="code-block">
											<code>
												~/inc/classes/<br />
												~/inc/repositories/<br />
												~/inc/utilities/<br />
												~/index.php<br />
												~/siteSettings.json
											</code>
										</div>
									</div>

									<div id="classes-folder" class="section-block">
										<h6>Classes Folder</h6>

										<p>
											This folder should contain any class definitions.  Any files with the extension <code>.cls.php</code> in this folder will be automatically included when Stoic is loaded.
										</p>

										<p>
											For more information on classes, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-classes'])?>">here</a>.
										</p>
									</div>

									<div id="repositories-folder" class="section-block">
										<h6>Repositories Folder</h6>

										<p>
											This folder should contain any repository classes, which normally provide methods for returning data in the form of classes.
											Any files with the extension <code>.rpo.php</code> in this folder will be automatically included when Stoic is loaded.
										</p>

										<p>
											For more information on repositories, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-repositories'])?>">here</a>.
										</p>
									</div>

									<div id="utilities-folder" class="section-block">
										<h6>Utilities Folder</h6>

										<p>
											This folder should contain any files that provide utility functionality for your site.
											Any files with the extension <code>.utl.php</code> in this folder will be automatically included when Stoic is loaded.
										</p>

										<p>
											For more information on utility files, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-utilities'])?>">here</a>.
										</p>
									</div>

									<div id="index-file" class="section-block">
										<h6>Index File</h6>

										<p>
											This file is a quick example of how Stoic can be loaded and used as an entry-point for a website.
										</p>

										<p>
											For more information on entry-points and Stoic, click <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'concepts-entry-points'])?>">here</a>.
										</p>
									</div>

									<div id="settings-file" class="section-block">
										<h6>Settings File</h6>

										<p>
											The settings file <code>siteSettings.json</code> contains the default configuration values for the site.
											The settings file is setup using the <a href="//github.com/AndyM84/config-migration" target="_blank">Config Migration</a> library.
										</p>
									</div>
								</section>

								<section id="example" class="doc-section">
									<h2 class="section-title"><!-- TODO: Wtf do we do as an example here? --></h2>

									<div class="section-block">
										<!-- Still need to know what to do -->
									</div>
								</section>
                            
								<section id="code-section" class="doc-section">
									<h2 class="section-title">Code</h2>

									<div class="section-block">
										<p>
											<a href="https://prismjs.com/" target="_blank">PrismJS</a> is used as the syntax highlighter here. You can <a href="https://prismjs.com/download.html" target="_blank">build your own version</a> via their website should you need to.
										</p>
									</div><!--//section-block-->

									<div id="html" class="section-block">
										<div class="callout-block callout-success">
											<div class="icon-holder">
												<i class="fas fa-thumbs-up"></i>
											</div><!--//icon-holder-->

											<div class="content">
												<h4 class="callout-title">Useful Tip:</h4>
												<p>You can use this online <a href="https://mothereff.in/html-entities" target="_blank">HTML entity encoder/decoder</a> to generate your code examples.</p>
											</div><!--//content-->
										</div>

										<div class="code-block">
											<h6>HTML Code Example</h6>

											<pre><code class="language-markup">&lt;!DOCTYPE html&gt; 
&lt;html lang=&quot;en&quot;&gt; 
...
&lt;div class=&quot;jumbotron&quot;&gt; 
&lt;h1&gt;Hello, world!&lt;/h1&gt; 
&lt;p&gt;...&lt;/p&gt; 
&lt;p&gt;&lt;a class=&quot;btn btn-primary btn-lg&quot; href=&quot;#&quot; role=&quot;button&quot;&gt;Learn more&lt;/a&gt;&lt;/p&gt; 
&lt;/div&gt;
&lt;div class=&quot;jumbotron&quot;&gt; 
&lt;h1&gt;Hello, world!&lt;/h1&gt; 
&lt;p&gt;...&lt;/p&gt; 
&lt;p&gt;&lt;a class=&quot;btn btn-primary btn-lg&quot; href=&quot;#&quot; role=&quot;button&quot;&gt;Learn more&lt;/a&gt;&lt;/p&gt; 
&lt;/div&gt;
...
&lt;/html&gt;</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="css" class="section-block">
										<div class="code-block">
											<h6>CSS Code Example</h6>

											<pre><code class="language-css">/* ======= Base Styling ======= */
body {
font-family: 'Open Sans', arial, sans-serif; 
color: #333; 
font-size: 16px; 
-webkit-font-smoothing: antialiased; 
-moz-osx-font-smoothing: grayscale; 
}</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="sass" class="section-block">
										<div class="code-block">
											<h6>SCSS Code Example</h6>

											<pre><code class="language-css">@mixin transform($property) {
-webkit-transform: $property;
-ms-transform: $property;
transform: $property;
}

.box { @include transform(rotate(30deg)); }</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="less" class="section-block">
										<div class="code-block">
											<h6>LESS Code Example</h6>

											<pre><code class="language-css">@base: #f04615;
@width: 0.5;

.class {
width: percentage(@width); // returns &#x60;50%&#x60;
color: saturate(@base, 5%);
background-color: spin(lighten(@base, 25%), 8);
}</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="javascript" class="section-block">
										<div class="code-block">
											<h6>JavaScript Code Example</h6>

											<pre><code class="language-javascript">&lt;script&gt; 
function myFunction(a, b) { 
return a * b; 
} 
    
document.getElementById(&quot;demo&quot;).innerHTML = myFunction(4, 3); 
&lt;/script&gt;</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="python" class="section-block">
										<div class="code-block">
											<h6>Python Code Example</h6>

											<pre><code class="language-python">&gt;&gt;&gt; x = int(input(&quot;
Please enter an integer: &quot;)) Please enter an integer: 42 
&gt;&gt;&gt; if x &lt; 0: 
... x = 0 
... print('Negative changed to zero') 
... elif x == 0: 
... print('Zero') 
... elif x == 1: 
... print('Single') 
... else: 
... print('More') 
... More</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="php" class="section-block">
										<div class="code-block">
											<h6>PHP Code Example</h6>

											<pre><code class="language-php">&lt;?php 
$txt = &quot;Hello world!&quot;; 
$x = 5; 
$y = 10.5; 

echo $txt; 
echo &quot;&lt;br&gt;&quot;; 
echo $x; 
echo &quot;&lt;br&gt;&quot;; 
echo $y; 
?&gt;</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->

									<div id="handlebars" class="section-block">
										<div class="code-block">
											<h6>Handlebars Code Example</h6>

											<pre><code class="language-handlebars">Handlebars.registerHelper('list', function(items, options) { 
var out = &quot;&lt;ul&gt;&quot;; 
  
for(var i=0, l=items.length; i&lt;l; i++) { 
out = out + &quot;&lt;li&gt;&quot; + options.fn(items[i]) + &quot;&lt;/li&gt;&quot;; 
} 
  
return out + &quot;&lt;/ul&gt;&quot;; 
});</code></pre>
										</div><!--//code-block-->

										<div class="code-block">
											<h6>Git Code Example</h6>

											<pre><code class="language-git">$ git add Documentation.txt</code></pre>
										</div><!--//code-block-->
									</div><!--//section-block-->
								</section><!--//doc-section-->

								<section id="callouts-section" class="doc-section">
									<h2 class="section-title">Callouts</h2>

									<div class="section-block">
										<p>
											Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. 
										</p>
									</div>

									<div class="section-block">
										<div class="callout-block callout-info">
											<div class="icon-holder">
												<i class="fas fa-info-circle"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Aenean imperdiet</h4>
												<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium <code>&lt;code&gt;</code> , Nemo enim ipsam voluptatem quia voluptas <a href="#">link example</a> sit aspernatur aut odit aut fugit.</p>
											</div><!--//content-->
										</div><!--//callout-block-->
                                    
										<div class="callout-block callout-warning">
											<div class="icon-holder">
												<i class="fas fa-bug"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Morbi posuere</h4>
												<p>Nunc hendrerit odio quis dignissim efficitur. Proin ut finibus libero. Morbi posuere fringilla felis eget sagittis. Fusce sem orci, cursus in tortor <a href="#">link example</a> tellus vel diam viverra elementum.</p>
											</div><!--//content-->
										</div><!--//callout-block-->
                                    
										<div class="callout-block callout-success">
											<div class="icon-holder">
												<i class="fas fa-thumbs-up"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Lorem ipsum dolor sit amet</h4>
												<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. <a href="#">Link example</a> aenean commodo ligula eget dolor.</p>
											</div><!--//content-->
										</div><!--//callout-block-->
                                    
										<div class="callout-block callout-danger">
											<div class="icon-holder">
												<i class="fas fa-exclamation-triangle"></i>
											</div><!--//icon-holder-->
											<div class="content">
												<h4 class="callout-title">Interdum et malesuada</h4>
												<p>Morbi eget interdum sapien. Donec sed turpis sed nulla lacinia accumsan vitae ut tellus. Aenean vestibulum <a href="#">Link example</a> maximus ipsum vel dignissim. Morbi ornare elit sit amet massa feugiat, viverra dictum ipsum pellentesque. </p>
											</div><!--//content-->
										</div><!--//callout-block-->
									</div>
								</section><!--//doc-section-->

								<section id="tables-section" class="doc-section">
									<h2 class="section-title">Tables</h2>

									<div class="section-block">
										<p>
											Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis.
										</p>
									</div>

									<div class="section-block">
										<h6>Basic Table</h6>

										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th>#</th>
														<th>First Name</th>
														<th>Last Name</th>
														<th>Username</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<th scope="row">1</th>
														<td>Mark</td>
														<td>Otto</td>
														<td>@mdo</td>
													</tr>
													<tr>
														<th scope="row">2</th>
														<td>Jacob</td>
														<td>Thornton</td>
														<td>@fat</td>
													</tr>
													<tr>
														<th scope="row">3</th>
														<td>Larry</td>
														<td>the Bird</td>
														<td>@twitter</td>
													</tr>
												</tbody>
											</table>
										</div><!--//table-responsive-->

										<h6>Striped Table</h6>

										<div class="table-responsive">
											<table class="table table-striped">
												<thead>
													<tr>
														<th>#</th>
														<th>First Name</th>
														<th>Last Name</th>
														<th>Username</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<th scope="row">1</th>
														<td>Mark</td>
														<td>Otto</td>
														<td>@mdo</td>
													</tr>
													<tr>
														<th scope="row">2</th>
														<td>Jacob</td>
														<td>Thornton</td>
														<td>@fat</td>
													</tr>
													<tr>
														<th scope="row">3</th>
														<td>Larry</td>
														<td>the Bird</td>
														<td>@twitter</td>
													</tr>
												</tbody>
											</table>
										</div><!--//table-responsive-->

										<h6>Bordered Table</h6>

										<div class="table-responsive">
											<table class="table table-bordered">
												<thead>
													<tr>
														<th>#</th>
														<th>First Name</th>
														<th>Last Name</th>
														<th>Username</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<th scope="row">1</th>
														<td>Mark</td>
														<td>Otto</td>
														<td>@mdo</td>
													</tr>
													<tr>
														<th scope="row">2</th>
														<td>Jacob</td>
														<td>Thornton</td>
														<td>@fat</td>
													</tr>
													<tr>
														<th scope="row">3</th>
														<td>Larry</td>
														<td>the Bird</td>
														<td>@twitter</td>
													</tr>
												</tbody>
											</table>
										</div><!--//table-responsive-->
									</div><!--//section-block-->
								</section><!--//doc-section-->

								<section id="buttons-section" class="doc-section">
									<h2 class="section-title">Buttons</h2>

									<div class="section-block">
										<p>
											Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nec imperdiet turpis. Curabitur aliquet pulvinar ultrices. Etiam at posuere leo. Proin ultrices ex et dapibus feugiat <a href="#">link example</a> aenean purus leo, faucibus at elit vel, aliquet scelerisque dui. Etiam quis elit euismod, imperdiet augue sit amet, imperdiet odio. Aenean sem erat, hendrerit  eu gravida id, dignissim ut ante. Nam consequat porttitor libero euismod congue. 
										</p>
										<div class="row">
											<div class="col-md-6 col-12">
												<h6>Basic Buttons</h6>

												<ul class="list list-unstyled">
													<li><a href="#" class="btn btn-primary">Primary Button</a></li>
													<li><a href="#" class="btn btn-green">Green Button</a></li>
													<li><a href="#" class="btn btn-blue">Blue Button</a></li>
													<li><a href="#" class="btn btn-orange">Orange Button</a></li>
													<li><a href="#" class="btn btn-red">Red Button</a></li>
												</ul>
											</div>

											<div class="col-md-6 col-12">
												<h6>CTA Buttons</h6>

												<ul class="list list-unstyled">
													<li><a href="#" class="btn btn-primary btn-cta"><i class="fas fa-download"></i> Download Now</a></li>
													<li><a href="#" class="btn btn-green btn-cta"><i class="fas fa-code-branch"></i> Fork Now</a></li>
													<li><a href="#" class="btn btn-blue btn-cta"><i class="fas fa-play-circle"></i> Find Out Now</a></li>
													<li><a href="#" class="btn btn-orange btn-cta"><i class="fas fa-bug"></i> Report Bugs</a></li>
													<li><a href="#" class="btn btn-red btn-cta"><i class="fas fa-exclamation-circle"></i> Submit Issues</a></li>
												</ul>
											</div>
										</div><!--//row-->
									</div><!--//section-block-->
								</section><!--//doc-section-->

								<section id="icons-section" class="doc-section">
									<h2 class="section-title">Icons</h2>

									<div class="section-block">
										<p>
											Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nec imperdiet turpis. Curabitur aliquet pulvinar ultrices. Etiam at posuere leo. Proin ultrices ex et dapibus feugiat <a href="#">link example</a> aenean purus leo, faucibus at elit vel, aliquet scelerisque dui. Etiam quis elit euismod, imperdiet augue sit amet, imperdiet odio. Aenean sem erat, hendrerit  eu gravida id, dignissim ut ante. Nam consequat porttitor libero euismod congue. 
										</p>
									</div><!--//section-block-->

									<div class="section-block">
										<h6>Elegant Icon Font</h6>

										<a href="https://www.elegantthemes.com/blog/resources/elegant-icon-font" target="_blank"><img class="img-fluid" src="<?=$page->getAssetPath('~/assets/images/demo/elegant-icon-font.jpg')?>" alt="elegant icons" /></a>
									</div><!--//section-block-->

									<div class="section-block">
										<h6>FontAwesome Icon Font</h6>

										<a href="https://fortawesome.github.io/Font-Awesome/" target="_blank"><img class="img-fluid" src="<?=$page->getAssetPath('~/assets/images/demo/fontawesome-icons.png')?>" alt="fontawesome" /></a>
									</div><!--//section-block-->
								</section><!--//doc-section-->
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#installation-section">Installation</a>
									<a class="nav-link scrollto" href="#folder-structure">Folder Structure</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#classes-folder">Classes</a>
										<a class="nav-link scrollto" href="#repositories-folder">Repositories</a>
										<a class="nav-link scrollto" href="#utilities-folder">Utilities</a>
										<a class="nav-link scrollto" href="#index-file">Index</a>
										<a class="nav-link scrollto" href="#settings-file">Settings</a>
									</nav>

									<a class="nav-link scrollto" href="#code-section">Code</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#html">HTML</a>
										<a class="nav-link scrollto" href="#css">CSS</a>
										<a class="nav-link scrollto" href="#sass">Sass</a>
										<a class="nav-link scrollto" href="#less">LESS</a>
										<a class="nav-link scrollto" href="#javascript">JavaScript</a>
										<a class="nav-link scrollto" href="#python">Python</a>
										<a class="nav-link scrollto" href="#php">PHP</a>
										<a class="nav-link scrollto" href="#handlebars">Handlebars</a>
									</nav><!--//nav-->

									<a class="nav-link scrollto" href="#callouts-section">Callouts</a>
									<a class="nav-link scrollto" href="#tables-section">Tables</a>
									<a class="nav-link scrollto" href="#buttons-section">Buttons</a>
									<a class="nav-link scrollto" href="#icons-section">Icons</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->