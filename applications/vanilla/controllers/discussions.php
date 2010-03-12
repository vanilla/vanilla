<?php if (!defined('APPLICATION')) exit();

/**
 * Discussions Controller
 */
class DiscussionsController extends VanillaController {
   
   public $Uses = array('Database', 'Gdn_DiscussionModel', 'Form');
   
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
      if ($this->Head) {
         $this->AddJsFile('discussions.js');
         $this->AddJsFile('bookmark.js');
         $this->AddJsFile('options.js');
         $this->Head->AddRss('/rss/'.$this->SelfUrl, $this->Head->Title());
         $this->Head->Title(Translate('All Discussions'));
      }
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);

      $this->SetData('Category', FALSE, TRUE);
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new Gdn_DiscussionModel();
      $CountDiscussions = $DiscussionModel->GetCount();
      $this->SetData('CountDiscussions', $CountDiscussions);
         
      $TmpLimit = $Limit;
      $AnnounceData = FALSE;
      if ($Offset == 0) {
         $AnnounceData = $DiscussionModel->GetAnnouncements();
         $TmpLimit = $Limit - $AnnounceData->NumRows();
         if ($TmpLimit <= 0)
            $TmpLimit = 1;
      }
      $this->SetJson('Loading', $Offset . ' to ' . $TmpLimit);
      $this->SetData('AnnounceData', $AnnounceData, TRUE);
      
      $this->SetData('DiscussionData', $DiscussionModel->Get($Offset, $TmpLimit), TRUE);

      // Build a pager.
      $PagerFactory = new PagerFactory();
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
      $this->AddJsFile('/js/library/jquery.gardenmorepager.js');
   }
   
   public function Bookmarked($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');
      $this->AddJsFile('options.js');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussions.js');
      $this->Title(Translate('My Bookmarks'));

      // $this->AddToolbar();            
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Session = Gdn::Session();
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Wheres = array('w.Bookmarked' => '1', 'w.UserID' => $Session->UserID);
      $DiscussionModel = new Gdn_DiscussionModel();
      $this->DiscussionData = $DiscussionModel->Get($Offset, $Limit, $Wheres);
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $this->Category = FALSE;
      
      // Build a pager
      $PagerFactory = new PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
      $this->Pager->Wrapper = '<li %1$s>%2$s</li>';
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
      $this->AddJsFile('/js/library/jquery.resizable.js');
      $this->AddJsFile('/js/library/jquery.ui.packed.js');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussions.js');
      $this->AddJsFile('options.js');
      $this->Title(Translate('My Discussions'));

      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Session = Gdn::Session();
      $Wheres = array('d.InsertUserID' => $Session->UserID);
      $DiscussionModel = new Gdn_DiscussionModel();
      $this->SetData('DiscussionData', $DiscussionModel->Get($Offset, $Limit, $Wheres), TRUE);
      $CountDiscussions = $this->SetData('CountDiscussions', $DiscussionModel->GetCount($Wheres));
      
      // Build a pager
      $PagerFactory = new PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
      $this->Pager->Wrapper = '<li %1$s>%2$s</li>';
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