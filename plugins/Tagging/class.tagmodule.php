<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class TagModule extends Gdn_Module {
   
   protected $_TagData;
   protected $_DiscussionID;
   
   public function __construct(&$Sender = '') {
      $this->_TagData = FALSE;
      $this->_DiscussionID = 0;
      parent::__construct($Sender);
   }
   
   public function GetData($DiscussionID = '') {
      $SQL = Gdn::SQL();
      if (is_numeric($DiscussionID) && $DiscussionID > 0) {
         $this->_DiscussionID = $DiscussionID;
         $SQL->Join('TagDiscussion td', 't.TagID = td.TagID')
            ->Where('td.DiscussionID', $DiscussionID);
      }
            
      $this->_TagData = $SQL
         ->Select('t.*')
         ->From('Tag t')
         ->Where('t.CountDiscussions >', 0, FALSE)
         ->OrderBy('t.CountDiscussions', 'desc')
         ->Limit(25)
         ->Get();
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if ($this->_TagData->NumRows() == 0)
         return '';
      
      $String = '';
      ob_start();
      ?>
      <div class="Box Tags">
         <h4><?php echo T($this->_DiscussionID > 0 ? 'Tagged' : 'Popular Tags'); ?></h4>
         <ul class="PanelInfo">
         <?php
         foreach ($this->_TagData->Result() as $Tag) {
            if ($Tag->Name != '') {
         ?>
            <li><strong><?php 
                           if (urlencode($Tag->Name) == $Tag->Name) {
                              echo Anchor(htmlspecialchars($Tag->Name), 'discussions/tagged/'.urlencode($Tag->Name));
                           } else {
                              echo Anchor(htmlspecialchars($Tag->Name), 'discussions/tagged?Tag='.urlencode($Tag->Name));
                           }
                        ?></strong> <?php echo $Tag->CountDiscussions; ?></li>
         <?php
            }
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