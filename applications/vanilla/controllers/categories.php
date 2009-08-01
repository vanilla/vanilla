<?php if (!defined('APPLICATION')) exit();

/**
 * Vanilla Categories Controller
 */
class CategoriesController extends VanillaController {
   
   public $Uses = array('Database', 'Form', 'Gdn_CategoryModel');
   
   public function Add() {
      $this->Permission('Vanilla.Categories.Manage');
      $RoleModel = new Gdn_RoleModel();
      $PermissionModel = Gdn::PermissionModel();
      $this->Form->SetModel($this->CategoryModel);
      
      if ($this->Head) {
         $this->Head->AddScript('/applications/vanilla/js/categories.js');
         $this->Head->AddScript('/js/library/jquery.gardencheckboxgrid.js');
         $this->Head->Title(Translate('Add Category'));
      }
      $this->AddCssFile('form.css');
      $this->AddCssFile('garden.css');
      $this->AddSideMenu('vanilla/categories/manage');
      
      // Load all roles with editable permissions
      $this->RoleArray = $RoleModel->GetArray();
      
      if (!$this->Form->AuthenticatedPostBack()) {
         $this->Form->SetData(array('AllowDiscussions' => '1')); // Checked by default
      } else {
         $CategoryID = $this->Form->Save();
         if ($CategoryID) {               
            $this->StatusMessage = Gdn::Translate('The category was created successfully.');
            $this->RedirectUrl = Url('vanilla/categories/manage');
         }
      }
      // Get all of the currently selected role/permission combinations for this junction
      $Permissions = $PermissionModel->GetJunctionPermissions(array('JunctionID' => isset($CategoryID) ? $CategoryID : 0), 'Category');
      $Permissions = $PermissionModel->UnpivotPermissions($Permissions, TRUE);
      $this->SetData('PermissionData', $Permissions, TRUE);
      
      $this->Render();      
   }
   
   public function Delete($CategoryID = FALSE) {
      $this->Permission('Vanilla.Categories.Manage');
      if ($this->Head) {
         $this->Head->AddScript('/applications/vanilla/js/categories.js');
         $this->Head->Title(Translate('Delete Category'));
      }

      $this->AddCssFile('form.css');
      $this->AddCssFile('garden.css');
      $this->Category = $this->CategoryModel->GetID($CategoryID);
      $this->AddSideMenu('vanilla/categories/manage');
      
      if (!$this->Category) {
         $this->Form->AddError('The specified category could not be found.');
      } else {
         // Make sure the form knows which item we are deleting.
         $this->Form->AddHidden('CategoryID', $CategoryID);
         
         // Get a list of categories other than this one that can act as a replacement
         $this->OtherCategories = $this->CategoryModel->GetWhere(
            array(
               'CategoryID <>' => $CategoryID,
               'AllowDiscussions' => $this->Category->AllowDiscussions // Don't allow a category with discussion to be the replacement for one without discussions (or vice versa)
            ),
            'Sort'
         );
         
         if (!$this->Form->AuthenticatedPostBack()) {
            $this->Form->SetFormValue('DeleteDiscussions', '1'); // Checked by default
         } else {
            $ReplacementCategoryID = $this->Form->GetValue('ReplacementCategoryID');
            $ReplacementCategory = $this->CategoryModel->GetID($ReplacementCategoryID);
            // Error if:
            // 1. The category being deleted is the last remaining category that
            // allows discussions.
            if ($this->Category->AllowDiscussions == '1'
               && $this->OtherCategories->NumRows() == 0)
               $this->Form->AddError('You cannot remove the only remaining category that allows discussions');
            
            /*
            // 2. The category being deleted allows discussions, and it contains
            // discussions, and there is no replacement category specified.
            if ($this->Form->ErrorCount() == 0
               && $this->Category->AllowDiscussions == '1'
               && $this->Category->CountDiscussions > 0
               && ($ReplacementCategory == FALSE || $ReplacementCategory->AllowDiscussions != '1'))
               $this->Form->AddError('You must select a replacement category in order to remove this category.');
            */
            
            // 3. The category being deleted does not allow discussions, and it
            // does contain other categories, and there are replacement parent
            // categories available, and one is not selected.
            if ($this->Category->AllowDiscussions == '0'
               && $this->OtherCategories->NumRows() > 0
               && !$ReplacementCategory) {
               if ($this->CategoryModel->GetWhere(array('ParentCategoryID' => $CategoryID))->NumRows() > 0)
                  $this->Form->AddError('You must select a replacement category in order to remove this category.');
            }
            
            if ($this->Form->ErrorCount() == 0) {
               // Go ahead and delete the category
               try {
                  $this->CategoryModel->Delete($this->Category, $this->Form->GetValue('ReplacementCategoryID'));
               } catch (Exception $ex) {
                  $this->Form->AddError($ex->getMessage());
               }
               if ($this->Form->ErrorCount() == 0) {
                  $this->RedirectUrl = Url('vanilla/categories/manage');
                  $this->StatusMessage = Gdn::Translate('Deleting category...');
               }
            }
         }
      }
      $this->Render();
   }
   
