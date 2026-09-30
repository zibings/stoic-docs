<?php

	namespace Zibings;

	use Stoic\Pdo\BaseDbColumnFlags as BCF;
	use Stoic\Pdo\BaseDbTypes;
	use Stoic\Pdo\StoicDbModel;
	use Stoic\Utilities\ReturnHelper;

	/**
	 * Roles a symbol can play on a page.
	 *
	 * @package Zibings
	 */
	class DocPageSymbolRoles {
		const string SUBJECT  = 'subject';
		const string MENTIONS = 'mentions';


		/**
		 * Whether the value is a valid role.
		 *
		 * @param string $role Role value to check.
		 * @return bool
		 */
		public static function isValid(string $role) : bool {
			return $role === self::SUBJECT || $role === self::MENTIONS;
		}
	}

	/**
	 * Links a page to a symbol. `subject` marks the symbol a reference page documents; `mentions` marks symbols the
	 * prose discusses, which drives the contract pane's scroll-follow and "also covered in" lists.
	 *
	 * @package Zibings
	 */
	class DocPageSymbol extends StoicDbModel {
		/**
		 * Identifier of the page.
		 *
		 * @var int
		 */
		public int $pageId;
		/**
		 * Role of the symbol on the page, see DocPageSymbolRoles.
		 *
		 * @var string
		 */
		public string $role;
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
			return $this->pageId > 0 && $this->symbolId > 0 && DocPageSymbolRoles::isValid($this->role);
		}

		/**
		 * Determines if the system should attempt to delete the link.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canDelete() : bool|ReturnHelper {
			return $this->pageId > 0 && $this->symbolId > 0;
		}

		/**
		 * Determines if the system should attempt to read the link.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canRead() : bool|ReturnHelper {
			return $this->pageId > 0 && $this->symbolId > 0;
		}

		/**
		 * Determines if the system should attempt to update the link.
		 *
		 * @return bool|ReturnHelper
		 */
		protected function __canUpdate() : bool|ReturnHelper {
			return $this->pageId > 0 && $this->symbolId > 0 && DocPageSymbolRoles::isValid($this->role);
		}

		/**
		 * Initializes a new DocPageSymbol object.
		 *
		 * @return void
		 */
		protected function __setupModel() : void {
			$this->setTableName('DocPageSymbol');

			$this->setColumn('pageId',   'PageID',   BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::SHOULD_INSERT);
			$this->setColumn('symbolId', 'SymbolID', BaseDbTypes::INTEGER, BCF::IS_KEY        | BCF::SHOULD_INSERT);
			$this->setColumn('role',     'Role',     BaseDbTypes::STRING,  BCF::SHOULD_INSERT | BCF::SHOULD_UPDATE);

			$this->pageId   = 0;
			$this->symbolId = 0;
			$this->role     = DocPageSymbolRoles::MENTIONS;

			return;
		}
	}
