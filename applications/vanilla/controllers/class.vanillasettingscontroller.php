<?php
/**
 * Settings controller
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying the dashboard "settings" pages for Vanilla via /settings endpoint.
 */
class VanillaSettingsController extends Gdn_Controller {

    /** @var array Models to include. */
    public $Uses = array('Database', 'Form', 'CategoryModel');

    /** @var CategoryModel */
    public $CategoryModel;

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
            'Vanilla.Comment.MaxLength',
            'Vanilla.Comment.MinLength',
            'Garden.TrustedDomains'
        ));

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Format trusted domains as a string
            $TrustedDomains = val('Garden.TrustedDomains', $ConfigurationModel->Data);
            if (is_array($TrustedDomains)) {
                $TrustedDomains = implode("\n", $TrustedDomains);
            }

            $ConfigurationModel->Data['Garden.TrustedDomains'] = $TrustedDomains;

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
            $ArchiveDateBak = Gdn::config('Vanilla.Archive.Date');
            $ArchiveExcludeBak = (bool)Gdn::config('Vanilla.Archive.Exclude');

            // Format the trusted domains as an array based on newlines & spaces
            $TrustedDomains = $this->Form->getValue('Garden.TrustedDomains');
            $TrustedDomains = explodeTrim("\n", $TrustedDomains);
            $TrustedDomains = array_unique(array_filter($TrustedDomains));
            $TrustedDomains = implode("\n", $TrustedDomains);
            $this->Form->setFormValue('Garden.TrustedDomains', $TrustedDomains);

            // Save new settings
            $Saved = $this->Form->save();
            if ($Saved) {
                $ArchiveDate = Gdn::config('Vanilla.Archive.Date');
                $ArchiveExclude = (bool)Gdn::config('Vanilla.Archive.Exclude');

                if ($ArchiveExclude != $ArchiveExcludeBak || ($ArchiveExclude && $ArchiveDate != $ArchiveDateBak)) {
                    $DiscussionModel = new DiscussionModel();
                    $DiscussionModel->UpdateDiscussionCount('All');
                }
                $this->informMessage(t("Your changes have been saved."));
            }

            // Reformat array as string so it displays properly in the form
            $this->Form->setFormValue('Garden.TrustedDomains', $TrustedDomains);

        }

        $this->setHighlightRoute('vanilla/settings/advanced');
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
        $this->ManageCategories();
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
     * @param string $currentUrl
     */
    public function setHighlightRoute($currentUrl = '') {
        if ($currentUrl) {
            DashboardNavModule::getDashboardNav()->setHighlightRoute($currentUrl);
        }
    }

    /**
     * @param string $currentUrl
     */
    public function addSideMenu($currentUrl = '') {
        deprecated('addSideMenu', 'setHighlightRoute');
        $this->setHighlightRoute($currentUrl);
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
        Gdn_Theme::section('Moderation');
        $this->setHighlightRoute('vanilla/settings/floodcontrol');

        // Check to see if Conversation is enabled.
        $IsConversationsEnabled = Gdn::addonManager()->isEnabled('Conversations', \Vanilla\Addon::TYPE_ADDON);

        $ConfigurationFields = array(
            'Vanilla.Discussion.SpamCount',
            'Vanilla.Discussion.SpamTime',
            'Vanilla.Discussion.SpamLock',
            'Vanilla.Comment.SpamCount',
            'Vanilla.Comment.SpamTime',
            'Vanilla.Comment.SpamLock'
        );
        if ($IsConversationsEnabled) {
            $ConfigurationFields = array_merge(
                $ConfigurationFields,
                array(
                    'Conversations.Conversation.SpamCount',
                    'Conversations.Conversation.SpamTime',
                    'Conversations.Conversation.SpamLock',
                    'Conversations.ConversationMessage.SpamCount',
                    'Conversations.ConversationMessage.SpamTime',
                    'Conversations.ConversationMessage.SpamLock'
                )
            );
        }
        // Load up config options we'll be setting
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField($ConfigurationFields);

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussion.SpamCount', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussion.SpamCount', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussion.SpamTime', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussion.SpamTime', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussion.SpamLock', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Discussion.SpamLock', 'Integer');

            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.SpamCount', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.SpamCount', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.SpamTime', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.SpamTime', 'Integer');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.SpamLock', 'Required');
            $ConfigurationModel->Validation->applyRule('Vanilla.Comment.SpamLock', 'Integer');


            if ($IsConversationsEnabled) {
                $ConfigurationModel->Validation->applyRule('Conversations.Conversation.SpamCount', 'Required');
                $ConfigurationModel->Validation->applyRule('Conversations.Conversation.SpamCount', 'Integer');
                $ConfigurationModel->Validation->applyRule('Conversations.Conversation.SpamTime', 'Required');
                $ConfigurationModel->Validation->applyRule('Conversations.Conversation.SpamTime', 'Integer');
                $ConfigurationModel->Validation->applyRule('Conversations.Conversation.SpamLock', 'Required');
                $ConfigurationModel->Validation->applyRule('Conversations.Conversation.SpamLock', 'Integer');

                $ConfigurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamCount', 'Required');
                $ConfigurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamCount', 'Integer');
                $ConfigurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamTime', 'Required');
                $ConfigurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamTime', 'Integer');
                $ConfigurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamLock', 'Required');
                $ConfigurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamLock', 'Integer');
            }

            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your changes have been saved."));
            }
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
    public function addCategory($parent = '') {
        // Check permission
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        // Set up head
        $this->addJsFile('jquery.alphanumeric.js');
        $this->addJsFile('manage-categories.js');
        $this->addJsFile('jquery.gardencheckboxgrid.js');
        $this->title(t('Add Category'));
        $this->setHighlightRoute('vanilla/settings/categories');

        // Prep models
        $RoleModel = new RoleModel();
        $PermissionModel = Gdn::permissionModel();
        $this->Form->setModel($this->CategoryModel);

        // Load all roles with editable permissions.
        $this->RoleArray = $RoleModel->getArray();

        $this->fireAs('SettingsController');
        $this->fireEvent('AddEditCategory');
        $this->setupDiscussionTypes(array());

        if ($this->Form->authenticatedPostBack()) {
            // Form was validly submitted
            $IsParent = $this->Form->getFormValue('IsParent', '0');
            $this->Form->setFormValue('AllowDiscussions', $IsParent == '1' ? '0' : '1');
            $this->Form->setFormValue('CustomPoints', (bool)$this->Form->getFormValue('CustomPoints'));

            // Enforces tinyint values on boolean fields to comply with strict mode
            $this->Form->setFormValue('HideAllDiscussions', forceBool($this->Form->getFormValue('HideAllDiscussions'), '0', '1', '0'));
            $this->Form->setFormValue('Archived', forceBool($this->Form->getFormValue('Archived'), '0', '1', '0'));
            $this->Form->setFormValue('AllowFileUploads', forceBool($this->Form->getFormValue('AllowFileUploads'), '0', '1', '0'));

            $CategoryID = $this->Form->save();
            if ($CategoryID) {
                $Category = CategoryModel::categories($CategoryID);
                $this->setData('Category', $Category);

                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirect('vanilla/settings/categories');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_DATA && method_exists($this, 'getCategory')) {
                    $this->Data = [];
                    $this->getCategory($CategoryID);
                    return;
                }
            } else {
                unset($CategoryID);
            }
        } else {
            if ($parent) {
                $category = CategoryModel::categories($parent);
                if ($category) {
                    $this->Form->setValue('ParentCategoryID', $category['CategoryID']);
                }
            }

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
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        if (!$categoryID) {
            throw new Gdn_UserException(sprintf(t('ValidationRequired'), 'CategoryID'));
        }

        $categoryModel = new CategoryModel();
        $category = $categoryModel->getID($categoryID, DATASET_TYPE_ARRAY);
//        $category = Gdn::sql()->getWhere('Category', ['CategoryID' => $categoryID])->firstRow(DATASET_TYPE_ARRAY);

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
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        // Set up head
        $this->addJsFile('manage-categories.js');
        $this->title(t('Delete Category'));
        $this->setHighlightRoute('vanilla/settings/categories');

        // Get category data
        $this->Category = $this->CategoryModel->getID($CategoryID);


        if (!$this->Category) {
            $this->Form->addError('The specified category could not be found.');
        } else {
            // Make sure the form knows which item we are deleting.
            $this->Form->addHidden('CategoryID', $CategoryID);

            // Get a list of categories other than this one that can act as a replacement
            $this->OtherCategories = $this->CategoryModel->getWhere(
                array(
                    'CategoryID <>' => $CategoryID,
                    'AllowDiscussions' => $this->Category->AllowDiscussions, // Don't allow a category with discussion to be the replacement for one without discussions (or vice versa)
                    'CategoryID >' => 0
                ),
                'Sort'
            );

            if (!$this->Form->authenticatedPostBack()) {
                $this->Form->setFormValue('DeleteDiscussions', '1'); // Checked by default
            } else {
                $ReplacementCategoryID = $this->Form->getValue('ReplacementCategoryID');
                $ReplacementCategory = $this->CategoryModel->getID($ReplacementCategoryID);
                // Error if:
                // 1. The category being deleted is the last remaining category that
                // allows discussions.
                if ($this->Category->AllowDiscussions == '1'
                    && $this->OtherCategories->numRows() == 0
                ) {
                    $this->Form->addError('You cannot remove the only remaining category that allows discussions');
                }

                /*
                // 2. The category being deleted allows discussions, and it contains
                // discussions, and there is no replacement category specified.
                if ($this->Form->errorCount() == 0
                   && $this->Category->AllowDiscussions == '1'
                   && $this->Category->CountDiscussions > 0
                   && ($ReplacementCategory == FALSE || $ReplacementCategory->AllowDiscussions != '1'))
                   $this->Form->addError('You must select a replacement category in order to remove this category.');
                */

                // 3. The category being deleted does not allow discussions, and it
                // does contain other categories, and there are replacement parent
                // categories available, and one is not selected.
                /*
                if ($this->Category->AllowDiscussions == '0'
                   && $this->OtherCategories->numRows() > 0
                   && !$ReplacementCategory) {
                   if ($this->CategoryModel->getWhere(array('ParentCategoryID' => $CategoryID))->numRows() > 0)
                      $this->Form->addError('You must select a replacement category in order to remove this category.');
                }
                */

                if ($this->Form->errorCount() == 0) {
                    // Go ahead and delete the category
                    try {
                        $this->CategoryModel->delete($this->Category, $this->Form->getValue('ReplacementCategoryID'));
                    } catch (Exception $ex) {
                        $this->Form->addError($ex);
                    }
                    if ($this->Form->errorCount() == 0) {
                        $this->RedirectUrl = url('vanilla/settings/categories');
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
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        $RedirectUrl = 'vanilla/settings/editcategory/'.$CategoryID;

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
     *
     *
     * @param $Category
     */
    protected function setupDiscussionTypes($Category) {
        $DiscussionTypes = DiscussionModel::DiscussionTypes();
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

        // Set up models
        $RoleModel = new RoleModel();
        $PermissionModel = Gdn::permissionModel();
        $this->Form->setModel($this->CategoryModel);

        if (!$CategoryID && $this->Form->authenticatedPostBack()) {
            if ($ID = $this->Form->getFormValue('CategoryID')) {
                $CategoryID = $ID;
            }
        }

        // Get category data
        $this->Category = $this->CategoryModel->getID($CategoryID);
        if (!$this->Category) {
            throw notFoundException('Category');
        }
        // Category data is expected to be in the form of an object.
        $this->Category = (object)$this->Category;
        $this->Category->CustomPermissions = $this->Category->CategoryID == $this->Category->PermissionCategoryID;

        // Set up head
        $this->addJsFile('jquery.alphanumeric.js');
        $this->addJsFile('manage-categories.js');
        $this->addJsFile('jquery.gardencheckboxgrid.js');
        $this->title(t('Edit Category'));

        $this->setHighlightRoute('vanilla/settings/categories');

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('CategoryID', $CategoryID);
        $this->setData('CategoryID', $CategoryID);

        // Load all roles with editable permissions
        $this->RoleArray = $RoleModel->getArray();

        $this->fireAs('SettingsController');
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
                    redirect('vanilla/settings/categories');
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
     * Manage the category hierarchy.
     *
     * @param string $parent The URL slug of a parent category if looking at a sub tree.
     */
    public function categories($parent = '') {
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);
        $this->setHighlightRoute('vanilla/settings/categories');

        // Make sure we are reading the categories from the database only.
        $collection = $this->CategoryModel->createCollection(Gdn::sql(), new Gdn_Dirtycache());

        if (!empty($parent)) {
            $categoryRow = $collection->get((string)$parent);
            if (empty($categoryRow)) {
                throw notFoundException('Category');
            }
            $this->setData('Category', $categoryRow);
            $parentID = $categoryRow['CategoryID'];
        } else {
            $parentID = -1;
        }

        $categories = $collection->getTree($parentID, ['maxdepth' => 10, 'collapsecategories' => true]);
        $this->setData('Categories', $categories);

        if ($parentID > 0) {
            $ancestors = $collection->getAncestors($parentID, true);
            $this->setData('Ancestors', $ancestors);
        }

        $this->addJsFile('category-settings.js');
        $this->addJsFile('manage-categories.js');
        $this->addJsFile('jquery.nestable.js');
        require_once $this->fetchViewLocation('category-settings-functions');
        $this->addAsset('Content', $this->fetchView('symbols'));
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
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);
        $this->setHighlightRoute('vanilla/settings/categories');
        $this->addJsFile('manage-categories.js');
        $this->addJsFile('jquery.alphanumeric.js');




        // This now works on latest jQuery version 1.10.2
        //
        // Jan29, 2014, upgraded jQuery UI to 1.10.3 from 1.8.11
        $this->addJsFile('nestedSortable/jquery-ui.min.js');
        // Newer nestedSortable, but does not work.
        //$this->addJsFile('js/library/nestedSortable/jquery.mjs.nestedSortable.js');
        // old jquery-ui
        //$this->addJsFile('js/library/nestedSortable.1.3.4/jquery-ui-1.8.11.custom.min.js');
        $this->addJsFile('nestedSortable.1.3.4/jquery.ui.nestedSortable.js');

        $this->title(t('Categories'));

        // Get category data
        $CategoryData = $this->CategoryModel->getAll('TreeLeft');

        // Set CanDelete per-category so we can override later if we want.
        $canDelete = checkPermission(['Garden.Community.Manage', 'Garden.Settings.Manage']);
        array_walk($CategoryData->result(), function(&$value) use ($canDelete) {
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
                CategoryModel::clearCache();
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
                $this->RedirectUrl = url('/vanilla/settings/categories');
            }
        } else {
            throw forbiddenException('GET');
        }

        return $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Set the display as property of a category.
     *
     * @throws Gdn_UserException Throws an exception of the posted data is incorrect.
     */
    public function categoryDisplayAs() {
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        if ($this->Request->isAuthenticatedPostBack(true)) {
            $categoryID = $this->Request->post('CategoryID');
            $displayAs = $this->Request->post('DisplayAs');

            if (!$categoryID || !$displayAs) {
                throw new Gdn_UserException("CategoryID and DisplayAs are required", 400);
            }

            $this->CategoryModel->setField($categoryID, 'DisplayAs', $displayAs);
            $category = CategoryModel::categories($categoryID);
            $this->setData('CategoryID', $category['CategoryID']);
            $this->setData('DisplayAs', $category['DisplayAs']);
        } else {
            throw new Gdn_UserException(Gdn::request()->requestMethod().' not allowed.', 405);
        }
        $this->render();
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
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        // Set delivery type to true/false
        if (Gdn::request()->isAuthenticatedPostBack()) {
            $TreeArray = val('TreeArray', $_POST);
            $Saves = $this->CategoryModel->SaveTree($TreeArray);
            $this->setData('Result', true);
            $this->setData('Saves', $Saves);
        }

        // Renders true/false rather than template
        $this->render();
    }

    /**
     * Sorting display order of categories.
     */
    public function categoriesTree() {
        // Check permission
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        if ($this->Request->isAuthenticatedPostBack(true)) {
            $tree = json_decode($this->Request->post('Subtree'), true);
            $result = $this->CategoryModel->SaveSubtree($tree);
            $this->setData('Result', $result);
        } else {
            throw new Gdn_UserException($this->Request->requestMethod().' is not allowed.', 405);
        }

        // Renders true/false rather than template
        $this->render();
    }
}
