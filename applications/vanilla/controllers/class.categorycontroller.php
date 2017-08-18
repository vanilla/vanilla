<?php
/**
 * Discussion controller
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0.17.9
 */

/**
 * Handles the /category endpoint.
 */
class CategoryController extends VanillaController {

    /** @var Gdn_CategoryModel */
    public $CategoryModel;

    public function __construct() {
        parent::__construct();
        $this->CategoryModel = new CategoryModel();
    }

    public function follow($categoryID, $value, $tKey) {
        if (Gdn::session()->validateTransientKey($tKey)) {
            $this->CategoryModel->saveUserTree($categoryID, ['Unfollow' => (int)(!(bool)$value)]);
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo('/categories');
        }

        $this->render();
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
            redirectTo('/categories');
        }

        $this->render();
    }
}
