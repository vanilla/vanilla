<?php
/**
 * User Info module.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
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
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        $this->User = FALSE;
        $this->Path(__FILE__);
        parent::__construct($Sender);
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function LoadData() {
        $UserID = Gdn::Controller()->Data('Profile.UserID', Gdn::Session()->UserID);
        $this->User = Gdn::UserModel()->GetID($UserID);
        $this->Roles = Gdn::UserModel()->GetRoles($UserID)->ResultArray();
        // Hide personal info roles
        if (!CheckPermission('Garden.PersonalInfo.View')) {
            $this->Roles = array_filter($this->Roles, 'RoleModel::FilterPersonalInfo');
        }
    }

    public function ToString() {
        if (!$this->User)
            $this->LoadData();

        if (is_object($this->User))
            return parent::ToString();

        return '';
    }
}
