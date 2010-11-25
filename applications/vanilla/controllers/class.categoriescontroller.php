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
 * Categories Controller
 *
 * @package Vanilla
 */
 
/**
 * Handles displaying categories.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class CategoriesController extends VanillaController {
   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'Form', 'CategoryModel');
   
   /**
    * Should the discussions have their options available.
    * 
    * @since 2.0.0
    * @access public
    * @var bool
    */
   public $ShowOptions = TRUE;
   
   /**
    * Unique identifier.
    * 
    * @since 2.0.0
    * @access public
    * @var int
    */
   public $CategoryID;
   
   /**
    * Category object.
    * 
    * @since 2.0.0
    * @access public
    * @var object
    */
   public $Category;
   
   /**
    * Show all discussions in a particular category.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param string $CategoryIdentifier Unique category slug or ID.
    * @param int $Offset Number of discussions to skip.
    */
   public function Index($CategoryIdentifier = '', $Offset = '0') {
      list($Offset, $Limit) = OffsetLimit($Offset, C('Vanilla.Discussions.PerPage', 30));
      
      if (!is_numeric($CategoryIdentifier))
         $Category = $this->CategoryModel->GetFullByUrlCode(urlencode($CategoryIdentifier));
      else
         $Category = $this->CategoryModel->GetFull($CategoryIdentifier);
      
      if ($Category === FALSE)
         return $this->All();
      
      $this->SetData('Category', $Category, TRUE);

      // Setup head
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
         
      // Set CategoryID
      $this->SetData('CategoryID', $this->Category->CategoryID, TRUE);

      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Get a DiscussionModel
      $DiscussionModel = new DiscussionModel();
      $Wheres = array('d.CategoryID' => $this->CategoryID);
      
      // Check permission
      $this->Permission('Vanilla.Discussions.View', TRUE, 'Category', $this->CategoryID);
      
      // Set discussion meta data
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $this->SetData('CountDiscussions', $CountDiscussions);
      $AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements($Wheres) : new Gdn_DataSet();
      $this->SetData('AnnounceData', $AnnounceData, TRUE);
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
      $this->CanonicalUrl(Url(ConcatSep('/', 'categories/'.GetValue('UrlCode', $Category, $CategoryIdentifier), PageNumber($Offset, $Limit, TRUE)), TRUE));
      
      // Change the controller name so that it knows to grab the discussion views
      $this->ControllerName = 'DiscussionsController';
      // Pick up the discussions class
      $this->CssClass = 'Discussions';
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Render default view
      $this->Render();
   }

   /**
    * Show all categories and few discussions from each.
    * 
    * @since 2.0.0
    * @access public
    */
   public function All() {
      // Setup head
      $this->AddCssFile('vanilla.css');
      $this->Menu->HighlightRoute('/discussions');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussions.js');
      $this->AddJsFile('jquery.menu.js');
      $this->AddJsFile('options.js');
      $this->Title(T('All Categories'));
      
      // Get category data and discussions
      $this->DiscussionsPerCategory = C('Vanilla.Discussions.PerCategory', 5);
      $DiscussionModel = new DiscussionModel();
      $this->CategoryData = $this->CategoryModel->GetFull();
		$this->SetData('Categories', $this->CategoryData);
      $this->CategoryDiscussionData = array();
      foreach ($this->CategoryData->Result() as $Category) {
         $this->CategoryDiscussionData[$Category->CategoryID] = $DiscussionModel->Get(0, $this->DiscussionsPerCategory, array('d.CategoryID' => $Category->CategoryID));
      }
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Set view and render
      $this->View = 'all';
      $this->Render();
   }
   
   /**
    * Highlight route.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/categories');
   }      
}