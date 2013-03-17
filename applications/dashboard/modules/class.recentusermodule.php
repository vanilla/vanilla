<?php if (!defined('APPLICATION')) exit();

/**
 * Renders the recently active users. Built for use in a side panel.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class RecentUserModule extends Gdn_Module {
   
   public function __construct($Sender = '') {
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 20) {
      $UserModel = new UserModel();
      $this->_Sender->RecentUserData = $UserModel->GetActiveUsers($Limit);
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (!C('Garden.Modules.ShowRecentUserModule'))
         return '';

      $Data = $this->_Sender->RecentUserData;
      if ($Data !== FALSE && $Data->NumRows() > 0)
         return parent::ToString();

      return '';
   }
}