   public function Edit($CategoryID = '') {
      $this->Permission('Vanilla.Categories.Manage');
      $RoleModel = new Gdn_RoleModel();
      $PermissionModel = Gdn::PermissionModel();
      $this->Form->SetModel($this->CategoryModel);
      $this->Category = $this->CategoryModel->GetID($CategoryID);
      if ($this->Head) {
         $this->Head->AddScript('/js/library/jquery.gardencheckboxgrid.js');
         $this->Head->Title(Translate('Edit Category'));
      }
         
      $this->AddCssFile('form.css');
      $this->AddCssFile('garden.css');
      $this->AddSideMenu('vanilla/categories/manage');
      
      // Make sure the form knows which item we are editing.
      $this->Form->AddHidden('CategoryID', $CategoryID);
      
      // Load all roles with editable permissions
      $this->RoleArray = $RoleModel->GetArray();
      
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         $this->Form->SetData($this->Category);
      } else {
         if ($this->Form->Save()) {
            // Report success
            $this->StatusMessage = Gdn::Translate('The category was saved successfully.');
            $this->RedirectUrl = Url('vanilla/categories/manage');
         }
      }
       
      // Get all of the currently selected role/permission combinations for this junction
      $Permissions = $PermissionModel->GetJunctionPermissions(array('JunctionID' => $CategoryID), 'Category');
      $Permissions = $PermissionModel->UnpivotPermissions($Permissions, TRUE);
      $this->SetData('PermissionData', $Permissions, TRUE);
      
      $this->Render();
   }
   
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
         $Category = $this->CategoryModel->GetFullByName(urldecode($CategoryIdentifier));
      else
         $Category = $this->CategoryModel->GetFull($CategoryIdentifier);
      $this->SetData('Category', $Category, TRUE);
      
      if ($Category === FALSE)
         return $this->All();

      $this->AddCssFile('vanilla.css');
      $this->Menu->HighlightRoute('/discussions');      
      if ($this->Head) {
         $this->Head->Title($Category->Name);
         $this->Head->AddScript('/applications/vanilla/js/discussions.js');
         $this->Head->AddScript('/applications/vanilla/js/options.js');
         $this->Head->AddScript('/js/library/jquery.gardenmorepager.js');
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
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More Discussions';
      $this->Pager->LessCode = 'Newer Discussions';
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
      if ($this->Head) {
         $this->Head->AddScript('/applications/vanilla/js/discussions.js');
         $this->Head->AddScript('/applications/vanilla/js/options.js');
         $this->Head->Title(Translate('All Categories'));
      }
         
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

   public function Manage() {
      $this->Permission('Vanilla.Categories.Manage');
      $this->AddSideMenu('vanilla/categories/manage');
      if ($this->Head) {
         $this->Head->AddScript('/js/library/jquery.tablednd.js');
         $this->Head->AddScript('/js/library/jquery.ui.packed.js');
         $this->Head->AddScript('/applications/vanilla/js/categories.js');
         $this->Head->Title(Translate('Categories'));
      }
      $this->AddCssFile('form.css');
      $this->AddCssFile('garden.css');
      $this->CategoryData = $this->CategoryModel->Get('Sort');
      $this->Render();
   }
   
   public function Sort() {
      $this->Permission('Vanilla.Categories.Manage');
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Success = FALSE;
      if ($this->Form->AuthenticatedPostBack()) {
         $TableID = GetPostValue('TableID', FALSE);
         if ($TableID) {
            $Rows = GetPostValue($TableID, FALSE);
            if (is_array($Rows)) {
               foreach ($Rows as $Sort => $ID) {
                  $this->CategoryModel->Update(array('Sort' => $Sort), array('CategoryID' => $ID));
               }
               // And now call the category model's organize method to make sure
               // orphans appear in the correct place.
               $this->CategoryModel->Organize();
               $Success = TRUE;
            }
         }
      }
      if (!$Success)
         $this->Form->AddError('ErrorBool');
         
      $this->Render();
   }
}