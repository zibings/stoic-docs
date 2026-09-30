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
		 * @param int $coats How many coats.
		 * @return static
		 * @throws \InvalidArgumentException when the color is unknown
		 */
		public function paint(Color $color, int $coats = 1) : static {
			return $this;
		}

		/** @internal */
		public function debugState() : array {
			return [];
		}

		protected function secret() : void {
		}

		/** Builds a widget from an array. */
		public static function fromArray(array $data) : self {
			return new self($data['name']);
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
