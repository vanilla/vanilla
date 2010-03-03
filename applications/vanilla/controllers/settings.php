<?php if (!defined('APPLICATION')) exit();

/**
 * Settings Controller
 */
class SettingsController extends Gdn_Controller {
   
   public $Uses = array('Database', 'Form', 'Gdn_CategoryModel');
   
   public function Index() {
      $this->View = 'general';
      $this->General();
   }
   
   public function General() {
      $this->Permission('Vanilla.Settings.Manage');
      $this->AddSideMenu('vanilla/settings/general');
      $this->Title(Translate('Forum Settings'));

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Vanilla.Discussions.PerPage',
         'Vanilla.Comments.AutoRefresh',
         'Vanilla.Comments.PerPage',
         'Vanilla.Categories.Use'
      ));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussions.PerPage', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussions.PerPage', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comments.AutoRefresh', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comments.PerPage', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comments.PerPage', 'Integer');
         
         if ($this->Form->Save() !== FALSE)
            $this->StatusMessage = Translate("Your changes have been saved.");

      }
      
      $this->Render();
   }
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      if (in_array($this->ControllerName, array('profilecontroller', 'activitycontroller'))) {
         $this->AddJsFile('jquery.menu.js');
         $this->AddCssFile('style.css');
      } else {
         $this->AddCssFile('admin.css');
      }
      
      $this->MasterView = 'admin';
      parent::Initialize();
   }   
   
   public function AddSideMenu($CurrentUrl) {
      // Only add to the assets if this is not a view-only request
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
         $SideMenu = new Gdn_SideMenuModule($this);
         $SideMenu->HtmlId = '';
         $SideMenu->HighlightRoute($CurrentUrl);
         $this->EventArguments['SideMenu'] = &$SideMenu;
         $this->FireEvent('GetAppSettingsMenuItems');
         $this->AddModule($SideMenu, 'Panel');
      }
   }

   public function Spam() {
      $this->Title(Translate('Spam'));
      $this->Permission('Vanilla.Spam.Manage');
      $this->AddSideMenu('vanilla/settings/spam');
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Vanilla.Discussion.SpamCount',
         'Vanilla.Discussion.SpamTime',
         'Vanilla.Discussion.SpamLock',
         'Vanilla.Comment.SpamCount',
         'Vanilla.Comment.SpamTime',
         'Vanilla.Comment.SpamLock',
         'Vanilla.Comment.MaxLength'
      ));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamCount', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamCount', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamTime', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamTime', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamLock', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamLock', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamCount', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamCount', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamTime', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamTime', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamLock', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamLock', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.MaxLength', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.MaxLength', 'Integer');
         
         if ($this->Form->Save() !== FALSE) {
            $this->StatusMessage = Translate("Your changes have been saved.");
         }
      }
      
      $this->Render();
   }
   
   public function AddCategory() {
      $this->Permission('Vanilla.Categories.Manage');
      $RoleModel = new Gdn_RoleModel();
      $PermissionModel = Gdn::PermissionModel();
      $this->Form->SetModel($this->CategoryModel);
      
      $this->AddJsFile('categories.js');
      $this->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $this->Title(Translate('Add Category'));
      $this->AddSideMenu('vanilla/settings/managecategories');
      
      // Load all roles with editable permissions
      $this->RoleArray = $RoleModel->GetArray();
      
      if (!$this->Form->AuthenticatedPostBack()) {
         $this->Form->SetData(array('AllowDiscussions' => '1')); // Checked by default
      } else {
         $CategoryID = $this->Form->Save();
         if ($CategoryID) {               
            $this->StatusMessage = Gdn::Translate('The category was created successfully.');
            $this->RedirectUrl = Url('vanilla/settings/managecategories');
         } else {
				unset($CategoryID);
			}
      }
      // Get all of the currently selected role/permission combinations for this junction
      $Permissions = $PermissionModel->GetJunctionPermissions(array('JunctionID' => isset($CategoryID) ? $CategoryID : 0), 'Category');
      $Permissions = $PermissionModel->UnpivotPermissions($Permissions, TRUE);
      $this->SetData('PermissionData', $Permissions, TRUE);
      
      $this->Render();      
   }
   
   public function DeleteCategory($CategoryID = FALSE) {
      $this->Permission('Vanilla.Categories.Manage');
      $this->AddJsFile('categories.js');
      $this->Title(Translate('Delete Category'));

      $this->Category = $this->CategoryModel->GetID($CategoryID);
      $this->AddSideMenu('vanilla/settings/managecategories');
      
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
                  $this->RedirectUrl = Url('vanilla/settings/managecategories');
                  $this->StatusMessage = Gdn::Translate('Deleting category...');
               }
            }
         }
      }
      $this->Render();
   }
   
   public function EditCategory($CategoryID = '') {
      $this->Permission('Vanilla.Categories.Manage');
      $RoleModel = new Gdn_RoleModel();
      $PermissionModel = Gdn::PermissionModel();
      $this->Form->SetModel($this->CategoryModel);
      $this->Category = $this->CategoryModel->GetID($CategoryID);
      $this->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $this->Title(Translate('Edit Category'));
         
      $this->AddSideMenu('vanilla/settings/managecategories');
      
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
            $this->RedirectUrl = Url('vanilla/settings/managecategories');
         }
      }
       
      // Get all of the currently selected role/permission combinations for this junction
      $Permissions = $PermissionModel->GetJunctionPermissions(array('JunctionID' => $CategoryID), 'Category');
      $Permissions = $PermissionModel->UnpivotPermissions($Permissions, TRUE);
      $this->SetData('PermissionData', $Permissions, TRUE);
      
      $this->Render();
   }
   
   public function ManageCategories() {
      $this->Permission('Vanilla.Categories.Manage');
      $this->AddSideMenu('vanilla/settings/managecategories');
      $this->AddJsFile('categories.js');
      $this->AddJsFile('jquery.tablednd.js');
      $this->AddJsFile('jquery.ui.packed.js');
      $this->Title(Translate('Categories'));
      $this->CategoryData = $this->CategoryModel->Get('Sort');
      $this->Render();
   }
   
   public function SortCategories() {
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