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
 * Discussions Controller
 *
 * @package Vanilla
 */
 
/**
 * Handles displaying discussions in most contexts.
 *
 * @since 2.0.0
 * @package Vanilla
 * @todo Resolve inconsistency between use of $Page and $Offset as parameters.
 */
class DiscussionsController extends VanillaController {
   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'DiscussionModel', 'Form');
   
   /**
    * A boolean value indicating if discussion options should be displayed when
    * rendering the discussion view.
    *
    * @since 2.0.0
    * @access public
    * @var boolean
    */
   public $ShowOptions;
   
   /**
    * Category object. 
    * 
    * Used to limit which discussion are returned to a particular category.
    * 
    * @since 2.0.0
    * @access public
    * @var object
    */
   public $Category;
   
   /**
    * Unique identifier for category.
    * 
    * @since 2.0.0
    * @access public
    * @var int
    */
   public $CategoryID;
   
   /**
    * Default all discussions view: chronological by most recent comment.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $Page Multiplied by PerPage option to determine offset.
    */
   public function Index($Page = '0') {
      // Determine offset from $Page
      list($Page, $Limit) = OffsetLimit($Page, Gdn::Config('Vanilla.Discussions.PerPage', 30));
      $this->CanonicalUrl(Url(ConcatSep('/', 'discussions', PageNumber($Page, $Limit, TRUE)), TRUE));
      
      // Validate $Page
      if (!is_numeric($Page) || $Page < 0)
         $Page = 0;
      
      // Setup head
		$this->Title(T('All Discussions'));
      if ($this->Head)
         $this->Head->AddRss(Url('/discussions/feed.rss', TRUE), $this->Head->Title());
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Set criteria & get discussions data
      $this->SetData('Category', FALSE, TRUE);
      $DiscussionModel = new DiscussionModel();
      $CountDiscussions = $DiscussionModel->GetCount();
      $this->SetData('CountDiscussions', $CountDiscussions);
      $this->AnnounceData = $Page == 0 ? $DiscussionModel->GetAnnouncements() : FALSE;
		$this->SetData('Announcements', $this->AnnounceData !== FALSE ? $this->AnnounceData : array(), TRUE);
      $this->DiscussionData = $DiscussionModel->Get($Page, $Limit);
      $this->SetData('Discussions', $this->DiscussionData, TRUE);
      $this->SetJson('Loading', $Page . ' to ' . $Limit);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$this->EventArguments['PagerType'] = 'Pager';
		$this->FireEvent('BeforeBuildPager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Page,
         $Limit,
         $CountDiscussions,
         'discussions/%1$s'
      );
		$this->FireEvent('AfterBuildPager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Set a definition of the user's current timezone from the db. jQuery
      // will pick this up, compare to the browser, and update the user's
      // timezone if necessary.
      $CurrentUser = Gdn::Session()->User;
      if (is_object($CurrentUser)) {
         $ClientHour = $CurrentUser->HourOffset + date('G', time());
         $this->AddDefinition('SetClientHour', $ClientHour);
      }
      
      // Render default view (discussions/index.php)
      $this->Render();
   }
   
   /**
    * Highlight route and include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      $this->ShowOptions = TRUE;
      $this->Menu->HighlightRoute('/discussions');
      $this->AddCssFile('vanilla.css');
		$this->AddJsFile('bookmark.js');
		$this->AddJsFile('discussions.js');
		$this->AddJsFile('jquery.menu.js');
		$this->AddJsFile('options.js');
      $this->AddJsFile('jquery.gardenmorepager.js');
		$this->AddModule('SignedInModule');
		$this->FireEvent('AfterInitialize');
   }
   
   /**
    * Display discussions the user has bookmarked.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $Offset Number of discussions to skip.
    */
   public function Bookmarked($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');
      
      // Validate $Offset
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Set criteria & get discussions data
      $Session = Gdn::Session();
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Wheres = array('w.Bookmarked' => '1', 'w.UserID' => $Session->UserID);
      $DiscussionModel = new DiscussionModel();
      $this->DiscussionData = $DiscussionModel->Get($Offset, $Limit, $Wheres);
      $this->SetData('Discussions', $this->DiscussionData);
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $this->SetData('CountDiscussions', $CountDiscussions);
      $this->Category = FALSE;
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$this->EventArguments['PagerType'] = 'MorePager';
		$this->FireEvent('BeforeBuildBookmarkedPager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/bookmarked/%1$s'
      );
		$this->FireEvent('AfterBuildBookmarkedPager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      
      // Render default view (discussions/bookmarked.php)
      $this->Render();
   }
   
   /**
    * Display discussions started by the user.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $Offset Number of discussions to skip.
    */
   public function Mine($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');
      
      // Validate $Offset
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Set criteria & get discussions data
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Session = Gdn::Session();
      $Wheres = array('d.InsertUserID' => $Session->UserID);
      $DiscussionModel = new DiscussionModel();
      $this->DiscussionData = $DiscussionModel->Get($Offset, $Limit, $Wheres);
      $this->SetData('Discussions', $this->DiscussionData);
      $CountDiscussions = $this->SetData('CountDiscussions', $DiscussionModel->GetCount($Wheres));
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$this->EventArguments['PagerType'] = 'MorePager';
		$this->FireEvent('BeforeBuildMinePager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/mine/%1$s'
      );
		$this->FireEvent('AfterBuildMinePager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Render default view (discussions/mine.php)
      $this->Render();
   }
}