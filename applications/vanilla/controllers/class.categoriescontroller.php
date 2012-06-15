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
    * @var CategoryModel 
    */
   public $CategoryModel;
   
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
    * "Table" layout for categories. Mimics more traditional forum category layout.
    */
   public function Table() {
      if ($this->SyndicationMethod == SYNDICATION_NONE) {
         $this->View = 'table';
      } else
         $this->View = 'all';
      $this->All();
   }
   
   /**
    * Show all discussions in a particular category.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param string $CategoryIdentifier Unique category slug or ID.
    * @param int $Offset Number of discussions to skip.
    */
   public function Index($CategoryIdentifier = '', $Page = '0') {
		if ($CategoryIdentifier == '') {
			// Figure out which category layout to choose (Defined on "Homepage" settings page).
			$Layout = C('Vanilla.Categories.Layout');
			switch($Layout) {
				case 'mixed':
					$this->View = 'discussions';
					$this->Discussions();
					break;
				case 'table':
					$this->Table();
					break;
				default:
					$this->View = 'all';
					$this->All();
					break;
			}
			return;
		} else {
         Gdn_Theme::Section('DiscussionList');
			// Figure out which discussions layout to choose (Defined on "Homepage" settings page).
			$Layout = C('Vanilla.Discussions.Layout');
			switch($Layout) {
				case 'table':
               if ($this->SyndicationMethod == SYNDICATION_NONE)
                  $this->View = 'table';
					break;
				default:
					// $this->View = 'index';
					break;
			}
			
			$Category = CategoryModel::Categories($CategoryIdentifier);
			
			if (empty($Category)) {
				if ($CategoryIdentifier)
					throw NotFoundException();
			}
			$Category = (object)$Category;
				
			// Load the breadcrumbs.
			$this->SetData('Breadcrumbs', array_merge(array(array('Name' => T('Categories'), 'Url' => '/categories')), CategoryModel::GetAncestors(GetValue('CategoryID', $Category))));
			
			$this->SetData('Category', $Category, TRUE);
         
         // Load the subtree.
         if (C('Vanilla.ExpandCategories', TRUE))
            $Categories = CategoryModel::GetSubtree($CategoryIdentifier);
         else
            $Categories = array($Category);
         
         $this->SetData('Categories', $Categories);
	
			// Setup head
			$this->AddCssFile('vanilla.css');
			$this->Menu->HighlightRoute('/discussions');
			if ($this->Head) {
				$this->AddJsFile('discussions.js');
				$this->AddJsFile('bookmark.js');
				$this->AddJsFile('options.js');
				$this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->Title());
			}
			
			
			$this->Title(GetValue('Name', $Category, ''));
			$this->Description(GetValue('Description', $Category), TRUE);
			
			// Set CategoryID
         $CategoryID = GetValue('CategoryID', $Category);
			$this->SetData('CategoryID', $CategoryID, TRUE);
			
			// Add modules
         $this->AddModule('NewDiscussionModule');
         $this->AddModule('DiscussionFilterModule');
			$this->AddModule('CategoriesModule');
			$this->AddModule('BookmarkedModule');
			
			// Get a DiscussionModel
			$DiscussionModel = new DiscussionModel();
         $CategoryIDs = ConsolidateArrayValuesByKey($this->Data('Categories'), 'CategoryID');
			$Wheres = array('d.CategoryID' => $CategoryIDs);
         $this->SetData('_ShowCategoryLink', count($CategoryIDs) > 1);
			
			// Check permission
			$this->Permission('Vanilla.Discussions.View', TRUE, 'Category', GetValue('PermissionCategoryID', $Category));
			
			// Set discussion meta data.
			$this->EventArguments['PerPage'] = C('Vanilla.Discussions.PerPage', 30);
			$this->FireEvent('BeforeGetDiscussions');
			list($Offset, $Limit) = OffsetLimit($Page, $this->EventArguments['PerPage']);
			
			if (!is_numeric($Offset) || $Offset < 0)
				$Offset = 0;
				
			$CountDiscussions = $DiscussionModel->GetCount($Wheres);
			$this->SetData('CountDiscussions', $CountDiscussions);
			$this->SetData('_Limit', $Limit);
         
         // We don't wan't child categories in announcements.
         $Wheres['d.CategoryID'] = $CategoryID;
			$AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements($Wheres) : new Gdn_DataSet();
			$this->SetData('AnnounceData', $AnnounceData, TRUE);
         $Wheres['d.CategoryID'] = $CategoryIDs;
         
         $this->DiscussionData = $this->SetData('Discussions', $DiscussionModel->Get($Offset, $Limit, $Wheres));
	
			// Build a pager
			$PagerFactory = new Gdn_PagerFactory();
			$this->Pager = $PagerFactory->GetPager('Pager', $this);
			$this->Pager->ClientID = 'Pager';
			$this->Pager->Configure(
				$Offset,
				$Limit,
				$CountDiscussions,
				array('CategoryUrl')
			);
         $this->Pager->Record = $Category;
         PagerModule::Current($this->Pager);
			$this->SetData('_Page', $Page);
	
			// Set the canonical Url.
			$this->CanonicalUrl(CategoryUrl($Category, PageNumber($Offset, $Limit)));
			
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
			// Render default view.
			$this->Render();
		}
   }
	
	/**
	 * Show all (nested) categories.
	 *
	 * @since 2.0.17
	 * @access public
	 */
	public function All() {
      // Setup head.
      $this->Menu->HighlightRoute('/discussions');
      $Title = C('Garden.HomepageTitle');
      if ($Title)
         $this->Title($Title, '');
      else
         $this->Title(T('All Categories'));
      Gdn_Theme::Section('CategoryList');
            
      $this->Description(C('Garden.Description', NULL));
      
      $this->SetData('Breadcrumbs', array(array('Name' => T('Categories'), 'Url' => '/categories')), CategoryModel::GetAncestors(GetValue('CategoryID', $Category)));
     
		// Set the category follow toggle before we load category data so that it affects the category query appropriately.
		$CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
		$CategoryFollowToggleModule->SetToggle();
	   
      // Get category data
      $CategoryModel = new CategoryModel();
      $this->CategoryModel->Watching = !Gdn::Session()->GetPreference('ShowAllCategories');
      
//      $Categories = CategoryModel::Categories();
//      CategoryModel::JoinRecentPosts($Categories);
//      $this->SetData('Categories2', $Categories);
      
      
      
      $Categories = $this->CategoryModel->GetFull()->ResultArray();
		$this->SetData('Categories', $Categories);
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
		$this->AddModule('DiscussionFilterModule');
      $this->AddModule('BookmarkedModule');
		$this->AddModule($CategoryFollowToggleModule);

      $this->CanonicalUrl(Url('/categories', TRUE));
      
      // Set a definition of the user's current timezone from the db. jQuery
      // will pick this up, compare to the browser, and update the user's
      // timezone if necessary.
      $CurrentUser = Gdn::Session()->User;
      if (is_object($CurrentUser)) {
         $ClientHour = $CurrentUser->HourOffset + date('G', time());
         $this->AddDefinition('SetClientHour', $ClientHour);
      }

      include_once $this->FetchViewLocation('helper_functions', 'categories');
      $this->Render();
	}

   /**
    * Show all categories and few discussions from each.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Discussions() {
      // Setup head
      $this->AddCssFile('vanilla.css');
      $this->Menu->HighlightRoute('/discussions');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussions.js');
      $this->AddJsFile('options.js');
      $Title = C('Garden.HomepageTitle');
      if ($Title)
         $this->Title($Title, '');
      else
         $this->Title(T('All Categories'));
      $this->Description(C('Garden.Description', NULL));
      Gdn_Theme::Section('DiscussionList');
      
		// Set the category follow toggle before we load category data so that it affects the category query appropriately.
		$CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
		$CategoryFollowToggleModule->SetToggle();
		
      // Get category data and discussions
      $this->DiscussionsPerCategory = C('Vanilla.Discussions.PerCategory', 5);
      $DiscussionModel = new DiscussionModel();
      $this->CategoryModel->Watching = !Gdn::Session()->GetPreference('ShowAllCategories');
      $this->CategoryData = $this->CategoryModel->GetFull();
		$this->SetData('Categories', $this->CategoryData);
      $this->CategoryDiscussionData = array();
      foreach ($this->CategoryData->Result() as $Category) {
			if ($Category->CategoryID > 0)
				$this->CategoryDiscussionData[$Category->CategoryID] = $DiscussionModel->Get(0, $this->DiscussionsPerCategory, array('d.CategoryID' => $Category->CategoryID, 'Announce' => 'all'));
      }
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
		$this->AddModule('DiscussionFilterModule');
      $this->AddModule('CategoriesModule');
      $this->AddModule('BookmarkedModule');
		$this->AddModule($CategoryFollowToggleModule);
      
      // Set view and render
      $this->View = 'discussions';

      $this->CanonicalUrl(Url('/categories', TRUE));
      include_once $this->FetchViewLocation('helper_functions', 'discussions');
      $this->Render();
   }
   
   public function __get($Name) {
      switch ($Name) {
         case 'CategoryData':
//            Deprecated('CategoriesController->CategoryData', "CategoriesController->Data('Categories')");
            $this->CategoryData = new Gdn_DataSet($this->Data('Categories'), DATASET_TYPE_ARRAY);
            $this->CategoryData->DatasetType(DATASET_TYPE_OBJECT);
            return $this->CategoryData;
      }
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
			
		$this->CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
   }      
}