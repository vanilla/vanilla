<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
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