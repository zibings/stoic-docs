<?php

	namespace App;

	use Acme\Widgets\Widget;
	use Acme\Widgets\Util\Color;
	use function Acme\Widgets\make_widget;

	class App extends Widget {
		public function run(Widget $given) : void {
			$w = new Widget('a');
			$w->paint(Color::Red);
			$w->paint(Color::Blue)->paint(Color::Red);
			$given->setColor('x');
			echo Widget::DEFAULT_NAME;

			if ($given instanceof Widget) {
				(new Widget('b'))->paint(Color::Red);
			}
		}
	}
