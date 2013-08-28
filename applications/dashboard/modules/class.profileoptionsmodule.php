<?php if (!defined('APPLICATION')) exit();

/**
 * Displays profile options like "Message", "Edit Profile", or "Back to Profile" buttons on the top of the profile page.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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
         if ($Controller->User->UserID != $Session->UserID) {
            if ($Session->CheckPermission('Garden.Users.Edit'))
               $ProfileOptions[] = array('Text' => Sprite('SpEditProfile').' '.T('Edit Profile'), 'Url' => UserUrl($Controller->User, '', 'edit'));
         } else if (C('Garden.UserAccount.AllowEdit')) {
            $ProfileOptions[] = array('Text' => Sprite('SpEditProfile').' '.T('Edit Profile'), 'Url' => '/profile/edit');
         }
         if ($Session->CheckPermission('Garden.Moderation.Manage') && $UserID != $Session->UserID) {
            // Ban/Unban
            if ($Controller->User->Banned) {
               $ProfileOptions[] = array('Text' => Sprite('SpBan').' '.T('Unban'), 'Url' => "/user/ban?userid=$UserID&unban=1", 'CssClass' => 'Popup');
            } else {
               $ProfileOptions[] = array('Text' => Sprite('SpBan').' '.T('Ban'), 'Url' => "/user/ban?userid=$UserID", 'CssClass' => 'Popup');
            }

            // Delete content.
            $ProfileOptions[] = array('Text' => Sprite('SpDelete').' '.T('Delete Content'), 'Url' => "/user/deletecontent?userid=$UserID", 'CssClass' => 'Popup');
         }
      }
      return parent::ToString();
   }
}