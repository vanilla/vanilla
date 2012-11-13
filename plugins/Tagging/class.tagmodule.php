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
   protected $_ParentID;
   protected $_ParentType;
   
   public function __construct($Sender = '') {
      $this->_TagData = FALSE;
      $this->_ParentID = 0;
      parent::__construct($Sender);
   }
   
   public function GetData($ParentID = '', $ParentType = 'Discussion') {
      $TagQuery = Gdn::SQL();
      
      if (!$ParentID)
         $TagQuery->Cache('TagModule', 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
      
      if (is_numeric($ParentID) && $ParentID > 0) {
         $this->_ParentID = $ParentID;
         $this->_ParentType = $ParentType;
         
         switch ($ParentType) {
            case 'Discussion':
               $TagQuery->Join('TagDiscussion td', 't.TagID = td.TagID')
                  ->Where('td.DiscussionID', $this->_ParentID);
               break;
            case 'Category':
               $TagQuery->Join('TagDiscussion td', 't.TagID = td.TagID')
                  ->Select('COUNT(DISTINCT td.TagID)', 'NumTags')
                  ->Where('td.CategoryID', $this->_ParentID)
                  ->GroupBy('td.TagID');
               break;
         }
      } else {
         $TagQuery->Where('t.CountDiscussions >', 0, FALSE);
      }
      
      $this->_TagData = $TagQuery
         ->Select('t.*')
         ->From('Tag t')
         ->OrderBy('t.CountDiscussions', 'desc')
         ->Limit(25)
         ->Get();
      
      $this->_TagData->DatasetType(DATASET_TYPE_ARRAY);
   }

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function InlineDisplay() {
      if ($this->_TagData->NumRows() == 0)
         return '';
      $String = '';
      ob_start();
      ?>
      <div class="InlineTags Meta">
         <?php echo T('Tagged'); ?>:
         <ul>
         <?php
         foreach ($this->_TagData->ResultArray() as $Tag) {
            if ($Tag['Name'] != '') {
         ?>
            <li><?php 
               if (rawurlencode($Tag['Name']) == $Tag['Name']) {
                  echo Anchor(htmlspecialchars($Tag['Name']), 'discussions/tagged/'.rawurlencode($Tag['Name']));
               } else {
                  echo Anchor(htmlspecialchars($Tag['Name']), 'discussions/tagged?Tag='.urlencode($Tag['Name']));
               }
            ?></li>
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

   public function ToString() {
      if (!$this->_TagData)
         $this->GetData();
      
      if ($this->_TagData->NumRows() == 0)
         return '';
      $String = '';
      ob_start();
      ?>
      <div class="Box Tags">
         <h4><?php echo T($this->_ParentID > 0 ? 'Tagged' : 'Popular Tags'); ?></h4>
         <ul class="TagCloud">
         <?php
         foreach ($this->_TagData->Result() as $Tag) {
            if ($Tag['Name'] != '') {
         ?>
            <li><span><?php 
               if (urlencode($Tag['Name']) == $Tag['Name']) {
                  echo Anchor(htmlspecialchars($Tag['Name']), 'discussions/tagged/'.urlencode($Tag['Name']));
               } else {
                  echo Anchor(htmlspecialchars($Tag['Name']), 'discussions/tagged?Tag='.urlencode($Tag['Name']));
               }
            ?></span> <span class="Count"><?php echo number_format($Tag['CountDiscussions']); ?></span></li>
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