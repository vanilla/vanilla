<?php if (!defined('APPLICATION')) exit();

/**
 * Identity interface
 * 
 * DOCUMENT ME
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

interface Gdn_IIdentity {
	/**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    *
    * @return int
    */
   public function GetIdentity();
	
	/**
    * Generates the user's session cookie.
    *
    * @param int $UserID The unique id assigned to the user in the database.
    * @param boolean $Persist Should the user's session remain persistent across visits?
    */
   public function SetIdentity($UserID, $Persist = FALSE);
	
}