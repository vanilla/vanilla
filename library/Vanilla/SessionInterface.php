<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;


interface SessionInterface {
    public function getUserID();
    public function setUserID($userID);

    public function getUser();
    public function setUser($user);

    public function getPermissions();
    public function setPermissions(Permissions $permissions);
}
