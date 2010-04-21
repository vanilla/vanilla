<?php if (!defined('APPLICATION')) exit();

/**
 * Garden.Modules
 */

/**
 * Renders user drafts. If rendered within a discussion, it only shows drafts
 * related to that discussion.
 */
class DraftsModule extends Gdn_Module {
   
   protected $_DraftData;
   public $Form;
   
   public function __construct(&$Sender = '') {
      $this->_DraftData = FALSE;
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 20, $DiscussionID = '') {
      $Session = Gdn::Session();
      if ($Session->IsValid()) {
         $DraftModel = new DraftModel();
         $this->_DraftData = $DraftModel->Get($Session->UserID, 0, $Limit, $DiscussionID);
      }
      $this->Form = $this->_Sender->Form;
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if ($this->_DraftData !== FALSE && $this->_DraftData->NumRows() > 0)
         return parent::ToString();

      return '';
   }
}