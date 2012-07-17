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
   
   public $DefaultButton;
   
   public $CssClass = 'Button Action Big';
   
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      parent::__construct($Sender, $ApplicationFolder);
      $this->DefaultButton = C('Vanilla.DefaultNewButton', FALSE);
   }
   
   public function ToString() {
      Gdn::Controller()->EventArguments['NewDiscussionModule'] = &$this;
      Gdn::Controller()->FireEvent('BeforeNewDiscussionButton');
      $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', 'any');
      if (!$HasPermission)
         return '';
      
      return parent::ToString();
   }
   
   public $Buttons = array();
   public function AddButton($Text, $Url) {
      $this->Buttons[] = array('Text' => $Text, 'Url' => $Url);
   }
}