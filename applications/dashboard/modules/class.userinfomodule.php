<?php if (!defined('APPLICATION')) exit();

/**
 * Renders information about a user in the user profile (email, join date, visits, etc).
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class UserInfoModule extends Gdn_Module {
   
   public $User;
   public $Roles;
   
   public function __construct($Sender = '') {
      $this->User = FALSE;
      $this->Path(__FILE__);
      parent::__construct($Sender);
   }
   
   public function AssetTarget() {
      return 'Panel';
   }
   
   public function LoadData() {
      $UserID = Gdn::Controller()->Data('Profile.UserID', Gdn::Session()->UserID);
      $this->User = Gdn::UserModel()->GetID($UserID);
      $this->Roles = Gdn::UserModel()->GetRoles($UserID)->ResultArray();
   }

   public function ToString() {
      if (!$this->User)
         $this->LoadData();
      
      if (is_object($this->User))
         return parent::ToString();

      return '';
   }
}