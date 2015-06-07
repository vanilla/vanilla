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

    public function assetTarget() {
        return 'Content';
    }

    public function toString() {
        $Session = Gdn::session();
        $Controller = Gdn::controller();
        $UserID = $Controller->User->UserID;
        $MemberOptions = array();
        $ProfileOptions = array();
        $Controller->EventArguments['UserID'] = $UserID;
        $Controller->EventArguments['ProfileOptions'] = &$ProfileOptions;
        $Controller->EventArguments['MemberOptions'] = &$MemberOptions;
        if ($Controller->EditMode) {
            return '<div class="ProfileOptions">'.anchor(t('Back to Profile'), userUrl($Controller->User), array('class' => 'ProfileButtons')).'</div>';
//         $ProfileOptions[] = array('Text' => t('Back to Profile'), 'Url' => userUrl($Controller->User), 'CssClass' => 'BackToProfile');
        } else {
            // Profile Editing
            if (hasEditProfile($Controller->User->UserID)) {
                $ProfileOptions[] = array('Text' => sprite('SpEditProfile').' '.t('Edit Profile'), 'Url' => userUrl($Controller->User, '', 'edit'));
            } elseif ($Session->isValid() && $UserID == $Session->UserID) {
                $ProfileOptions[] = array('Text' => sprite('SpEditProfile').' '.t('Preferences'), 'Url' => userUrl($Controller->User, '', 'preferences'));
            }

            // Ban/Unban
            $MayBan = checkPermission('Garden.Moderation.Manage') || checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Users.Ban');
            if ($MayBan && $UserID != $Session->UserID) {
                if (BanModel::isBanned($Controller->User->Banned, BanModel::BAN_MANUAL)) {
                    $ProfileOptions[] = array('Text' => sprite('SpBan').' '.t('Unban'), 'Url' => "/user/ban?userid=$UserID&unban=1", 'CssClass' => 'Popup');
                } elseif (!$Controller->User->Admin) {
                    $ProfileOptions[] = array('Text' => sprite('SpBan').' '.t('Ban'), 'Url' => "/user/ban?userid=$UserID", 'CssClass' => 'Popup');
                }
            }

            // Delete content.
            if (checkPermission('Garden.Moderation.Manage')) {
                $ProfileOptions[] = array('Text' => sprite('SpDelete').' '.t('Delete Content'), 'Url' => "/user/deletecontent?userid=$UserID", 'CssClass' => 'Popup');
            }
        }
        return parent::ToString();
    }
}
