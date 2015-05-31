<?php
/**
 * Discussion controller
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
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

    public function  __construct() {
        parent::__construct();
        $this->CategoryModel = new CategoryModel();
    }

    public function Follow($CategoryID, $Value, $TKey) {
        if (Gdn::Session()->ValidateTransientKey($TKey)) {
            $this->CategoryModel->SaveUserTree($CategoryID, array('Unfollow' => !(bool)$Value));
        }

        if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
            Redirect('/categories');

        $this->Render();
    }

    public function MarkRead($CategoryID, $TKey) {
        if (Gdn::Session()->ValidateTransientKey($TKey)) {
            $this->CategoryModel->SaveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::ToDateTime()));
        }
        if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
            Redirect('/categories');

        $this->Render();
    }
}
