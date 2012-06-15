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
      $Controller = Gdn::Controller();
      $Session = Gdn::Session();
      $UserID = $Controller->User->UserID;
      
      // Add some advanced options.
      $this->SetData('Advanced', array());
      
      if ($Session->CheckPermission('Garden.Moderation.Manage') && $Controller->User->UserID != $Session->UserID) {
         
         // Ban/Unban
         if ($Controller->User->Banned) {
            $this->Data['Advanced']['BanUnban'] = array('Label' => T('Unban'), 'Url' => "/user/ban?userid=$UserID&unban=1", 'Class' => 'Popup');
         } else {
            $this->Data['Advanced']['BanUnban'] = array('Label' => T('Ban'), 'Url' => "/user/ban?userid=$UserID", 'Class' => 'Popup');
         }
         
         // Delete content.
         if ($Controller->User->Banned) {
            $this->Data['Advanced']['DeleteContent'] = array('Label' => T('Delete Content'), 'Url' => "/user/deletecontent?userid=$UserID", 'Class' => 'Popup');
         }
      }
      return parent::ToString();
   }
}