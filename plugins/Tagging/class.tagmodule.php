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
   protected $CategorySearch;
   
   public function __construct($Sender = '') {
      $this->_TagData = FALSE;
      $this->ParentID = NULL;
      $this->ParentType = 'Global';
      $this->CategorySearch = C('Plugins.Tagging.CategorySearch', FALSE);
      parent::__construct($Sender);
   }
   
   public function __set($Name, $Value) {
      if ($Name == 'Context')
         $this->AutoContext($Value);
   }
   
   protected function AutoContext($Hint = NULL) {
      // If we're already configured, don't auto configure
      if (!is_null($this->ParentID) && is_null($Hint)) return;
      
      // If no hint was given, determine by environment
      if (is_null($Hint)) {
         if (Gdn::Controller() instanceof Gdn_Controller) {
            $DiscussionID = Gdn::Controller()->Data('Discussion.DiscussionID', NULL);
            $CategoryID = Gdn::Controller()->Data('Category.CategoryID', NULL);
            
            if ($DiscussionID) {
               $Hint = 'Discussion';
            } elseif ($CategoryID) {
               $Hint = 'Category';
            } else {
               $Hint = 'Global';
            }
         }
      }
      
      switch ($Hint) {
         case 'Discussion':
            $this->ParentType = 'Discussion';
            $DiscussionID = Gdn::Controller()->Data('Discussion.DiscussionID');
            $this->ParentID = $DiscussionID;
            break;
         
         case 'Category':
            if ($this->CategorySearch) {
               $this->ParentType = 'Category';
               $CategoryID = Gdn::Controller()->Data('Category.CategoryID');
               $this->ParentID = $CategoryID;
            }
            break;
      }
      
      if (!$this->ParentID) {
         $this->ParentID = 0;
         $this->ParentType = 'Global';
      }
      
   }
   
   public function GetData() {
      $TagQuery = Gdn::SQL();
      
      $this->AutoContext();
      
      $TagCacheKey = "TagModule-{$this->ParentType}-{$this->ParentID}";
      switch ($this->ParentType) {
         case 'Discussion':
            $TagQuery->Join('TagDiscussion td', 't.TagID = td.TagID')
               ->Where('td.DiscussionID', $this->ParentID)
               ->Cache($TagCacheKey, 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
            break;
         
         case 'Category':
            $TagQuery->Join('TagDiscussion td', 't.TagID = td.TagID')
               ->Select('COUNT(DISTINCT td.TagID)', '', 'NumTags')
               ->Where('td.CategoryID', $this->ParentID)
               ->GroupBy('td.TagID')
               ->Cache($TagCacheKey, 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
            break;
         
         case 'Global':
            $TagCacheKey = 'TagModule-Global';
            $TagQuery->Where('t.CountDiscussions >', 0, FALSE)
               ->Cache($TagCacheKey, 'get', array(Gdn_Cache::FEATURE_EXPIRY => 120));
            
            if ($this->CategorySearch)
               $TagQuery->Where('t.CategoryID', '-1');
            
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
      if (!$this->_TagData)
         $this->GetData();
      
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
               $Url = (rawurlencode($Tag['Name']) == $Tag['Name']) ? '/'.rawurlencode($Tag['Name']) : '?Tag='.urlencode($Tag['Name']);
               echo Anchor(htmlspecialchars($Tag['Name']), 
                       'discussions/tagged'.$Url, 
                       array('class' => 'Tag_'.str_replace(' ', '_', $Tag['Name']))
                    );
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
               $Url = (rawurlencode($Tag['Name']) == $Tag['Name']) ? '/'.rawurlencode($Tag['Name']) : '?Tag='.urlencode($Tag['Name']);
               echo Anchor(htmlspecialchars($Tag['Name']), 
                       'discussions/tagged'.$Url, 
                       array('class' => 'Tag_'.str_replace(' ', '_', $Tag['Name']))
                    );
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