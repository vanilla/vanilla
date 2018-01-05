<?php
/**
 * User Info module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders information about a user in the user profile (email, join date, visits, etc).
 */
class UserInfoModule extends Gdn_Module {

    /** @var array  */
    public $User;

    /** @var array  */
    public $Roles;

    /**
     *
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        $this->User = false;
        $this->path(__FILE__);
        parent::__construct($sender);
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function loadData() {
        $userID = Gdn::controller()->data('Profile.UserID', Gdn::session()->UserID);
        $this->User = Gdn::userModel()->getID($userID);
        $this->Roles = Gdn::userModel()->getRoles($userID)->resultArray();
        // Hide personal info roles
        if (!checkPermission('Garden.PersonalInfo.View')) {
            $this->Roles = array_filter($this->Roles, 'RoleModel::FilterPersonalInfo');
        }
    }

    public function toString() {
        if (!$this->User) {
            $this->loadData();
        }

        if (is_object($this->User)) {
            return parent::toString();
        }

        return '';
    }
}
