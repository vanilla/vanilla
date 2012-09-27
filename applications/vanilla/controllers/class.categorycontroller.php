<?php if (!defined('APPLICATION')) exit();

/**
 * Category controller
 * 
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @since 2.0.17.9
 * @package Vanilla
 */

class CategoryController extends VanillaController {
   /**
    * @var Gdn_CategoryModel
    */
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