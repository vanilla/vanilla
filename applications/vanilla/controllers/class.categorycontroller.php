<?php
/**
 * Discussion controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    public function follow($CategoryID, $Value, $TKey) {
        if (Gdn::session()->validateTransientKey($TKey)) {
            $this->CategoryModel->SaveUserTree($CategoryID, array('Unfollow' => !(bool)$Value));
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirect('/categories');
        }

        $this->render();
    }

    public function markRead($CategoryID, $TKey) {
        if (Gdn::session()->validateTransientKey($TKey)) {
            $this->CategoryModel->SaveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::toDateTime()));
        }
        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirect('/categories');
        }

        $this->render();
    }
}
