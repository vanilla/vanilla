<?php if (!defined('APPLICATION')) exit();

/**
 * Garden.Modules
 */

/**
 * Renders the "Start a New Discussion" button.
 */
class NewDiscussionModule extends Gdn_Module {

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function ToString() {
      $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', 'any');
      if ($HasPermission)
         return parent::ToString();

      return '';
   }
}