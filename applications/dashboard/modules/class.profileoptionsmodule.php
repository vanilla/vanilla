<?php
/**
 * Profile options module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Displays profile options like "Message", "Edit Profile", or "Back to Profile" buttons on the top of the profile page.
 */
class ProfileOptionsModule extends Gdn_Module {

    public function AssetTarget() {
        return 'Content';
    }

    public function ToString() {
        $Session = Gdn::Session();
        $Controller = Gdn::Controller();
        $UserID = $Controller->User->UserID;
        $MemberOptions = array();
        $ProfileOptions = array();
        $Controller->EventArguments['UserID'] = $UserID;
        $Controller->EventArguments['ProfileOptions'] = &$ProfileOptions;
        $Controller->EventArguments['MemberOptions'] = &$MemberOptions;
        if ($Controller->EditMode) {
            return '<div class="ProfileOptions">'.Anchor(T('Back to Profile'), UserUrl($Controller->User), array('class' => 'ProfileButtons')).'</div>';
//         $ProfileOptions[] = array('Text' => T('Back to Profile'), 'Url' => UserUrl($Controller->User), 'CssClass' => 'BackToProfile');
        } else {
            // Profile Editing
            if (hasEditProfile($Controller->User->UserID)) {
                $ProfileOptions[] = array('Text' => Sprite('SpEditProfile').' '.T('Edit Profile'), 'Url' => UserUrl($Controller->User, '', 'edit'));
            } elseif ($Session->IsValid() && $UserID == $Session->UserID) {
                $ProfileOptions[] = array('Text' => Sprite('SpEditProfile').' '.T('Preferences'), 'Url' => UserUrl($Controller->User, '', 'preferences'));
            }

            // Ban/Unban
            $MayBan = CheckPermission('Garden.Moderation.Manage') || CheckPermission('Garden.Users.Edit') || CheckPermission('Moderation.Users.Ban');
            if ($MayBan && $UserID != $Session->UserID) {
                if (BanModel::isBanned($Controller->User->Banned, BanModel::BAN_MANUAL)) {
                    $ProfileOptions[] = array('Text' => Sprite('SpBan').' '.T('Unban'), 'Url' => "/user/ban?userid=$UserID&unban=1", 'CssClass' => 'Popup');
                } elseif (!$Controller->User->Admin) {
                    $ProfileOptions[] = array('Text' => Sprite('SpBan').' '.T('Ban'), 'Url' => "/user/ban?userid=$UserID", 'CssClass' => 'Popup');
                }
            }

            // Delete content.
            if (CheckPermission('Garden.Moderation.Manage'))
                $ProfileOptions[] = array('Text' => Sprite('SpDelete').' '.T('Delete Content'), 'Url' => "/user/deletecontent?userid=$UserID", 'CssClass' => 'Popup');
        }
        return parent::ToString();
    }
}
