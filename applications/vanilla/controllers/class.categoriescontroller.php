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
 * Categories Controller - Handles displaying categories.
 */
class CategoriesController extends VanillaController {
   
   public $Uses = array('Database', 'Form', 'CategoryModel');
   
   /**
    * Should the discussions have their options available.
    */
   public $ShowOptions = TRUE;
   public $CategoryID;
   public $Category;
   /**
    * Show all discussions in a particular category.
    */
   public function Index($CategoryIdentifier = '', $Offset = '0') {
      list($Offset, $Limit) = OffsetLimit($Offset, Gdn::Config('Vanilla.Discussions.PerPage', 30));
      
      if (!is_numeric($CategoryIdentifier))
         $Category = $this->CategoryModel->GetFullByUrlCode(urldecode($CategoryIdentifier));
      else
         $Category = $this->CategoryModel->GetFull($CategoryIdentifier);
      $this->SetData('Category', $Category, TRUE);
      
      if ($Category === FALSE)
         return $this->All();
      
      $this->AddCssFile('vanilla.css');
      $this->Menu->HighlightRoute('/discussions');      
      if ($this->Head) {
         $this->Head->Title($Category->Name);
         $this->AddJsFile('discussions.js');
         $this->AddJsFile('bookmark.js');
			$this->AddJsFile('jquery.menu.js');
         $this->AddJsFile('options.js');
         $this->AddJsFile('jquery.gardenmorepager.js');
         $this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->Title());
      }
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
         
      
      $this->SetData('CategoryID', $this->Category->CategoryID, TRUE);

      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
   
      $DiscussionModel = new DiscussionModel();
      $Wheres = array('d.CategoryID' => $this->CategoryID);
      
      $this->Permission('Vanilla.Discussions.View', TRUE, 'Category', $this->CategoryID);
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $this->SetData('CountDiscussions', $CountDiscussions);
      $AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements($Wheres) : new Gdn_DataSet();
      $this->SetData('AnnounceData', $AnnounceData, TRUE   );
      $this->SetData('DiscussionData', $DiscussionModel->Get($Offset, $Limit, $Wheres), TRUE);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('Pager', $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'categories/'.$CategoryIdentifier.'/%1$s'
      );

      // Set the canonical Url.
      $this->CanonicalUrl(Url(ConcatSep('/', 'categories/'.$CategoryIdentifier, PageNumber($Offset, $Limit, TRUE)), TRUE));
      
      // Change the controller name so that it knows to grab the discussion views
      $this->ControllerName = 'DiscussionsController';
      // Pick up the discussions class
      $this->CssClass = 'Discussions';
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Render the controller
      $this->Render();
   }

   /**
    * Show all categories, and few discussions from each.
    */
   public function All() {
      $this->AddCssFile('vanilla.css');
      $this->Menu->HighlightRoute('/discussions');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussions.js');
      $this->AddJsFile('jquery.menu.js');
      $this->AddJsFile('options.js');
      $this->Title(T('All Categories'));
         
      $this->DiscussionsPerCategory = Gdn::Config('Vanilla.Discussions.PerCategory', 5);
      $DiscussionModel = new DiscussionModel();
      $this->CategoryData = $this->CategoryModel->GetFull();
      $this->CategoryDiscussionData = array();
      foreach ($this->CategoryData->Result() as $Category) {
         $this->CategoryDiscussionData[$Category->CategoryID] = $DiscussionModel->Get(0, $this->DiscussionsPerCategory, array('d.CategoryID' => $Category->CategoryID));
      }
      
      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      $this->View = 'all';
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }      
}