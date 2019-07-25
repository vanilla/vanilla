<?php
/**
 * User Info module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
        $this->setData('_canViewPersonalInfo', Gdn::session()->UserID === $this->User->UserID || gdn::session()->checkPermission('Garden.PersonalInfo.View'));
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
