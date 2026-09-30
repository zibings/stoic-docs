<?php

	namespace Acme\Widgets;

	use Acme\Widgets\Util\Color;

	/**
	 * A widget you can paint.
	 *
	 * Longer description that is not part of the summary.
	 */
	class Widget implements \JsonSerializable {
		const string DEFAULT_NAME = 'widget';

		/** @var string[] */
		public array $tags = [];
		protected int $hidden = 0;

		/**
		 * Builds a widget.
		 *
		 * @param string $name Display name.
		 * @param Color $color Paint color.
		 */
		public function __construct(public readonly string $name, protected Color $color = Color::Red) {
		}

		/**
		 * Paints the widget.
		 *
		 * @param Color $color New color.
		 * @return static
		 * @throws \InvalidArgumentException when the color is unknown
		 */
		public function paint(Color $color) : static {
			return $this;
		}

		/**
		 * @deprecated use paint()
		 */
		public function setColor(string $color) : void {
		}

		/** @internal */
		public function debugState() : array {
			return [];
		}

		protected function secret() : void {
		}

		public function jsonSerialize() : mixed {
			return [];
		}
	}

	/**
	 * Makes a default widget.
	 *
	 * @param string|null $name Optional name.
	 */
	function make_widget(?string $name = null, int ...$sizes) : Widget {
		return new Widget($name ?? Widget::DEFAULT_NAME);
	}
