<?php if (!defined('APPLICATION')) exit();

/**
 * Handles displaying categories.
 *
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
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

   public function Archives($Category, $Month, $Page = FALSE) {
      $Category = CategoryModel::Categories($Category);
      if (!$Category)
         throw NotFoundException($Category);

      if (!$Category['PermsDiscussionsView'])
         throw PermissionException();

      $Timestamp = strtotime($Month);
      if (!$Timestamp)
         throw new Gdn_UserException("$Month is not a valid date.");

      $this->SetData('Category', $Category);

      // Round the month to the first day.
      $From = gmdate('Y-m-01', $Timestamp);
      $To = gmdate('Y-m-01', strtotime('+1 month', strtotime($From)));

      // Grab the discussions.
      list($Offset, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
      $Where = array(
         'CategoryID' => $Category['CategoryID'],
         'Announce' => 'all',
         'DateInserted >=' => $From,
         'DateInserted <' => $To);

      SaveToConfig('Vanilla.Discussions.SortField', 'd.DateInserted', FALSE);
      $DiscussionModel = new DiscussionModel();
      $Discussions = $DiscussionModel->GetWhere($Where, $Offset, $Limit);
      $this->DiscussionData = $this->SetData('Discussions', $Discussions);
      $this->SetData('_CurrentRecords', count($Discussions));
      $this->SetData('_Limit', $Limit);

      $Canonical = '/categories/archives/'.rawurlencode($Category['UrlCode']).'/'.gmdate('Y-m', $Timestamp);
      $Page = PageNumber($Offset, $Limit, TRUE, FALSE);
      $this->CanonicalUrl(Url($Canonical.($Page ? '?page='.$Page : ''), TRUE));

      PagerModule::Current()->Configure($Offset, $Limit, FALSE, $Canonical.'?page={Page}');

//      PagerModule::Current()->Offset = $Offset;
//      PagerModule::Current()->Url = '/categories/archives'.rawurlencode($Category['UrlCode']).'?page={Page}';

      Gdn_Theme::Section(GetValue('CssClass', $Category));
      Gdn_Theme::Section('DiscussionList');

      $this->Title(htmlspecialchars(GetValue('Name', $Category, '')));
      $this->Description(sprintf(T("Archives for %s"), gmdate('F Y', strtotime($From))), TRUE);
      $this->AddJsFile('discussions.js');
      $this->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex'));

      $this->ControllerName = 'DiscussionsController';
      $this->CssClass = 'Discussions';

      $this->Render();
   }

   /**
    * "Table" layout for categories. Mimics more traditional forum category layout.
    */
   public function Table($Category = '') {
      if ($this->SyndicationMethod == SYNDICATION_NONE) {
         $this->View = 'table';
      } else
         $this->View = 'all';
      $this->All($Category);
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
      // Figure out which category layout to choose (Defined on "Homepage" settings page).
      $Layout = C('Vanilla.Categories.Layout');

      if ($CategoryIdentifier == '') {
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
         $Category = CategoryModel::Categories($CategoryIdentifier);

         if (empty($Category)) {

            // Try lowercasing before outright failing
            $LowerCategoryIdentifier = strtolower($CategoryIdentifier);
            if ($LowerCategoryIdentifier != $CategoryIdentifier) {
               $Category = CategoryModel::Categories($LowerCategoryIdentifier);
               if ($Category) {
                  Redirect("/categories/{$LowerCategoryIdentifier}", 301);
               }
            }
            throw NotFoundException();
         }
         $Category = (object)$Category;
         Gdn_Theme::Section($Category->CssClass);

         // Load the breadcrumbs.
			$this->SetData('Breadcrumbs', CategoryModel::GetAncestors(GetValue('CategoryID', $Category)));

         $this->SetData('Category', $Category, TRUE);

         $this->Title(htmlspecialchars(GetValue('Name', $Category, '')));
         $this->Description(GetValue('Description', $Category), TRUE);


         if ($Category->DisplayAs == 'Categories') {
            if (GetValue('Depth', $Category) > C('Vanilla.Categories.NavDepth', 0)) {
               // Headings don't make sense if we've cascaded down one level.
               SaveToConfig('Vanilla.Categories.DoHeadings', FALSE, FALSE);
            }

            Trace($this->DeliveryMethod(), 'delivery method');
            Trace($this->DeliveryType(), 'delivery type');
            Trace($this->SyndicationMethod, 'syndication');

            if ($this->SyndicationMethod != SYNDICATION_NONE) {
               // RSS can't show a category list so just tell it to expand all categories.
               SaveToConfig('Vanilla.ExpandCategories', TRUE, FALSE);
            } else {
               // This category is an overview style category and displays as a category list.
               switch($Layout) {
                  case 'mixed':
                     $this->View = 'discussions';
                     $this->Discussions();
                     break;
                  case 'table':
                     $this->Table($CategoryIdentifier);
                     break;
                  default:
                     $this->View = 'all';
                     $this->All($CategoryIdentifier);
                     break;
               }
               return;
            }
         }

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

         // Load the subtree.
         $Categories = CategoryModel::GetSubtree($CategoryIdentifier, false);
         $this->SetData('Categories', $Categories);

         // Setup head
         $this->AddCssFile('vanilla.css');
         $this->Menu->HighlightRoute('/discussions');
         if ($this->Head) {
            $this->AddJsFile('discussions.js');
            $this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->Title());
         }

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
         $CategoryIDs = array($CategoryID);
         if (C('Vanilla.ExpandCategories')) {
            $CategoryIDs = array_merge($CategoryIDs, array_column($this->Data('Categories'), 'CategoryID'));
         }
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

         $Page = PageNumber($Offset, $Limit);

         // Allow page manipulation
         $this->EventArguments['Page'] = &$Page;
         $this->EventArguments['Offset'] = &$Offset;
         $this->EventArguments['Limit'] = &$Limit;
         $this->FireEvent('AfterPageCalculation');

         // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
         $MaxPages = C('Vanilla.Categories.MaxPages');
         if ($MaxPages && $Page > $MaxPages) {
            throw NotFoundException();
         }

         $CountDiscussions = $DiscussionModel->GetCount($Wheres);
         if ($MaxPages && $MaxPages * $Limit < $CountDiscussions) {
            $CountDiscussions = $MaxPages * $Limit;
         }

         $this->SetData('CountDiscussions', $CountDiscussions);
         $this->SetData('_Limit', $Limit);

         // We don't wan't child categories in announcements.
         $Wheres['d.CategoryID'] = $CategoryID;
         $AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements($Wheres) : new Gdn_DataSet();
         $this->SetData('AnnounceData', $AnnounceData, TRUE);
         $Wheres['d.CategoryID'] = $CategoryIDs;

         $this->DiscussionData = $this->SetData('Discussions', $DiscussionModel->GetWhere($Wheres, $Offset, $Limit));

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
            array('CategoryUrl')
         );
         $this->Pager->Record = $Category;
         PagerModule::Current($this->Pager);
         $this->SetData('_Page', $Page);
         $this->SetData('_Limit', $Limit);
         $this->FireEvent('AfterBuildPager');

         // Set the canonical Url.
         $this->CanonicalUrl(CategoryUrl($Category, PageNumber($Offset, $Limit)));

         // Change the controller name so that it knows to grab the discussion views
         $this->ControllerName = 'DiscussionsController';
         // Pick up the discussions class
         $this->CssClass = 'Discussions Category-'.GetValue('UrlCode', $Category);

         // Deliver JSON data if necessary
         if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->SetJson('LessRow', $this->Pager->ToString('less'));
            $this->SetJson('MoreRow', $this->Pager->ToString('more'));
            $this->View = 'discussions';
         }
         // Render default view.
         $this->FireEvent('BeforeCategoriesRender');
         $this->Render();
      }
   }

   /**
    * Show all (nested) categories.
    *
    * @since 2.0.17
    * @access public
    */
   public function All($Category = '') {
      // Setup head.
      $this->Menu->HighlightRoute('/discussions');
      if (!$this->Title()) {
         $Title = C('Garden.HomepageTitle');
         if ($Title)
            $this->Title($Title, '');
         else
            $this->Title(T('All Categories'));
      }
      Gdn_Theme::Section('CategoryList');

      $this->Description(C('Garden.Description', NULL));

      $this->SetData('Breadcrumbs', CategoryModel::GetAncestors(GetValue('CategoryID', $this->Data('Category'))));

      // Set the category follow toggle before we load category data so that it affects the category query appropriately.
      $CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
      $CategoryFollowToggleModule->SetToggle();

      // Get category data
      $this->CategoryModel->Watching = !Gdn::Session()->GetPreference('ShowAllCategories');

      if ($Category) {
         $Subtree = CategoryModel::GetSubtree($Category, false);
         $CategoryIDs = ConsolidateArrayValuesByKey($Subtree, 'CategoryID');
         $Categories = $this->CategoryModel->GetFull($CategoryIDs)->ResultArray();
      } else {
         $Categories = $this->CategoryModel->GetFull()->ResultArray();
      }
      $this->SetData('Categories', $Categories);

      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('BookmarkedModule');
      $this->AddModule($CategoryFollowToggleModule);

      $this->CanonicalUrl(Url('/categories', TRUE));

      $Location = $this->FetchViewLocation('helper_functions', 'categories', FALSE, FALSE);
      if ($Location)
         include_once $Location;
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
      $this->AddJsFile('discussions.js');
      $Title = C('Garden.HomepageTitle');
      if ($Title)
         $this->Title($Title, '');
      else
         $this->Title(T('All Categories'));
      $this->Description(C('Garden.Description', NULL));
      Gdn_Theme::Section('CategoryDiscussionList');

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
      $Path = $this->FetchViewLocation('helper_functions', 'discussions', FALSE, FALSE);
      if ($Path)
         include_once $Path;
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
      if (!C('Vanilla.Categories.Use'))
         Redirect('/discussions');
      if ($this->Menu)
         $this->Menu->HighlightRoute('/categories');

      $this->CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
   }
}
