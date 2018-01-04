<?php
/**
 * Profile options module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Displays profile options like "Message", "Edit Profile", or "Back to Profile" buttons on the top of the profile page.
 */
class ProfileOptionsModule extends Gdn_Module {

    private $profileOptionsDropdown;

    public function __construct() {
        $this->profileOptionsDropdown = new DropdownModule();
        $this->profileOptionsDropdown->setView('dropdown-navbutton');
        $this->profileOptionsDropdown->setTrigger(sprite('SpEditProfile', 'Sprite16').' <span class="Hidden">'.t('Edit Profile').'</span>', 'button', 'ProfileButtons Button-EditProfile');
        $this->fetchProfileOptionsData();
    }

    public function assetTarget() {
        return 'Content';
    }

    public function fetchProfileOptionsData() {
        $session = Gdn::session();
        $controller = Gdn::controller();
        $userID = $controller->User->UserID;

        if (hasEditProfile($controller->User->UserID)) {
            $this->profileOptionsDropdown->addLink(t('Edit Profile'), userUrl($controller->User, '', 'edit'), 'edit-profile');
        } else {
            $this->profileOptionsDropdown->addLinkIf($session->isValid() && $userID == $session->UserID, t('Preferences'), userUrl($controller->User, '', 'preferences'), 'preferences');
        }

        if ($userID != $session->UserID && multiCheckPermission(['Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'])) {
            if (BanModel::isBanned($controller->User->Banned, BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL)) {
                $this->profileOptionsDropdown->addLink(t('Unban'), "/user/ban?userid=$userID&unban=1", 'unban', 'Popup');
            } elseif (!$controller->User->Admin) {
                $this->profileOptionsDropdown->addLink(t('Ban'), "/user/ban?userid=$userID", 'ban', 'Popup');
            }
        }

        $this->profileOptionsDropdown->addLinkIf(checkPermission('Garden.Moderation.Manage') == true, t('Delete Content'), "/user/deletecontent?userid=$userID", 'delete-content', 'Popup');

        $memberOptions = [];
        $profileOptions = [];

        $controller->EventArguments['UserID'] = $userID;
        $controller->EventArguments['ProfileOptions'] = &$profileOptions;
        $controller->EventArguments['ProfileOptionsDropdown'] = &$this->profileOptionsDropdown;
        $controller->EventArguments['MemberOptions'] = &$memberOptions;
        $controller->fireEvent('BeforeProfileOptions');

        foreach($profileOptions as $option) {
            if (val('Text', $option) && val('Url', $option)) {
                $this->profileOptionsDropdown->addLink(val('Text', $option), val('Url', $option), NavModule::textToKey(val('Text', $option)), val('CssClass', $option, ''));
            }
        }

        $this->setData('MemberOptions', $memberOptions);
        $this->setData('ProfileOptionsDropdown', $this->profileOptionsDropdown);
    }
}
