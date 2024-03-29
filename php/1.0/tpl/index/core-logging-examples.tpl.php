<?php $this->layout('shared::master', [
	'page' => $page,
	'pageSection' => $pageSection ?? null,
	'breadcrumbs' => [
		['text' => 'Home', 'queryVars' => ['page' => 'toc'], 'active' => false],
		['text' => 'Core Component', 'queryVars' => ['page' => 'component-core'], 'active' => false],
		['text' => 'Logging', 'queryVars' => ['page' => 'core-logging'], 'active' => false],
		['text' => 'Examples', 'queryVars' => ['page' => 'core-logging-examples'], 'active' => true]
	],
	'title' => 'Stoic:PHP - Logging Examples'
]); ?>

<?php $this->insert('shared::doc-header', [
	'pageFile' => __FILE__,
	'docTitle' => 'Logging Examples'
]); ?>
					
					<div class="doc-body row">
						<div class="doc-content col-md-9 col-12 order-1">
							<div class="content-inner">
								<section id="examples-logger" class="doc-section">
									<h2 class="section-title">Logger Examples</h2>

									<p>&nbsp;</p>

									<h6 id="examples-logger-basic">Basic Usage</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Log\Logger;

$log = new Logger();
$log->emergency("Testing emergency logging");
$log->alert("Testing alert logging");
$log->critical("Testing critical logging");
$log->error("Testing error logging");
$log->warning("Testing warning logging");
$log->notice("Testing notice logging");
$log->info("Testing info logging");
$log->debug("Testing debug logging");

/*

Logger now produces this message collection, from memory:
    [
	    0 => Stoic\Log\Message { "level": "EMERGENCY", "message": "Testing emergency logging", "timestamp": "2018-09-25 17:10:40.518600" },
	    1 => Stoic\Log\Message { "level": "ALERT", "message": "Testing alert logging", "timestamp": "2018-09-25 17:10:40.518900" },
	    2 => Stoic\Log\Message { "level": "CRITICAL", "message": "Testing critical logging", "timestamp": "2018-09-25 17:10:40.519000" },
	    3 => Stoic\Log\Message { "level": "ERROR", "message": "Testing error logging", "timestamp": "2018-09-25 17:10:40.519000" },
	    4 => Stoic\Log\Message { "level": "WARNING", "message": "Testing warning logging", "timestamp": "2018-09-25 17:10:40.519100" },
	    5 => Stoic\Log\Message { "level": "NOTICE", "message": "Testing notice logging", "timestamp": "2018-09-25 17:10:40.519100" },
	    6 => Stoic\Log\Message { "level": "INFO", "message": "Testing info logging", "timestamp": "2018-09-25 17:10:40.519200" },
	    7 => Stoic\Log\Message { "level": "DEBUG", "message": "Testing debug logging", "timestamp": "2018-09-25 17:10:40.519200" }
    ]

*/
</code></pre>
									</div>

									<h6 id="examples-logger-level">Usage w/ Minimum Level</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Log\Logger;

$log = new Logger(\Psr\Log\LogLevel::WARNING);
$log->emergency("Testing emergency logging");
$log->alert("Testing alert logging");
$log->critical("Testing critical logging");
$log->error("Testing error logging");
$log->warning("Testing warning logging");
$log->notice("Testing notice logging");
$log->info("Testing info logging");
$log->debug("Testing debug logging");

/*

Logger now produces this message collection, from memory (notice we're missing anything less severe than a warning):
    [
        0 => Stoic\Log\Message { "level": "EMERGENCY", "message": "Testing emergency logging", "timestamp": "2018-09-25 17:17:28.449400" }
        1 => Stoic\Log\Message { "level": "ALERT", "message": "Testing alert logging", "timestamp": "2018-09-25 17:17:28.449600" }
        2 => Stoic\Log\Message { "level": "CRITICAL", "message": "Testing critical logging", "timestamp": "2018-09-25 17:17:28.449600" }
        3 => Stoic\Log\Message { "level": "ERROR", "message": "Testing error logging", "timestamp": "2018-09-25 17:17:28.449700" }
        4 => Stoic\Log\Message { "level": "WARNING", "message": "Testing warning logging", "timestamp": "2018-09-25 17:17:28.449700" }
    ]

*/
</code></pre>
									</div>

									<h6 id="examples-logger-context">Context Interpolation</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Log\Logger;

class DummyClass {
    public $variable = 'value';
}

class LessDummyClass {
    public $variable = 'value';

    public function __toString() {
        return "[object LessDummyClass: variable={$this->variable}]";
    }
}

$log = new Logger();
$log->info("This is an exception: {exception}", array('exception' => new Exception("Test exception")));
$log->info("This is a {something}", array('something' => 'message'));
$log->info("This is an object: {obj}", array('obj' => new DummyClass()));
$log->info("This is a smarter object: {obj}", array('obj' => new LessDummyClass()));
$log->info("And this is a datetime object: {obj}", array('obj' => new DateTime()));

