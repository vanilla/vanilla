<?php if (!defined('APPLICATION')) exit();

/**
 * Vanilla Categories Controller
 */
class CategoriesController extends VanillaController {
   
   public $Uses = array('Database', 'Form', 'Gdn_CategoryModel');
   
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
         $this->AddJsFile('options.js');
         $this->AddJsFile('/js/library/jquery.gardenmorepager.js');
         $this->Head->AddRss('/rss/'.$this->SelfUrl, $this->Head->Title());
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
      $DraftsModule = new DraftsModule($this);
      $DraftsModule->GetData();
      $this->AddModule($DraftsModule);

      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new Gdn_DiscussionModel();
      $Wheres = array('d.CategoryID' => $this->CategoryID);
      
      $this->Permission('Vanilla.Discussions.View', $this->CategoryID);
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $this->SetData('CountDiscussions', $CountDiscussions);
         
      $TmpLimit = $Limit;
      $AnnounceData = FALSE;
      if ($Offset == 0) {
         $AnnounceData = $DiscussionModel->GetAnnouncements($Wheres);
         $TmpLimit = $Limit - $AnnounceData->NumRows();
      }
      $this->SetData('AnnounceData', $AnnounceData, TRUE);
      
      $this->SetData('DiscussionData', $DiscussionModel->Get($Offset, $TmpLimit, $Wheres), TRUE);

      // Build a pager
      $PagerFactory = new PagerFactory();
      $this->Pager = $PagerFactory->GetPager('Pager', $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'categories/'.$CategoryIdentifier.'/%1$s'
      );
      
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
      $this->AddJsFile('discussions.js');
      $this->AddJsFile('options.js');
      $this->Title(Translate('All Categories'));
         
      $this->DiscussionsPerCategory = Gdn::Config('Vanilla.Discussions.PerCategory', 5);
      $DiscussionModel = new Gdn_DiscussionModel();
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
      $DraftsModule = new DraftsModule($this);
      $DraftsModule->GetData();
      $this->AddModule($DraftsModule);
      
      $this->View = 'all';
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }      
}