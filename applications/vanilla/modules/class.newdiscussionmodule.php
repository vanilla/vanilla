<?php if (!defined('APPLICATION')) exit();

/// <namespace>
/// Lussumo.Garden.Modules
/// </namespace>

/// <summary>
/// Renders the "Start a New Discussion" button.
/// </summary>
class NewDiscussionModule extends Module {

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function ToString() {
      $Session = Gdn::Session();
      if ($Session->IsValid())
         return parent::ToString();

      return '';
   }   
}