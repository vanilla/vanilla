<?php
/**
 * Settings controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying the dashboard "settings" pages for Vanilla via /settings endpoint.
 *
 * @todo Remove this controller in favor of Dashboard's settings controller.
 */
class SettingsController extends Gdn_Controller {

    /** @var array Models to include. */
    public $Uses = array('Database', 'Form', 'CategoryModel');

    /** @var Gdn_Form */
    public $Form;

    /** @var bool */
    public $ShowCustomPoints = false;

    /**
     * Advanced settings.
     *
     * Allows setting configuration values via form elements.
     *
     * @since 2.0.0
     * @access public
     */
    public function advanced() {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        // Load up config options we'll be setting
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array(
            'Vanilla.Discussions.PerPage',
            'Vanilla.Comments.AutoRefresh',
            'Vanilla.Comments.PerPage',
            'Garden.Html.AllowedElements',
            'Vanilla.Archive.Date',
            'Vanilla.Archive.Exclude',
            'Garden.EditContentTimeout',
            'Vanilla.AdminCheckboxes.Use',
            'Vanilla.Discussions.SortField' => 'd.DateLastComment',
            'Vanilla.Discussions.UserSortField',
            'Vanilla.Comment.MaxLength',
            'Vanilla.Comment.MinLength'
        ));

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussions.PerPage', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussions.PerPage', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comments.AutoRefresh', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comments.PerPage', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comments.PerPage', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Archive.Date', 'Date');
            $ConfigurationModel->Validation->applyRule('Garden.EditContentTimeout', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.MaxLength', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.MaxLength', 'Integer');

            // Grab old config values to check for an update.
            $ArchiveDateBak = c('Vanilla.Archive.Date');
            $ArchiveExcludeBak = (bool)c('Vanilla.Archive.Exclude');

            // Save new settings
            $Saved = $this->Form->save();
            if ($Saved) {
                $ArchiveDate = c('Vanilla.Archive.Date');
                $ArchiveExclude = (bool)c('Vanilla.Archive.Exclude');

                if ($ArchiveExclude != $ArchiveExcludeBak || ($ArchiveExclude && $ArchiveDate != $ArchiveDateBak)) {
                    $DiscussionModel = new DiscussionModel();
                    $DiscussionModel->updateDiscussionCount('All');
                }
                $this->informMessage(t("Your changes have been saved."));
            }
        }

        $this->addSideMenu('settings/advanced');
        $this->addJsFile('settings.js');
        $this->title(t('Advanced Forum Settings'));

        // Render default view (settings/advanced.php)
        $this->render();
    }

    /**
     * Alias for ManageCategories method.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->View = 'managecategories';
        $this->manageCategories();
    }

    /**
     * Switch MasterView. Include JS, CSS used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.atwho.js');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('global.js');

        if (in_array($this->ControllerName, array('profilecontroller', 'activitycontroller'))) {
            $this->addCssFile('style.css');
            $this->addCssFile('vanillicon.css', 'static');
        } else {
            $this->addCssFile('admin.css');
        }

        // Change master template
        $this->MasterView = 'admin';
        parent::initialize();
        Gdn_Theme::section('Dashboard');
    }

    /**
     * Configures navigation sidebar in Dashboard.
     *
     * @since 2.0.0
     * @access public
     *
     * @param $CurrentUrl Path to current location in dashboard.
     */
    public function addSideMenu($CurrentUrl) {
        // Only add to the assets if this is not a view-only request
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $SideMenu = new SideMenuModule($this);
            $SideMenu->HtmlId = '';
            $SideMenu->highlightRoute($CurrentUrl);
            $SideMenu->Sort = c('Garden.DashboardMenu.Sort');
            $this->EventArguments['SideMenu'] = &$SideMenu;
            $this->fireEvent('GetAppSettingsMenuItems');
            $this->addModule($SideMenu, 'Panel');
        }
    }

    /**
     * Display flood control options.
     *
     * @since 2.0.0
     * @access public
     */
    public function floodControl() {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        // Display options
        $this->title(t('Flood Control'));
        $this->addSideMenu('settings/floodcontrol');

        // Define what configuration settings we'll be using.
        $contexts = ['Vanilla.Discussion', 'Vanilla.Comment'];
        $settings = ['SpamCount', 'SpamTime', 'SpamLock'];

        // If Conversations is enabled, add its contexts.
        // Ideally we'd refactor this into an event and hook.
        $conversationsEnabled = Gdn::applicationManager()->checkApplication('Conversations');
        if ($conversationsEnabled) {
            $contexts[] = 'Conversations.Conversation';
            $contexts[] = 'Conversations.ConversationMessage';
        }

        // Build our list of configuration fields.
        $ConfigurationFields = array();
        foreach ($contexts as $context) {
            foreach ($settings as $setting) {
                $ConfigurationFields[] = $context.'.'.$setting;
            }
        }

        // Load up config options we'll be setting
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField($ConfigurationFields);

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // Submitted form.
        if ($this->Form->authenticatedPostBack()) {
            // Apply 'required' and 'integer' validation rules to all our spam settings in all contexts.
            foreach ($contexts as $context) {
                foreach ($settings as $setting) {
                    $ConfigurationModel->Validation->applyRule($context.'.'.$setting, 'Required');
                    $ConfigurationModel->Validation->applyRule($context.'.'.$setting, 'Integer');
                }
            }

            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your changes have been saved."));
            }
        } else {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        }

        // Render default view
        $this->render();
    }

    /**
     * Adding a new category.
     *
     * @since 2.0.0
     * @access public
     */
    public function addCategory() {
        // Check permission
        $this->permission('Garden.Community.Manage');

        $this->ShowCustomPoints = false;

        // Set up head
        $this->addJsFile('jquery.alphanumeric.js');
        $this->addJsFile('categories.js');
        $this->addJsFile('jquery.gardencheckboxgrid.js');
        $this->title(t('Add Category'));
        $this->addSideMenu('settings/managecategories');

        // Prep models
        $RoleModel = new RoleModel();
        $PermissionModel = Gdn::permissionModel();
        $categoryModel = new CategoryModel();
        $this->Form->setModel($categoryModel);

        // Load all roles with editable permissions.
        $this->RoleArray = $RoleModel->getArray();

        $this->fireEvent('AddEditCategory');
        $this->setupDiscussionTypes(array());

        if ($this->Form->authenticatedPostBack()) {
            // Form was validly submitted
            $IsParent = $this->Form->getFormValue('IsParent', '0');
            $this->Form->setFormValue('AllowDiscussions', $IsParent == '1' ? '0' : '1');
            $this->Form->setFormValue('CustomPoints', (bool)$this->Form->getFormValue('CustomPoints'));
            $CategoryID = $this->Form->save();
            if ($CategoryID) {
                $Category = CategoryModel::categories($CategoryID);
                $this->setData('Category', $Category);

                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirect('settings/managecategories');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_DATA && method_exists($this, 'getCategory')) {
                    $this->Data = [];
                    $this->getCategory($CategoryID);
                    return;
                }
            } else {
                unset($CategoryID);
            }
        } else {
            $this->Form->addHidden('CodeIsDefined', '0');
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $Permissions = $PermissionModel->getJunctionPermissions(array('JunctionID' => isset($CategoryID) ? $CategoryID : 0), 'Category');
        $Permissions = $PermissionModel->unpivotPermissions($Permissions, true);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->setData('PermissionData', $Permissions, true);
        }

        // Render default view
        $this->render();
    }

    /**
     * Get a single category for administration.
     *
     * This endpoint is intended for API access.
     *
     * @param int $categoryID The category to find.
     */
    public function getCategory($categoryID) {
        // Check permission
        $this->permission('Garden.Community.Manage');

        if (!$categoryID) {
            throw new Gdn_UserException(sprintf(t('ValidationRequired'), 'CategoryID'));
        }

        $categoryModel = new CategoryModel();
        $category = $categoryModel->getID($categoryID, DATASET_TYPE_ARRAY);

        if (!$category) {
            throw notFoundException('Category');
        }

        // Add the permissions for the category.
        if ($category['PermissionCategoryID'] == $category['CategoryID']) {
            $category['Permissions'] = $categoryModel->getRolePermissions($categoryID);
        } else {
            $category['Permissions'] = null;
        }

        $this->setData('Category', $category);
        saveToConfig('Api.Clean', false, false);
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Deleting a category.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CategoryID Unique ID of the category to be deleted.
     */
    public function deleteCategory($CategoryID = false) {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        // Set up head
        $this->addJsFile('categories.js');
        $this->title(t('Delete Category'));
        $this->addSideMenu('settings/managecategories');

        // Get category data
        $categoryModel = new CategoryModel();
        $this->Category = $categoryModel->getID($CategoryID);

        if (!$this->Category) {
            $this->Form->addError('The specified category could not be found.');
        } else {
            // Make sure the form knows which item we are deleting.
            $this->Form->addHidden('CategoryID', $CategoryID);

            // Get a list of categories other than this one that can act as a replacement
            $this->OtherCategories = $categoryModel->getWhere(
                array(
                    'CategoryID <>' => $CategoryID,
                    // Don't allow a category with discussion to be the replacement for one without discussions (or vice versa)
                    'AllowDiscussions' => $this->Category->AllowDiscussions,
                    'CategoryID >' => 0
                ),
                'Sort'
            );

            if (!$this->Form->authenticatedPostBack()) {
                $this->Form->setFormValue('DeleteDiscussions', '1'); // Checked by default
            } else {
                $ReplacementCategoryID = $this->Form->getValue('ReplacementCategoryID');

                // Error if category being deleted is the last remaining category that allows discussions.
                if ($this->Category->AllowDiscussions == '1' && $this->OtherCategories->numRows() == 0) {
                    $this->Form->addError('You cannot remove the only remaining category that allows discussions');
                }

                if ($this->Form->errorCount() == 0) {
                    // Go ahead and delete the category
                    try {
                        $categoryModel->delete($this->Category, $this->Form->getValue('ReplacementCategoryID'));
                    } catch (Exception $ex) {
                        $this->Form->addError($ex);
                    }
                    if ($this->Form->errorCount() == 0) {
                        $this->RedirectUrl = url('settings/managecategories');
                        $this->informMessage(t('Deleting category...'));
                    }
                }
            }
        }

        // Render default view
        $this->render();
    }

    /**
     * Deleting a category photo.
     *
     * @since 2.1
     * @access public
     *
     * @param int $CategoryID Unique ID of the category to have its photo deleted.
     */
    public function deleteCategoryPhoto($CategoryID = false, $TransientKey = '') {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        $RedirectUrl = 'settings/editcategory/'.$CategoryID;

        if (Gdn::session()->validateTransientKey($TransientKey)) {
            // Do removal, set message, redirect
            $CategoryModel = new CategoryModel();
            $CategoryModel->setField($CategoryID, 'Photo', null);
            $this->informMessage(t('Category photo has been deleted.'));
        }
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirect($RedirectUrl);
        } else {
            $this->RedirectUrl = url($RedirectUrl);
            $this->render();
        }
    }

    /**
     * Set allowed discussion types on the form.
     *
     * @param $Category
     */
    protected function setupDiscussionTypes($Category) {
        $DiscussionTypes = DiscussionModel::discussionTypes();
        $this->setData('DiscussionTypes', $DiscussionTypes);

        if (!$this->Form->isPostBack()) {
            $PCatID = val('PermissionCategoryID', $Category, -1);
            if ($PCatID == val('CategoryID', $Category)) {
                $PCat = $Category;
            } else {
                $PCat = CategoryModel::categories($PCatID);
            }
            $AllowedTypes = val('AllowedDiscussionTypes', $PCat);
            if (empty($AllowedTypes)) {
                $AllowedTypes = array_keys($DiscussionTypes);
            }

            $this->Form->setValue("AllowedDiscussionTypes", $AllowedTypes);
        }
    }

    /**
     * Editing a category.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CategoryID Unique ID of the category to be updated.
     */
    public function editCategory($CategoryID = '') {
        // Check permission
        $this->permission('Garden.Community.Manage');

        $this->ShowCustomPoints = false;

        // Set up models
        $RoleModel = new RoleModel();
        $PermissionModel = Gdn::permissionModel();
        $categoryModel = new CategoryModel();
        $this->Form->setModel($categoryModel);

        if (!$CategoryID && $this->Form->authenticatedPostBack()) {
            if ($ID = $this->Form->getFormValue('CategoryID')) {
                $CategoryID = $ID;
            }
        }

        // Get category data
        $this->Category = $categoryModel->getID($CategoryID);
        if (!$this->Category) {
            throw notFoundException('Category');
        }
        $this->Category->CustomPermissions = $this->Category->CategoryID == $this->Category->PermissionCategoryID;

        // Set up head
        $this->addJsFile('jquery.alphanumeric.js');
        $this->addJsFile('categories.js');
        $this->addJsFile('jquery.gardencheckboxgrid.js');
        $this->title(t('Edit Category'));

        $this->addSideMenu('settings/managecategories');

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('CategoryID', $CategoryID);
        $this->setData('CategoryID', $CategoryID);

        // Load all roles with editable permissions
        $this->RoleArray = $RoleModel->getArray();

        $this->fireEvent('AddEditCategory');

        if ($this->Form->authenticatedPostBack()) {
            $this->setupDiscussionTypes($this->Category);
            $Upload = new Gdn_Upload();
            $TmpImage = $Upload->validateUpload('PhotoUpload', false);
            if ($TmpImage) {
                // Generate the target image name
                $TargetImage = $Upload->generateTargetName(PATH_UPLOADS);
                $ImageBaseName = pathinfo($TargetImage, PATHINFO_BASENAME);

                // Save the uploaded image
                $Parts = $Upload->saveAs(
                    $TmpImage,
                    $ImageBaseName
                );
                $this->Form->setFormValue('Photo', $Parts['SaveName']);
            }
            $this->Form->setFormValue('CustomPoints', (bool)$this->Form->getFormValue('CustomPoints'));

            // Enforces tinyint values on boolean fields to comply with strict mode
            $this->Form->setFormValue('HideAllDiscussions', forceBool($this->Form->getFormValue('HideAllDiscussions'), '0', '1', '0'));
            $this->Form->setFormValue('Archived', forceBool($this->Form->getFormValue('Archived'), '0', '1', '0'));
            $this->Form->setFormValue('AllowFileUploads', forceBool($this->Form->getFormValue('AllowFileUploads'), '0', '1', '0'));

            if ($this->Form->save()) {
                $Category = CategoryModel::categories($CategoryID);
                $this->setData('Category', $Category);

                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirect('settings/managecategories');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_DATA && method_exists($this, 'getCategory')) {
                    $this->Data = [];
                    $this->getCategory($CategoryID);
                    return;
                }
            }
        } else {
            $this->Form->setData($this->Category);
            $this->setupDiscussionTypes($this->Category);
            $this->Form->setValue('CustomPoints', $this->Category->PointsCategoryID == $this->Category->CategoryID);
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $Permissions = $PermissionModel->getJunctionPermissions(array('JunctionID' => $CategoryID), 'Category', '', array('AddDefaults' => !$this->Category->CustomPermissions));
        $Permissions = $PermissionModel->unpivotPermissions($Permissions, true);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->setData('PermissionData', $Permissions, true);
        }

        // Render default view
        $this->render();
    }

    /**
     * Enabling and disabling categories from list.
     *
     * @since 2.0.0
     * @access public
     */
    public function manageCategories() {
        // Check permission
        $this->permission('Garden.Community.Manage');
        $this->addSideMenu('settings/managecategories');

        $this->addJsFile('categories.js');
        $this->addJsFile('jquery.alphanumeric.js');

        // Upgrading these with jQuery is extremely tricky, beware.
        $this->addJsFile('nestedSortable/jquery-ui.min.js');
        $this->addJsFile('nestedSortable.1.3.4/jquery.ui.nestedSortable.js');

        $this->title(t('Categories'));

        // Get category data
        $categoryModel = new CategoryModel();
        $CategoryData = $categoryModel->getAll('TreeLeft');

        // Set CanDelete per-category so we can override later if we want.
        $canDelete = checkPermission('Garden.Settings.Manage');
        array_walk($CategoryData->result(), function (&$value) use ($canDelete) {
            setvalr('CanDelete', $value, $canDelete);
        });

        $this->setData('CategoryData', $CategoryData, true);

        // Setup & save forms
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array(
            'Vanilla.Categories.MaxDisplayDepth',
            'Vanilla.Categories.DoHeadings',
            'Vanilla.Categories.HideModule'
        ));

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // Define MaxDepthOptions
        $DepthData = array();
        $DepthData['2'] = sprintf(t('more than %s deep'), plural(1, '%s level', '%s levels'));
        $DepthData['3'] = sprintf(t('more than %s deep'), plural(2, '%s level', '%s levels'));
        $DepthData['4'] = sprintf(t('more than %s deep'), plural(3, '%s level', '%s levels'));
        $DepthData['0'] = t('never');
        $this->setData('MaxDepthData', $DepthData);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        }

        // Render default view
        $this->render();
    }


    /**
     * Enable or disable the use of categories in Vanilla.
     *
     * @param bool $enabled Whether or not to enable/disable categories.
     * @throws Exception Throws an exception if accessed through an invalid post back.
     */
    public function enableCategories($enabled) {
        $this->permission('Garden.Settings.Manage');

        if ($this->Form->authenticatedPostBack()) {
            $enabled = (bool)$enabled;
            saveToConfig('Vanilla.Categories.Use', $enabled);
            $this->setData('Enabled', $enabled);

            if ($this->deliveryType() !== DELIVERY_TYPE_DATA) {
                $this->RedirectUrl = url('/settings/managecategories');
            }
        } else {
            throw forbiddenException('GET');
        }

        return $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Sorting display order of categories.
     *
     * Accessed by ajax so its default is to only output true/false.
     *
     * @since 2.0.0
     * @access public
     */
    public function sortCategories() {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        // Set delivery type to true/false
        if (Gdn::request()->isAuthenticatedPostBack()) {
            $TreeArray = val('TreeArray', $_POST);
            $categoryModel = new CategoryModel();
            $Saves = $categoryModel->saveTree($TreeArray);
            $this->setData('Result', true);
            $this->setData('Saves', $Saves);
        }

        // Renders true/false rather than template
        $this->render();
    }
}
