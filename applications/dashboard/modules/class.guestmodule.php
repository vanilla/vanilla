<?php if (!defined('APPLICATION')) exit();

/**
 * Renders the "You should register or sign in" panel box.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class GuestModule extends Gdn_Module {
   
   public $MessageCode = 'GuestModule.Message';
   public $MessageDefault = "It looks like you're new here. If you want to get involved, click one of these buttons!";
   
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      if (!$ApplicationFolder)
         $ApplicationFolder = 'Dashboard';
      parent::__construct($Sender, $ApplicationFolder);
      
      $this->Visible = C('Garden.Modules.ShowGuestModule');
   }
   
   public function AssetTarget() {
      return 'Panel';
   }
   
   public function ToString() {
      if (!Gdn::Session()->IsValid())
         return parent::ToString();

      return '';
   }   

}