/*

Logger now produces this message collection, from memory (notice we're missing anything less severe than a warning):
    [
        0 => Stoic\Log\Message { "level": "INFO", "message": "This is an exception: [exception Exception]
            Message: Test exception
            Stack Trace: #0 {main}", "timestamp": "2018-09-25 17:24:11.228700" }
        1 => Stoic\Log\Message { "level": "INFO", "message": "This is a message", "timestamp": "2018-09-25 17:24:11.228900" }
        2 => Stoic\Log\Message { "level": "INFO", "message": "This is an object: [object DummyClass]", "timestamp": "2018-09-25 17:24:11.228900" }
        3 => Stoic\Log\Message { "level": "INFO", "message": "This is a smarter object: [object LessDummyClass: variable=value]", "timestamp": "2018-09-25 17:24:11.229000" }
        4 => Stoic\Log\Message { "level": "INFO", "message": "And this is a datetime object: 2018-09-25T17:24:11-04:00", "timestamp": "2018-09-25 17:24:11.229000" }
    ]

*/
</code></pre>
									</div>
								</section>

								<section id="examples-appender" class="doc-section">
									<h2 class="section-title">Appender Examples</h2>

									<p>&nbsp;</p>

									<h6 id="examples-appender-basic">Basic Appender</h6>
									<div class="code-block">
										<pre class="language-php"><code>
use Stoic\Chain\DispatchBase;
use Stoic\Log\AppenderBase;
use Stoic\Log\MessageDispatch;

class EchoAppender extends AppenderBase {
    public function __construct() {
        $this->setKey('EchoAppender');
        $this->setVersion('1.0.0');

        return;
    }

    public function process($sender, DispatchBase &$dispatch) {
        if (!($dispatch instanceof MessageDispatch)) {
            return;
        }

        foreach (array_values($dispatch->messages) as $message) {
            echo("{$message}\n");
        }

        return;
    }
}

class JsonAppender extends AppenderBase {
    public function __construct() {
        $this->setKey('JsonAppender');
        $this->setVersion('1.0.0');

        return;
    }

    public function process($sender, DispatchBase &$dispatch) {
        if (!($dispatch instanceof MessageDispatch)) {
            return;
        }

        foreach (array_values($dispatch->messages) as $message) {
            echo("{$message->__toJson()}\n");
        }

        return;
    }
}
</code></pre>
									</div>

									<h6 id="examples-appender-adding">Adding Appenders</h6>
									<div class="code-block">
										<pre class="language-php">
use Psr\Log\LogLevel;
use Stoic\Log\Logger;

// Add appenders using constructor
$log = new Logger(LogLevel::DEBUG, array(new EchoAppender(), new JsonAppender()));
$log->info("Testing info logging");
$log->output();

// Add appenders using addAppender() method
$log = new Logger();
$log->addAppender(new EchoAppender());
$log->addAppender(new JsonAppender());
$log->info("Testing info logging");
$log->output();

/*

Both instances produce the same results in different places/formats:

    2018-09-25 17:59:19.599100 INFO      Testing info logging
    { "level": "INFO", "message": "Testing info logging", "timestamp": "2018-09-25 17:59:19.599100" }

*/
</code></pre>
									</div>
								</section>

								<section id="examples-reading" class="doc-section">
									<h2 class="section-title">Further Reading</h2>

									<div class="section-block">
										<ul>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-logger'])?>">Logger</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-appenders'])?>">Appenders</a></li>
											<li><a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-logging-messages'])?>">Messages</a></li>
										</ul>
									</div>
								</section>

								<section id="whats-next" class="doc-section">
									<h2 class="section-title">Next Up</h2>

									<div class="section-block">
										<p>
											Continue to read about <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'core-returnhelper'])?>">ReturnHelper</a>,
											or visit the <a href="<?=$page->getAssetPath('~/php/1.0/', ['page' => 'toc'])?>">Table of Contents</a>.
										</p>
									</div>
								</section>
							</div><!--//content-inner-->
						</div><!--//doc-content-->

						<div class="doc-sidebar col-md-3 col-12 order-0 d-none d-md-flex">
							<div id="doc-nav" class="doc-nav">
								<nav id="doc-menu" class="nav doc-menu flex-column sticky">
									<a class="nav-link scrollto" href="#examples-logger">Logger Examples</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#examples-logger-basic">Basic Usage</a>
										<a class="nav-link scrollto" href="#examples-logger-level">Minimum Level</a>
										<a class="nav-link scrollto" href="#examples-logger-context">Interpolation</a>
									</nav>

									<a class="nav-link scrollto" href="#examples-appender">Appender Examples</a>

									<nav class="doc-sub-menu nav flex-column">
										<a class="nav-link scrollto" href="#examples-appender-basic">Basic Appender</a>
										<a class="nav-link scrollto" href="#examples-appender-adding">Adding Appenders</a>
									</nav>

									<a class="nav-link scrollto" href="#examples-reading">Further Reading</a>
									<a class="nav-link scrollto" href="#whats-next">What's Next</a>
								</nav><!--//doc-menu-->
							</div>
						</div><!--//doc-sidebar-->
					</div><!--//doc-body-->