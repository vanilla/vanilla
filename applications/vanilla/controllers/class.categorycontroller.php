<?php
/**
 * Category controller
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0.17.9
 */

/**
 * Handles the /category endpoint.
 */
class CategoryController extends VanillaController {

    /** @var CategoryModel */
    public $CategoryModel;

    public function __construct() {
        parent::__construct();
        $this->CategoryModel = new CategoryModel();
    }

    /**
     * Mute or unmute a category.
     *
     * @deprecated 2.6 Deprecated in favor of whitelist-style category following.
     * @see CategoryController::followed
     * @param $categoryID
     * @param $value
     * @param $tKey
     */
    public function follow($categoryID, $value, $tKey) {
        deprecated(__METHOD__, __CLASS__.'::followed');

        if (Gdn::session()->validateTransientKey($tKey)) {
            $this->CategoryModel->saveUserTree($categoryID, ['Unfollow' => (int)(!(bool)$value)]);
        }

        if ($this->isRenderingMasterView()) {
            redirectTo('/categories');
        }

        $this->render();
    }

    /**
     * Allows user to follow or unfollow a category.
     *
     * @param int $DiscussionID Unique discussion ID.
     */
    public function followed($categoryID = null, $tKey = null) {
        // Make sure we are posting back.
        if (!$this->Request->isAuthenticatedPostBack() && !Gdn::session()->validateTransientKey($tKey)) {
            throw permissionException('Javascript');
        }

        if (!Gdn::session()->isValid()) {
            throw permissionException('SignedIn');
        }

        $userID = Gdn::session()->UserID;

        $categoryModel = new CategoryModel();
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            throw notFoundException('Category');
        }

        // Check the form to see if the data was posted.
        $form = new Gdn_Form();
        $categoryID = $form->getFormValue('CategoryID', $categoryID);
        $followed = $form->getFormValue('Followed', null);
        $hasPermission = $categoryModel::checkPermission($categoryID, 'Vanilla.Discussions.View');
        if (!$hasPermission) {
            throw permissionException('Vanilla.Discussion.View');
        }

        try {
            $result = $categoryModel->follow($userID, $categoryID, $followed);
        } catch (Exception $e) {
            throw new \Gdn_UserException($e->getMessage(), 403, $e);
        }

        // Set the new value for api calls and json targets.
        $this->setData([
            'UserID' => $userID,
            'CategoryID' => $categoryID,
            'Followed' => $result
        ]);

        $parentCategory = false;
        if ($category['ParentCategoryID'] > 0) {
            $parentCategory = $categoryModel::categories($category['ParentCategoryID'])['UrlCode'];
        }

        switch ($this->deliveryType()) {
            case DELIVERY_TYPE_DATA:
                $this->render('Blank', 'Utility', 'Dashboard');
                return;
            case DELIVERY_TYPE_ALL:
                // If this is a subcategories, redirect to parent category. Otherwise, redirect to categories page.
                $parentCategory ? redirectTo('/categories/'.$parentCategory) : redirectTo('/categories');
        }

        // Return the appropriate bookmark.
        require_once $this->fetchViewLocation('helper_functions', 'Categories');
        $markup = followButton($categoryID);
        $this->jsonTarget("!element", $markup, 'ReplaceWith');

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    public function initialize() {
        parent::initialize();

        /**
         * The default Cache-Control header does not include no-store, which can cause issues with outdated category
         * information (e.g. counts).  The same check is performed here as in Gdn_Controller before the Cache-Control
         * header is added, but this value includes the no-store specifier.
         */
        if (Gdn::session()->isValid()) {
            $this->setHeader('Cache-Control', 'private, no-cache, no-store, max-age=0, must-revalidate');
        }
    }

    public function markRead($categoryID, $tKey) {
        if (Gdn::session()->validateTransientKey($tKey)) {
            $this->CategoryModel->saveUserTree($categoryID, ['DateMarkedRead' => Gdn_Format::toDateTime()]);
        }
        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $category = $this->CategoryModel->getID($categoryID, DATASET_TYPE_ARRAY);

            if ($category['ParentCategoryID'] > 0) {
                $parentCategory = $this->CategoryModel->getID($category['ParentCategoryID'], DATASET_TYPE_ARRAY);
                // If this is a subcategory, redirect to parent category.
                redirectTo('/categories/' . $parentCategory['UrlCode']);
            }
            // Otherwise, redirect to categories page.
            redirectTo('/categories');
        }

        $this->render();
    }
}
