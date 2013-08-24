<?php if (!defined('APPLICATION')) exit();

/**
 * A wrapper for the Exception class so that methods can throw a specific 
 * application as a means of validation or user error, rather than a critical exception.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_UserException extends Exception {
	/** Constructs the Gdn_ApplicationException.
	 *
	 * @param string $Message A user readable message for the exception.
	 * @param Exception $Previous The previous exception used for exception chaining.
	 */
   public function __construct($Message, $Code = 400) {
		parent::__construct($Message, $Code);
	}
}