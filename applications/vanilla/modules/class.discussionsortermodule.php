<?php if (!defined('APPLICATION')) exit();
/**
 *
 */

/**
 * Renders the discussion sorter.
 */
class DiscussionSorterModule extends Gdn_Module {
   
   public function __construct($Sender) {
      parent::__construct($Sender, 'Vanilla');
   }
   
   public function AssetTarget() {
      return FALSE;
   }

   public function ToString() {
      if (Gdn::Session()->IsValid())
         return parent::ToString();
   }
}