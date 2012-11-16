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
   protected $ParentID;
   protected $ParentType;
   
   public function __construct($Sender = '') {
      $this->_TagData = FALSE;
      $this->ParentID = 0;
      $this->ParentType = 'Global';
      parent::__construct($Sender);
   }
   
   public function __set($Name, $Value) {
      switch ($Name) {
         case 'Category':
            $CategorySearch = C('Plugins.Tagging.CategorySearch', FALSE);
            if ($CategorySearch) {
               $this->ParentType = 'Category';
               $CategoryID = Gdn::Controller() instanceof Gdn_Controller ? Gdn::Controller()->Data('Category.CategoryID') : NULL;
               $this->ParentID = $CategoryID;
            }
            break;
         
         case 'Discussion':
            $this->ParentType = 'Discussion';
            $DiscussionID = Gdn::Controller() instanceof Gdn_Controller ? Gdn::Controller()->Data('Discussion.DiscussionID') : NULL;
            $this->ParentID = $DiscussionID;
            break;
      }
      
      if (!$this->ParentID) {
         $this->ParentType = 'Global';
         $this->ParentID = 0;
      }
   }
   
   public function GetData() {
      $TagQuery = Gdn::SQL();
      
      $TagCacheKey = "TagModule-{$this->ParentType}-{$this->ParentID}";
      switch ($this->ParentType) {
         case 'Discussion':
            $TagQuery->Join('TagDiscussion td', 't.TagID = td.TagID')
               ->Where('td.DiscussionID', $this->ParentID)
               ->Cache($TagCacheKey, 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
            break;
         
         case 'Category':
            $TagQuery->Join('TagDiscussion td', 't.TagID = td.TagID')
               ->Select('COUNT(DISTINCT td.TagID)', 'NumTags')
               ->Where('td.CategoryID', $this->ParentID)
               ->GroupBy('td.TagID')
               ->Cache($TagCacheKey, 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
            break;
         
         case 'Global':
            $TagCacheKey = 'TagModule-Global';
            $TagQuery->Where('t.CountDiscussions >', 0, FALSE)
               ->Cache($TagCacheKey, 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
            break;
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
         <h4><?php echo T($this->ParentID > 0 ? 'Tagged' : 'Popular Tags'); ?></h4>
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