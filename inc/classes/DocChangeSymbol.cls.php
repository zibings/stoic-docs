<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Links a change to a symbol it affects.
	 *
	 * @package Zibings
	 */
	class DocChangeSymbol extends StoicDbModel {
		/**
		 * Identifier of the change.
		 *
		 * @var int
		 */
		public int $changeId;
		/**
		 * Identifier of the symbol.
		 *
		 * @var int
		 */
		public int $symbolId;


		/**
		 * Determines if the system should attempt to create the link.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canCreate() : bool|ReturnHelper {
			return $this->changeId > 0 && $this->symbolId > 0;
		}

		/**
		 * Determines if the system should attempt to delete the link.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->changeId > 0 && $this->symbolId > 0;
		}

		/**
		 * Determines if the system should attempt to read the link.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->changeId > 0 && $this->symbolId > 0;
		}

		/**
		 * Links have no updatable columns.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			return false;
		}

		/**
		 * Initializes a new DocChangeSymbol object.
		 *
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocChangeSymbol');

			$this->setColumn('changeId', 'ChangeID', BaseDbTypes::INTEGER, BCF::IS_KEY | BCF::SHOULD_INSERT);
			$this->setColumn('symbolId', 'SymbolID', BaseDbTypes::INTEGER, BCF::IS_KEY | BCF::SHOULD_INSERT);

			$this->changeId = 0;
			$this->symbolId = 0;

			return;
		}
	}
