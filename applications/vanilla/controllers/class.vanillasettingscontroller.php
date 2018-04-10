<?php
/**
 * Settings controller
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying the dashboard "settings" pages for Vanilla via /settings endpoint.
 */
class VanillaSettingsController extends Gdn_Controller {

    /** @var array Models to include. */
    public $Uses = ['Database', 'Form', 'CategoryModel'];

    /** @var CategoryModel */
    public $CategoryModel;

    /** @var object The current category, if available. */
    public $Category;

    /** @var Gdn_Form */
    public $Form;

    /** @var array An array of category records. */
    public $OtherCategories;

    /**
     * Posting settings.
     *
     * Allows setting configuration values via form elements.
     *
     * @since 2.0.0
     * @access public
     */
    public function posting() {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Vanilla.Categories.MaxDisplayDepth',
            'Vanilla.Discussions.PerPage',
            'Vanilla.Comments.PerPage',
            'Garden.Html.AllowedElements',
            'Garden.EditContentTimeout',
            'Vanilla.AdminCheckboxes.Use',
            'Vanilla.Comment.MaxLength',
            'Vanilla.Comment.MinLength',
            'Garden.Format.DisableUrlEmbeds',
        ]);

        // Set the model on the form.
        $this->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {


            // Apply the config settings to the form.
            $this->Form->setData($configurationModel->Data);
        } else {
            // This is a "reverse" field on the form. Disabling URL embeds is associated with a toggle that enables them.
            $disableUrlEmbeds = $this->Form->getFormValue('Garden.Format.DisableUrlEmbeds', true);
            $this->Form->setFormValue('Garden.Format.DisableUrlEmbeds', !$disableUrlEmbeds);

            // Define some validation rules for the fields being saved
            $configurationModel->Validation->applyRule('Vanilla.Categories.MaxDisplayDepth', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Categories.MaxDisplayDepth', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Discussions.PerPage', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Discussions.PerPage', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Comments.PerPage', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Comments.PerPage', 'Integer');
            $configurationModel->Validation->applyRule('Garden.EditContentTimeout', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Comment.MaxLength', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Comment.MaxLength', 'Integer');

            // Save new settings
            $saved = $this->Form->save();
            if ($saved !== false) {
                $this->informMessage(t("Your changes have been saved."));
            }
        }

        $this->setHighlightRoute('vanilla/settings/posting');
        $this->addJsFile('settings.js');
        $this->title(t('Posting'));

        // Render default view (settings/posting.php)
        $this->render();
    }

    /**
     * Backwards compatibility.
     *
     * @deprecated 2.4 Legacy redirect. Use VanillaSettingsController::posting instead.
     */
    public function advanced() {
        redirectTo('/vanilla/settings/posting');
    }

    /**
     *
     */
    public function archive() {
        // Check permission
        $this->permission('Garden.Settings.Manage');

        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Vanilla.Archive.Date',
            'Vanilla.Archive.Exclude'
        ]);

        // Set the model on the form.
        $this->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            $this->Form->setData($configurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $configurationModel->Validation->applyRule('Vanilla.Archive.Date', 'Date');

            // Grab old config values to check for an update.
            $archiveDateBak = Gdn::config('Vanilla.Archive.Date');
            $archiveExcludeBak = (bool)Gdn::config('Vanilla.Archive.Exclude');

            // Save new settings
            $saved = $this->Form->save();
            if ($saved !== false) {
                $archiveDate = Gdn::config('Vanilla.Archive.Date');
                $archiveExclude = (bool)Gdn::config('Vanilla.Archive.Exclude');

                if ($archiveExclude != $archiveExcludeBak || ($archiveExclude && $archiveDate != $archiveDateBak)) {
                    $discussionModel = new DiscussionModel();
                    $discussionModel->updateDiscussionCount('All');
                }
                $this->informMessage(t("Your changes have been saved."));
            }
        }

        $this->setHighlightRoute('vanilla/settings/archive');
        $this->title(t('Archive Discussions'));

        // Render default view (settings/archive.php)
        $this->render();
    }

    /**
     * Alias for ManageCategories method.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        redirectTo('/vanilla/settings/categories');
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

        if (in_array($this->ControllerName, ['profilecontroller', 'activitycontroller'])) {
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
        $isConversationsEnabled = Gdn::addonManager()->isEnabled('Conversations', \Vanilla\Addon::TYPE_ADDON);
        $this->setData('IsConversationsEnabled', $isConversationsEnabled);

        $configurationFields = [
            'Vanilla.Discussion.SpamCount',
            'Vanilla.Discussion.SpamTime',
            'Vanilla.Discussion.SpamLock',
            'Vanilla.Comment.SpamCount',
            'Vanilla.Comment.SpamTime',
            'Vanilla.Comment.SpamLock',
            'Vanilla.Activity.SpamCount',
            'Vanilla.Activity.SpamTime',
            'Vanilla.Activity.SpamLock',
            'Vanilla.ActivityComment.SpamCount',
            'Vanilla.ActivityComment.SpamTime',
            'Vanilla.ActivityComment.SpamLock',
        ];
        if ($isConversationsEnabled) {
            $configurationFields = array_merge(
                $configurationFields,
                [
                    'Conversations.Conversation.SpamCount',
                    'Conversations.Conversation.SpamTime',
                    'Conversations.Conversation.SpamLock',
                    'Conversations.ConversationMessage.SpamCount',
                    'Conversations.ConversationMessage.SpamTime',
                    'Conversations.ConversationMessage.SpamLock'
                ]
            );
        }
        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField($configurationFields);

        // Set the model on the form.
        $this->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($configurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $configurationModel->Validation->applyRule('Vanilla.Discussion.SpamCount', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Discussion.SpamCount', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Discussion.SpamTime', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Discussion.SpamTime', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Discussion.SpamLock', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Discussion.SpamLock', 'Integer');

            $configurationModel->Validation->applyRule('Vanilla.Comment.SpamCount', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Comment.SpamCount', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Comment.SpamTime', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Comment.SpamTime', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Comment.SpamLock', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Comment.SpamLock', 'Integer');

            $configurationModel->Validation->applyRule('Vanilla.Activity.SpamCount', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Activity.SpamCount', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Activity.SpamTime', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Activity.SpamTime', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.Activity.SpamLock', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.Activity.SpamLock', 'Integer');

            $configurationModel->Validation->applyRule('Vanilla.ActivityComment.SpamCount', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.ActivityComment.SpamCount', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.ActivityComment.SpamTime', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.ActivityComment.SpamTime', 'Integer');
            $configurationModel->Validation->applyRule('Vanilla.ActivityComment.SpamLock', 'Required');
            $configurationModel->Validation->applyRule('Vanilla.ActivityComment.SpamLock', 'Integer');

            if ($isConversationsEnabled) {
                $configurationModel->Validation->applyRule('Conversations.Conversation.SpamCount', 'Required');
                $configurationModel->Validation->applyRule('Conversations.Conversation.SpamCount', 'Integer');
                $configurationModel->Validation->applyRule('Conversations.Conversation.SpamTime', 'Required');
                $configurationModel->Validation->applyRule('Conversations.Conversation.SpamTime', 'Integer');
                $configurationModel->Validation->applyRule('Conversations.Conversation.SpamLock', 'Required');
                $configurationModel->Validation->applyRule('Conversations.Conversation.SpamLock', 'Integer');

                $configurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamCount', 'Required');
                $configurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamCount', 'Integer');
                $configurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamTime', 'Required');
                $configurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamTime', 'Integer');
                $configurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamLock', 'Required');
                $configurationModel->Validation->applyRule('Conversations.ConversationMessage.SpamLock', 'Integer');
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
        $this->setupDiscussionTypes([]);

        $displayAsOptions = CategoryModel::getDisplayAsOptions();

        if ($this->Form->authenticatedPostBack()) {
            // Form was validly submitted
            $IsParent = $this->Form->getFormValue('IsParent', '0');
            $this->Form->setFormValue('AllowDiscussions', $IsParent == '1' ? '0' : '1');
            $this->Form->setFormValue('CustomPoints', (bool)$this->Form->getFormValue('CustomPoints'));

            // Enforces tinyint values on boolean fields to comply with strict mode
            $this->Form->setFormValue('HideAllDiscussions', forceBool($this->Form->getFormValue('HideAllDiscussions', null), '0', '1', '0'));
            $this->Form->setFormValue('Archived', forceBool($this->Form->getFormValue('Archived', null), '0', '1', '0'));
            $this->Form->setFormValue('AllowFileUploads', forceBool($this->Form->getFormValue('AllowFileUploads', null), '1', '1', '0'));

            $upload = new Gdn_Upload();
            $tmpImage = $upload->validateUpload('Photo_New', false);

            if ($tmpImage) {
                // Generate the target image name
                $targetImage = $upload->generateTargetName(PATH_UPLOADS);
                $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

                // Save the uploaded image
                $parts = $upload->saveAs(
                    $tmpImage,
                    $imageBaseName
                );
                $this->Form->setFormValue('Photo', $parts['SaveName']);
            }

            $CategoryID = $this->Form->save();
            if ($CategoryID) {
                $Category = CategoryModel::categories($CategoryID);
                $this->setData('Category', $Category);

                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    $redirect = 'vanilla/settings/categories';

                    if (!empty($parent)) {
                        $parentCategory = CategoryModel::categories($parent);
                        $visitedCategoryIDs = [];
                        do {
                            // Check against cycling dependency which would make make an infinite loop.
                            if (in_array($parentCategory['CategoryID'], $visitedCategoryIDs)) {
                                break;
                            }
                            $visitedCategoryIDs[] = $parentCategory['CategoryID'];

                            if (in_array($parentCategory['DisplayAs'], ['Categories', 'Flat'])) {
                                $redirect = 'vanilla/settings/categories?parent='.$parentCategory['UrlCode'];
                                break;
                            }
                        } while ($parentCategory = CategoryModel::categories($parentCategory['ParentCategoryID']));
                    }

                    redirectTo($redirect);
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

                    if (val('DisplayAs', $category) === 'Flat') {
                        unset($displayAsOptions['Heading']);
                    }
                }
            }

            $this->Form->addHidden('CodeIsDefined', '0');
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $Permissions = $PermissionModel->getJunctionPermissions(['JunctionID' => isset($CategoryID) ? $CategoryID : 0], 'Category');
        $Permissions = $PermissionModel->unpivotPermissions($Permissions, true);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->setData('PermissionData', $Permissions, true);
        }

        $this->setData('Operation', 'Add');
        $this->setData('DisplayAsOptions', $displayAsOptions);
        $this->render('editcategory', 'vanillasettings', 'vanilla');
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
     * @param int|bool $categoryID Unique ID of the category to be deleted.
     */
    public function deleteCategory($categoryID = false) {
        // Check permission
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        // Set up head
        $this->addJsFile('manage-categories.js');
        $this->title(t('Delete Category'));
        $this->setHighlightRoute('vanilla/settings/categories');

        // Get category data
        $this->Category = $this->CategoryModel->getID($categoryID);

        // Block deleting special categories.
        if (!val('CanDelete', $this->Category, true)) {
            $this->Form->addError('The specified category cannot be deleted.');
        }

        if (!$this->Category) {
            $this->Form->addError('The specified category could not be found.');
        } else {
            // Make sure the form knows which item we are deleting.
            $this->Form->addHidden('CategoryID', $categoryID);

            // Get a list of categories other than this one that can act as a replacement
            $this->OtherCategories = $this->CategoryModel->getWhere(
                [
                    'CategoryID <>' => $categoryID,
                    'AllowDiscussions' => $this->Category->AllowDiscussions, // Don't allow a category with discussion to be the replacement for one without discussions (or vice versa)
                    'CategoryID >' => 0
                ],
                'Sort'
            );

            // Get the list of sub-categories
            $subcategories = $this->CategoryModel->getSubtree($categoryID, false);
            $this->setData('Subcategories', $subcategories);
            // Number of discussions contained in the subcategories
            $discussionModel = new DiscussionModel();
            $categoryIDs = array_merge([$categoryID], array_column($subcategories, 'CategoryID'));
            $this->setData('DiscussionsCount', $discussionModel->getCountForCategory($categoryIDs));

            if ($this->Form->authenticatedPostBack()) {
                // Error if the category being deleted is the last remaining category that allows discussions.
                if ($this->Category->AllowDiscussions == '1' && $this->OtherCategories->numRows() == 0) {
                    $this->Form->addError('You cannot remove the only remaining category that allows discussions');
                } else {
                    $newCategoryID = 0;
                    $contentAction = $this->Form->getFormValue('ContentAction', false);

                    switch($contentAction) {
                        case 'move':
                            $newCategoryID = $this->Form->getFormValue('ReplacementCategoryID');
                            if (!$newCategoryID) {
                                $this->Form->addError('Replacement category is required.');
                            }
                            break;
                        case 'delete':
                            if (!$this->Form->getFormValue('ConfirmDelete', false)) {
                                $this->Form->addError('You must confirm the deletion.');
                            }
                            break;
                        default:
                            $this->Form->addError('Something went wrong.');
                            break;
                    }
                }

                if ($this->Form->errorCount() == 0) {
                    // Go ahead and delete the category
                    try {
                        $this->CategoryModel->deleteAndReplace($this->Category, $newCategoryID);
                    } catch (Exception $ex) {
                        $this->Form->addError($ex);
                    }

                    if ($this->Form->errorCount() == 0) {
                        $this->setRedirectTo('vanilla/settings/categories');
                        $this->informMessage(t('Deleting category...'));
                    }
                }
            }
        }

        // Render default view
        $this->render();
    }

    /**
     * Delete a category photo.
     *
     * @since 2.1
     * @access public
     *
     * @param String $categoryID Unique ID of the category to have its photo deleted.
     */
    public function deleteCategoryPhoto($categoryID = '') {
        // Check permission
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        if ($categoryID && Gdn::request()->isAuthenticatedPostBack(true)) {
            // Do removal, set message
            $categoryModel = new CategoryModel();
            $categoryModel->setField($categoryID, 'Photo', null);
            $this->informMessage(t('Category photo was successfully deleted.'));
        }
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     *
     *
     * @param $category
     */
    protected function setupDiscussionTypes($category) {
        $discussionTypes = DiscussionModel::discussionTypes();
        $this->setData('DiscussionTypes', $discussionTypes);

        if (!$this->Form->isPostBack()) {
            $pCatID = val('PermissionCategoryID', $category, -1);
            if ($pCatID == val('CategoryID', $category)) {
                $pCat = $category;
            } else {
                $pCat = CategoryModel::categories($pCatID);
            }
            $allowedTypes = val('AllowedDiscussionTypes', $pCat);
            if (empty($allowedTypes)) {
                $allowedTypes = array_keys($discussionTypes);
            }

            $this->Form->setValue("AllowedDiscussionTypes", $allowedTypes);
        }
    }

    /**
     * Editing a category.
     *
     * @since 2.0.0
     * @param int|string $categoryID Unique ID of the category to be updated.
     * @throws Exception when category cannot be found.
     */
    public function editCategory($categoryID = '') {
        // Check permission
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        // Set up models
        $roleModel = new RoleModel();
        $permissionModel = Gdn::permissionModel();
        $this->Form->setModel($this->CategoryModel);

        if (!$categoryID && $this->Form->authenticatedPostBack()) {
            if ($iD = $this->Form->getFormValue('CategoryID')) {
                $categoryID = $iD;
            }
        }

        // Get category data
        $this->Category = CategoryModel::categories($categoryID);
        if (!$this->Category) {
            throw notFoundException('Category');
        }
        // Category data is expected to be in the form of an object.
        $this->Category = (object)$this->Category;
        $this->Category->CustomPermissions = $this->Category->CategoryID == $this->Category->PermissionCategoryID;

        $displayAsOptions = categoryModel::getDisplayAsOptions();

        // Restrict "Display As" types based on parent.
        $parentCategory = $this->CategoryModel->getID($this->Category->ParentCategoryID);
        $parentDisplay = val('DisplayAs', $parentCategory);
        if ($parentDisplay === 'Flat') {
            unset($displayAsOptions['Heading']);
        }

        // Set up head
        $this->addJsFile('jquery.alphanumeric.js');
        $this->addJsFile('manage-categories.js');
        $this->addJsFile('jquery.gardencheckboxgrid.js');
        $this->title(t('Edit Category'));

        $this->setHighlightRoute('vanilla/settings/categories');

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('CategoryID', $categoryID);
        $this->setData('CategoryID', $categoryID);

        // Load all roles with editable permissions
        $this->RoleArray = $roleModel->getArray();

        $this->fireAs('SettingsController');
        $this->fireEvent('AddEditCategory');

        if ($this->Form->authenticatedPostBack()) {
            $this->setupDiscussionTypes($this->Category);
            $upload = new Gdn_Upload();
            $tmpImage = $upload->validateUpload('Photo_New', false);
            if ($tmpImage) {
                // Generate the target image name
                $targetImage = $upload->generateTargetName(PATH_UPLOADS);
                $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

                // Save the uploaded image
                $parts = $upload->saveAs(
                    $tmpImage,
                    $imageBaseName
                );
                $this->Form->setFormValue('Photo', $parts['SaveName']);
            }
            $this->Form->setFormValue('CustomPoints', (bool)$this->Form->getFormValue('CustomPoints'));

            // Enforces tinyint values on boolean fields to comply with strict mode
            if ($this->Form->getFormValue('HideAllDiscussions', null) !== null) {
                $this->Form->setFormValue('HideAllDiscussions', forceBool($this->Form->getFormValue('HideAllDiscussions'), '0', '1', '0'));
            }
            if ($this->Form->getFormValue('Archived', null) !== null) {
                $this->Form->setFormValue('Archived', forceBool($this->Form->getFormValue('Archived'), '0', '1', '0'));
            }
            if ($this->Form->getFormValue('AllowFileUploads', null) !== null) {
                $this->Form->setFormValue('AllowFileUploads', forceBool($this->Form->getFormValue('AllowFileUploads'), '1', '1', '0'));
            }

            if ($parentDisplay === 'Flat' && $this->Form->getFormValue('DisplayAs') === 'Heading') {
                $this->Form->addError('Cannot display as a heading when your parent category is displayed flat.', 'DisplayAs');
            }

            if ($this->Form->save()) {
                $category = CategoryModel::categories($categoryID);
                $this->setData('Category', $category);

                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    $destination = $this->categoryPageByParent($parentCategory);
                    redirectTo($destination);
                } elseif ($this->deliveryType() === DELIVERY_TYPE_DATA && method_exists($this, 'getCategory')) {
                    $this->Data = [];
                    $this->getCategory($categoryID);
                    return;
                }
            }
        } else {
            $this->Form->setData($this->Category);
            $this->setupDiscussionTypes($this->Category);
            $this->Form->setValue('CustomPoints', $this->Category->PointsCategoryID == $this->Category->CategoryID);
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $permissions = $permissionModel->getJunctionPermissions(['JunctionID' => $categoryID], 'Category', '', ['AddDefaults' => !$this->Category->CustomPermissions]);
        $permissions = $permissionModel->unpivotPermissions($permissions, true);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->setData('PermissionData', $permissions, true);
        }

        // Render default view
        $this->setData('Operation', 'Edit');
        $this->setData('DisplayAsOptions', $displayAsOptions);
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

        $allowSorting = true;

        $usePagination = false;
        $perPage = 30;
        $page = Gdn::request()->get('Page', Gdn::request()->get('page', null));
        list($offset, $limit) = offsetLimit($page, $perPage);

        if (!empty($parent)) {
            $categoryRow = $collection->get((string)$parent);
            if (empty($categoryRow)) {
                throw notFoundException('Category');
            }
            $this->setData('Category', $categoryRow);
            $parentID = $categoryRow['CategoryID'];
            $parentDisplayAs = val('DisplayAs', $categoryRow);
        } else {
            $parentID = -1;
            $parentDisplayAs = CategoryModel::getRootDisplayAs();
        }

        if (in_array($parentDisplayAs, ['Flat'])) {
            $allowSorting = false;
            $usePagination = true;
        }

        if ($parentDisplayAs === 'Flat') {
            $categories = $this->CategoryModel->getTreeAsFlat($parentID, $offset, $limit);
        } else {
            $categories = $collection->getTree($parentID, ['maxdepth' => 10, 'collapsecategories' => true]);
        }

        $this->addJsFile('categoryfilter.js', 'vanilla');

        $this->setData('ParentID', $parentID);
        $this->setData('Categories', $categories);
        $this->setData('_Limit', $perPage);
        $this->setData('_CurrentRecords', count($categories));

        if ($parentID > 0) {
            $ancestors = $collection->getAncestors($parentID, true);
            $this->setData('Ancestors', $ancestors);
        }

        $this->setData('AllowSorting', $allowSorting);
        $this->setData('UsePagination', $usePagination);

        $this->addDefinition('AllowSorting', $allowSorting);
        $this->addDefinition('SavePending', t('Saving. Please wait.'));

        $this->addJsFile('category-settings.js');
        $this->addJsFile('manage-categories.js');
        $this->addJsFile('jquery.nestable.js');
        require_once $this->fetchViewLocation('category-settings-functions');
        $this->addAsset('Content', $this->fetchView('symbols'));
        $this->render();
    }

    /**
     * Edit advanced category settings.
     */
    public function categorySettings() {
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);
        $this->setHighlightRoute('vanilla/settings/categories');

        $cf = new ConfigurationModule($this);

        $cf->initialize([
            'Vanilla.EnableCategoryFollowing' => [
                'LabelCode' => 'Category Following',
                'Control' => 'toggle',
                'Description' => t(
                    'Some themes must be updated for category following.',
                    'Some themes may need to be updated to work with category following. You can disable the feature while you update your theme.'
                )
            ],
        ]);

        $this->setData('Title', t('Advanced Category Settings'));
        $cf->renderAll();
    }

    /**
     * Move through the category's parents to determine the proper management page URL.
     *
     * @param array|object $category
     * @return string
     */
    private function categoryPageByParent($category) {
        $default = 'vanilla/settings/categories';
        $parentID = val('ParentCategoryID', $category);

        if ($parentID === -1) {
            return $default;
        }

        $parent = CategoryModel::categories($parentID);
        if (!$parent) {
            return $default;
        }

        switch (val('DisplayAs', $parent)) {
            case 'Categories':
            case 'Flat':
                $urlCode = val('UrlCode', $parent);
                return "vanilla/settings/categories?parent={$urlCode}";
            case 'Discussions':
            case 'Heading':
                return $this->categoryPageByParent($parent);
            default:
                return $default;
        }
    }

    /**
     * Enabling and disabling categories from list.
     *
     * @since 2.0.0
     * @access public
     */
    public function manageCategories() {
        deprecated('categories');

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
        $categoryData = $this->CategoryModel->getAll('TreeLeft');

        // Set CanDelete per-category so we can override later if we want.
        $canDelete = checkPermission(['Garden.Community.Manage', 'Garden.Settings.Manage']);
        array_walk($categoryData->result(), function(&$value) use ($canDelete) {
            setvalr('CanDelete', $value, $canDelete);
        });

        $this->setData('CategoryData', $categoryData, true);

        // Setup & save forms
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Vanilla.Categories.MaxDisplayDepth',
            'Vanilla.Categories.DoHeadings',
            'Vanilla.Categories.HideModule'
        ]);

        // Set the model on the form.
        $this->Form->setModel($configurationModel);

        // Define MaxDepthOptions
        $depthData = [];
        $depthData['2'] = sprintf(t('more than %s deep'), plural(1, '%s level', '%s levels'));
        $depthData['3'] = sprintf(t('more than %s deep'), plural(2, '%s level', '%s levels'));
        $depthData['4'] = sprintf(t('more than %s deep'), plural(3, '%s level', '%s levels'));
        $depthData['0'] = t('never');
        $this->setData('MaxDepthData', $depthData);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($configurationModel->Data);
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
     * Move a category to a different parent.
     *
     * @param int $categoryID Unique ID for the category to move.
     * @throws Exception if category is not found.
     */
    public function moveCategory($categoryID) {
        // Check permission
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        $category = CategoryModel::categories($categoryID);

        if (!$category) {
            throw notFoundException();
        }

        $this->Form->setModel($this->CategoryModel);
        $this->Form->addHidden('CategoryID', $categoryID);
        $this->setData('Category', $category);

        $parentCategories = CategoryModel::getAncestors($categoryID);
        array_pop($parentCategories);
        if (!empty($parentCategories)) {
            $this->setData('ParentCategories', array_column($parentCategories, 'Name', 'CategoryID'));
        }

        if ($this->Form->authenticatedPostBack()) {
            // Verify we're only attempting to save specific values.
            $this->Form->formValues([
                'CategoryID' => $this->Form->getValue('CategoryID'),
                'ParentCategoryID' => $this->Form->getValue('ParentCategoryID'),
            ]);
            $this->Form->save();
        } else {
            $this->Form->setData($category);
        }

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
                $this->setRedirectTo('/vanilla/settings/categories');
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
            $treeArray = val('TreeArray', $_POST);
            $saves = $this->CategoryModel->saveTree($treeArray);
            $this->setData('Result', true);
            $this->setData('Saves', $saves);
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
            $this->CategoryModel->saveSubtree($tree, $this->Request->post('ParentID', -1));

            $this->setData('Result', (count($this->CategoryModel->Validation->results()) === 0));
            $this->setData('Validation', $this->CategoryModel->Validation->resultsArray());
        } else {
            throw new Gdn_UserException($this->Request->requestMethod().' is not allowed.', 405);
        }

        // Renders true/false rather than template
        $this->render();
    }
}
