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
 * Discussions Controller - handles displaying discussions in all their forms.
 */
class DiscussionsController extends VanillaController {
   
   public $Uses = array('Database', 'DiscussionModel', 'Form');
   
   /**
    * A boolean value indicating if discussion options should be displayed when
    * rendering the discussion view.
    *
    * @var boolean
    */
   public $ShowOptions;
   public $Category;
   public $CategoryID;
   
   public function Index($Offset = '0') {
      list($Offset, $Limit) = OffsetLimit($Offset, Gdn::Config('Vanilla.Discussions.PerPage', 30));
      $this->CanonicalUrl(Url(ConcatSep('/', 'discussions', PageNumber($Offset, $Limit, TRUE)), TRUE));

		$this->Title(T('All Discussions'));
      if ($this->Head)
         $this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->Title());

      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);

      $this->SetData('Category', FALSE, TRUE);
      $DiscussionModel = new DiscussionModel();
      $CountDiscussions = $DiscussionModel->GetCount();
      $this->SetData('CountDiscussions', $CountDiscussions);
      $this->AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements() : FALSE;
		$this->SetData('Announcements', $this->AnnounceData !== FALSE ? $this->AnnounceData : array(), TRUE);
      $this->DiscussionData = $DiscussionModel->Get($Offset, $Limit);
      $this->SetData('Discussions', $this->DiscussionData, TRUE);
      $this->SetJson('Loading', $Offset . ' to ' . $Limit);

      // Build a pager.
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('Pager', $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/%1$s'
      );
      
      // Deliver json data if necessary
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
      
      // Render the controller
      $this->Render();
   }
   
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
		$this->FireEvent('AfterInitialize');
   }
   
   public function Bookmarked($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');

      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
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
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/bookmarked/%1$s'
      );
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      
      $this->Render();
   }
   
   public function Mine($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');

      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Session = Gdn::Session();
      $Wheres = array('d.InsertUserID' => $Session->UserID);
      $DiscussionModel = new DiscussionModel();
      $this->DiscussionData = $DiscussionModel->Get($Offset, $Limit, $Wheres);
      $this->SetData('Discussions', $this->DiscussionData);
      $CountDiscussions = $this->SetData('CountDiscussions', $DiscussionModel->GetCount($Wheres));
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/mine/%1$s'
      );
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Render the controller
      $this->Render();
   }
}