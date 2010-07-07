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
 * Renders a list of users who are taking part in a particular discussion.
 */
class InThisDiscussionModule extends Gdn_Module {
   
   protected $_UserData;
   
   public function __construct(&$Sender = '') {
      $this->_UserData = FALSE;
      parent::__construct($Sender);
   }
   
   public function GetData($DiscussionID, $Limit = 50) {
      $SQL = Gdn::SQL();
      $this->_UserData = $SQL
         ->Select('u.UserID, u.Name, u.Photo')
         ->Select('c.DateInserted', 'max', 'DateLastActive')
         ->From('User u')
         ->Join('Comment c', 'u.UserID = c.InsertUserID')
         ->Where('c.DiscussionID', $DiscussionID)
         ->GroupBy('u.UserID, u.Name')
         ->OrderBy('u.Name', 'asc')
         ->Get();
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if ($this->_UserData->NumRows() == 0)
         return '';
      
      $String = '';
      ob_start();
      ?>
      <div class="Box">
         <h4><?php echo T('In this Discussion'); ?></h4>
         <ul class="PanelInfo">
         <?php
         foreach ($this->_UserData->Result() as $User) {
            ?>
            <li>
               <strong><?php
                  echo UserAnchor($User, 'UserLink');
               ?></strong>
               <?php
                  echo Gdn_Format::Date($User->DateLastActive);
               ?>
            </li>
            <?php
         }
         ?>
         </ul>
      </div>
      <?php
      $String = ob_get_contents();
      @ob_end_clean();
      return $String;
   }
}