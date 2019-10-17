<?php $this->layout('shared::landing-master', ['page' => $page, 'pageSection' => $pageSection ?? null]); ?>

					<h2 class="title">Getting started is easy!</h2>
					
					<div class="intro">
						<p>
							Welcome to Stoic:PHP, a framework built to keep your code clearer and reduce unnecessary abstraction.  If you're just looking to get started, go ahead and click the button below.
						</p>

						<div class="cta-container">
							<a class="btn btn-primary btn-cta" href="<?=$page->getAssetPath('~/php/how-to-install.php')?>"><i class="fas fa-cloud-download-alt"></i> How To Install</a>
						</div><!--//cta-container-->
					</div><!--//intro-->
					
					<div id="cards-wrapper" class="cards-wrapper row cta-container">
						<div class="item item-green col-lg-4 col-6">
							<div class="item-inner">
								<div class="icon-holder">
									<i class="icon fa fa-paper-plane"></i>
								</div><!--//icon-holder-->
								
								<h3 class="title">Web Quick Start</h3>
								
								<p class="intro">The absolute basics to starting a new website with Stoic:PHP.</p>
								<a class="link" href="<?=$page->getAssetPath('~/php/quick-start.php')?>"><span></span></a>
							</div><!--//item-inner-->
						</div><!--//item-->
						
						<div class="item item-pink item-2 col-lg-4 col-6">
							<div class="item-inner">
								<div class="icon-holder">
									<span aria-hidden="true" class="icon icon_puzzle_alt"></span>
								</div><!--//icon-holder-->
								
								<h3 class="title">Components</h3>
								
								<p class="intro">See the list of Stoic:PHP components and their documentation.</p>
								<a class="link" href="<?=$page->getAssetPath('~/php/components')?>"><span></span></a>
							</div><!--//item-inner-->
						</div><!--//item-->
						
						<div class="item item-purple col-lg-4 col-6">
							<div class="item-inner">
								<div class="icon-holder">
									<span aria-hidden="true" class="icon icon_lifesaver"></span>
								</div><!--//icon-holder-->
							
								<h3 class="title">API Reference</h3>
							
								<p class="intro">Full reference documentation for all components and their contents. (Coming Soon)</p>
								<a class="link" href="javascript: void(0);"><span></span></a>
							</div><!--//item-inner-->
						</div><!--//item-->
					</div><!--//cards-->

<?php $this->push('tagline') ?>
					<div class="tagline">
						<p>Devoted to Keeping Code Clear</p>
					</div><!--//tagline-->
<?php $this->end() ?>