<?php

	use Acme\Widgets\Widget;

	$w = Widget::fromArray(['name' => 'x']);
	$w->paint(\Acme\Widgets\Util\Color::Red);
	echo $w->name;
