<?php if (!defined('APPLICATION')) exit();

/// <namespace>
/// Lussumo.Garden.Modules
/// </namespace>

/// <summary>
/// Renders user drafts. If rendered within a discussion, it only shows drafts
/// related to that discussion.
/// </summary>
class DraftsModule extends Module {
   
   protected $_DraftData;
   public $Form;
   
   public function __construct(&$Sender = '') {
      $this->_DraftData = FALSE;
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 20, $DiscussionID = '') {
      $Session = Gdn::Session();
      if ($Session->IsValid()) {
         $DiscussionModel = new DiscussionModel();
         $this->_DraftData = $DiscussionModel->GetDrafts($Session->UserID, 0, $Limit, $DiscussionID);
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