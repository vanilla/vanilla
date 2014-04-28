<?php if (!defined('APPLICATION')) exit();

/**
 * Handles displaying discussions in most contexts.
 *
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
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
    * "Table" layout for discussions. Mimics more traditional forum discussion layout.
    * 
    * @param int $Page Multiplied by PerPage option to determine offset.
    */
   public function Table($Page = '0') {
      if ($this->SyndicationMethod == SYNDICATION_NONE)
         $this->View = 'table';
      $this->Index($Page);
   }
   
   /**
    * Default all discussions view: chronological by most recent comment.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $Page Multiplied by PerPage option to determine offset.
    */
   public function Index($Page = FALSE) {
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
      Gdn_Theme::Section('DiscussionList');
      
      // Determine offset from $Page
      list($Offset, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
      $Page = PageNumber($Offset, $Limit);
      $this->CanonicalUrl(Url(ConcatSep('/', 'discussions', PageNumber($Offset, $Limit, TRUE, FALSE)), TRUE));
      
      // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
      $MaxPages = C('Vanilla.Discussions.MaxPages');
      if ($MaxPages && $Page > $MaxPages) {
         throw NotFoundException();
      }
      
      // Setup head.
      if (!$this->Data('Title')) {
         $Title = C('Garden.HomepageTitle');
         if ($Title)
            $this->Title($Title, '');
         else
            $this->Title(T('Recent Discussions'));
      }
      if (!$this->Description())
         $this->Description(C('Garden.Description', NULL));
      if ($this->Head)
         $this->Head->AddRss(Url('/discussions/feed.rss', TRUE), $this->Head->Title());
      
      // Add modules
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $this->AddModule('BookmarkedModule');
      $this->SetData('Breadcrumbs', array(array('Name' => T('Recent Discussions'), 'Url' => '/discussions')));
      
      
      // Set criteria & get discussions data
      $this->SetData('Category', FALSE, TRUE);
      $DiscussionModel = new DiscussionModel();
      $DiscussionModel->Watching = TRUE;
      
      // Get Discussion Count
      $CountDiscussions = $DiscussionModel->GetCount();
      
      if ($MaxPages)
         $CountDiscussions = $MaxPages * $Limit;
      
      $this->SetData('CountDiscussions', $CountDiscussions);
      
      // Get Announcements
      $this->AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements() : FALSE;
		$this->SetData('Announcements', $this->AnnounceData !== FALSE ? $this->AnnounceData : array(), TRUE);
      
      // Get Discussions
      $this->DiscussionData = $DiscussionModel->GetWhere(FALSE, $Offset, $Limit);
      
      $this->SetData('Discussions', $this->DiscussionData, TRUE);
      $this->SetJson('Loading', $Offset . ' to ' . $Limit);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$this->EventArguments['PagerType'] = 'Pager';
		$this->FireEvent('BeforeBuildPager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/%1$s'
      );
      if (!$this->Data('_PagerUrl'))
         $this->SetData('_PagerUrl', 'discussions/{Page}');
      $this->SetData('_Page', $Page);
      $this->SetData('_Limit', $Limit);
		$this->FireEvent('AfterBuildPager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      $this->Render();
   }
   
   public function Unread($Page = '0') {
      if (!Gdn::Session()->IsValid())
         Redirect('/discussions/index', 302);
      
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
      Gdn_Theme::Section('DiscussionList');
      
      // Determine offset from $Page
      list($Page, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
      $this->CanonicalUrl(Url(ConcatSep('/', 'discussions', 'unread', PageNumber($Page, $Limit, TRUE, FALSE)), TRUE));
      
      // Validate $Page
      if (!is_numeric($Page) || $Page < 0)
         $Page = 0;
      
      // Setup head.
      if (!$this->Data('Title')) {
         $Title = C('Garden.HomepageTitle');
         if ($Title)
            $this->Title($Title, '');
         else
            $this->Title(T('Unread Discussions'));
      }
      if (!$this->Description())
         $this->Description(C('Garden.Description', NULL));
      if ($this->Head)
         $this->Head->AddRss(Url('/discussions/unread/feed.rss', TRUE), $this->Head->Title());
      
      // Add modules
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $this->AddModule('BookmarkedModule');
      $this->SetData('Breadcrumbs', array(
          array('Name' => T('Discussions'), 'Url' => '/discussions'),
          array('Name' => T('Unread'), 'Url' => '/discussions/unread')
      ));
      
      
      // Set criteria & get discussions data
      $this->SetData('Category', FALSE, TRUE);
      $DiscussionModel = new DiscussionModel();
      $DiscussionModel->Watching = TRUE;
      
      // Get Discussion Count
      $CountDiscussions = $DiscussionModel->GetUnreadCount();
      $this->SetData('CountDiscussions', $CountDiscussions);
      
      // Get Discussions
      $this->DiscussionData = $DiscussionModel->GetUnread($Page, $Limit);
      
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
         'discussions/unread/%1$s'
      );
      if (!$this->Data('_PagerUrl'))
         $this->SetData('_PagerUrl', 'discussions/unread/{Page}');
      $this->SetData('_Page', $Page);
      $this->SetData('_Limit', $Limit);
		$this->FireEvent('AfterBuildPager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
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
		$this->AddJsFile('discussions.js');
			
		// Inform moderator of checked comments in this discussion
		$CheckedDiscussions = Gdn::Session()->GetAttribute('CheckedDiscussions', array());
		if (count($CheckedDiscussions) > 0)
			ModerationController::InformCheckedDiscussions($this);
         
      $this->CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
         
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
   public function Bookmarked($Page = '0') {
      $this->Permission('Garden.SignIn.Allow');
      Gdn_Theme::Section('DiscussionList');

      // Figure out which discussions layout to choose (Defined on "Homepage" settings page).
      $Layout = C('Vanilla.Discussions.Layout');
      switch($Layout) {
         case 'table':
            if ($this->SyndicationMethod == SYNDICATION_NONE)
               $this->View = 'table';
            break;
         default:
            $this->View = 'index';
            break;
      }
      
      // Determine offset from $Page
      list($Page, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
      $this->CanonicalUrl(Url(ConcatSep('/', 'discussions', 'bookmarked', PageNumber($Page, $Limit, TRUE, FALSE)), TRUE));
      
      // Validate $Page
      if (!is_numeric($Page) || $Page < 0)
         $Page = 0;
      
      $DiscussionModel = new DiscussionModel();
      $Wheres = array(
         'w.Bookmarked' => '1', 
         'w.UserID' => Gdn::Session()->UserID
      );
      
      $this->DiscussionData = $DiscussionModel->Get($Page, $Limit, $Wheres);
      $this->SetData('Discussions', $this->DiscussionData);
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $this->SetData('CountDiscussions', $CountDiscussions);
      $this->Category = FALSE;
      
      $this->SetJson('Loading', $Page . ' to ' . $Limit);
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$this->EventArguments['PagerType'] = 'Pager';
		$this->FireEvent('BeforeBuildBookmarkedPager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Page,
         $Limit,
         $CountDiscussions,
         'discussions/bookmarked/%1$s'
      );
      
      if (!$this->Data('_PagerUrl'))
         $this->SetData('_PagerUrl', 'discussions/bookmarked/{Page}');
      $this->SetData('_Page', $Page);
      $this->SetData('_Limit', $Limit);
		$this->FireEvent('AfterBuildBookmarkedPager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Add modules
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      
      // Render default view (discussions/bookmarked.php)
      $this->SetData('Title', T('My Bookmarks'));
		$this->SetData('Breadcrumbs', array(array('Name' => T('My Bookmarks'), 'Url' => '/discussions/bookmarked')));
      $this->Render();
   }
   
   public function BookmarkedPopin() {
      $this->Permission('Garden.SignIn.Allow');
      
      $DiscussionModel = new DiscussionModel();
      $Wheres = array(
         'w.Bookmarked' => '1', 
         'w.UserID' => Gdn::Session()->UserID
      );
      
      $Discussions = $DiscussionModel->Get(0, 5, $Wheres)->Result();
      $this->SetData('Title', T('Bookmarks'));
      $this->SetData('Discussions', $Discussions);
      $this->Render('Popin');
   }
   
   /**
    * Display discussions started by the user.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $Offset Number of discussions to skip.
    */
   public function Mine($Page = 'p1') {
      $this->Permission('Garden.SignIn.Allow');
      Gdn_Theme::Section('DiscussionList');
      
      // Set criteria & get discussions data
      list($Offset, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
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

      $this->SetData('_PagerUrl', 'discussions/mine/{Page}');
      $this->SetData('_Page', $Page);
      $this->SetData('_Limit', $Limit);

		$this->FireEvent('AfterBuildMinePager');
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'discussions';
      }
      
      // Add modules
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $this->AddModule('BookmarkedModule');
      
      // Render default view (discussions/mine.php)
      $this->SetData('Title', T('My Discussions'));
      $this->SetData('Breadcrumbs', array(array('Name' => T('My Discussions'), 'Url' => '/discussions/mine')));
      $this->Render('Index');
   }

   public function UserBookmarkCount($UserID = FALSE) {
      if ($UserID === FALSE) {
         $UserID = Gdn::Session()->UserID;
      }

      if (!$UserID) {
         $CountBookmarks = NULL;
      } else {
         if ($UserID == Gdn::Session() && isset(Gdn::Session()->User->CountBookmarks)) {
            $CountBookmarks = Gdn::Session()->User->CountBookmarks;
         } else {
            $UserModel = new UserModel();
            $User = $UserModel->GetID($ID, DATASET_TYPE_ARRAY);
            $CountBookmarks = $User['CountBookmarks'];
         }

         if ($CountBookmarks === NULL) {
            $CountBookmarks = Gdn::SQL()
               ->Select('DiscussionID', 'count', 'CountBookmarks')
               ->From('UserDiscussion')
               ->Where('Bookmarked', '1')
               ->Where('UserID', $UserID)
               ->Get()->Value('CountBookmarks', 0);

            Gdn::UserModel()->SetField($UserID, 'CountBookmarks', $CountBookmarks);
         }
      }
      $this->SetData('CountBookmarks', $CountBookmarks);
      $this->SetData('_Value', $CountBookmarks);
      $this->xRender('Value', 'utility', 'dashboard');
   }
	
	/**
	 * Takes a set of discussion identifiers and returns their comment counts in the same order.
	 */
	public function GetCommentCounts() {
      $this->AllowJSONP(TRUE);
      
		$vanilla_identifier = GetValue('vanilla_identifier', $_GET);
		if (!is_array($vanilla_identifier))
			$vanilla_identifier = array($vanilla_identifier);
         
      $vanilla_identifier = array_unique($vanilla_identifier);
      
      $FinalData = array_fill_keys($vanilla_identifier, 0);
      $Misses = array();
      $CacheKey = 'embed.comments.count.%s';
      $OriginalIDs = array();
      foreach ($vanilla_identifier as $ForeignID) {
         $HashedForeignID = ForeignIDHash($ForeignID);
         
         // Keep record of non-hashed identifiers for the reply
         $OriginalIDs[$HashedForeignID] = $ForeignID;
         
         $RealCacheKey = sprintf($CacheKey, $HashedForeignID);
         $Comments = Gdn::Cache()->Get($RealCacheKey);
         if ($Comments !== Gdn_Cache::CACHEOP_FAILURE)
            $FinalData[$ForeignID] = $Comments;
         else
            $Misses[] = $HashedForeignID;
      }
      
      if (sizeof($Misses)) {
         $CountData = Gdn::SQL()
            ->Select('ForeignID, CountComments')
            ->From('Discussion')
            ->Where('Type', 'page')
            ->WhereIn('ForeignID', $Misses)
            ->Get()->ResultArray();
         
         foreach ($CountData as $Row) {
            // Get original identifier to send back
            $ForeignID = $OriginalIDs[$Row['ForeignID']];
            $FinalData[$ForeignID] = $Row['CountComments'];
            
            // Cache using the hashed identifier
            $RealCacheKey = sprintf($CacheKey, $Row['ForeignID']);
            Gdn::Cache()->Store($RealCacheKey, $Row['CountComments'], array(
               Gdn_Cache::FEATURE_EXPIRY     => 60
            ));
         }         
      }
      
		$this->SetData('CountData', $FinalData);
		$this->DeliveryMethod = DELIVERY_METHOD_JSON;
		$this->DeliveryType = DELIVERY_TYPE_DATA;
		$this->Render();
	}
}
