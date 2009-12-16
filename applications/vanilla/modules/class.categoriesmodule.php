<?php if (!defined('APPLICATION')) exit();

/**
 * Garden.Modules
 */

/**
 * Renders the discussion categories.
 */
class CategoriesModule extends Module {
   
   protected $_CategoryData;
   
   public function __construct(&$Sender = '') {
      // Load categories
      $this->_CategoryData = FALSE;
      if (Gdn::Config('Vanilla.Categories.Use') == TRUE) {
         if (!property_exists($Sender, 'CategoryModel') || !is_object($Sender->CategoryModel))
            $Sender->CategoryModel = new Gdn_CategoryModel();
            
         $this->_CategoryData = $Sender->CategoryModel->GetFull();
      }
      parent::__construct($Sender);
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (Gdn::Config('Vanilla.Categories.Use') == TRUE)
         return parent::ToString();

      return '';
   }
}