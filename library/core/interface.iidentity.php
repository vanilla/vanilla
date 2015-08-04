<?php
/**
 * Identity interface
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Interface Gdn_IIdentity
 */
interface Gdn_IIdentity {

    /**
     * Return the unique id assigned to a user in the database.
     *
     * This is retrieved from the session cookie if the cookie authenticates) or false if not found or authentication
     * fails.
     *
     * @return int
     */
    public function getIdentity();

    /**
     * Generates the user's session cookie.
     *
     * @param int $userID The unique id assigned to the user in the database.
     * @param boolean $persist Should the user's session remain persistent across visits?
     */
    public function setIdentity($userID, $persist = false);
}
