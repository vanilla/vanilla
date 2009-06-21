<?php if (!defined('APPLICATION')) exit();

/// <namespace>
/// Lussumo.Garden.Modules
/// </namespace>

/// <summary>
/// Renders recently active bookmarked discussions
/// </summary>
class BookmarkedModule extends Module {
   
   protected $_DiscussionData;
   
   public function __construct(&$Sender = '') {
      $this->_DiscussionData = FALSE;
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 10) {
      $Session = Gdn::Session();
      if ($Session->IsValid()) {
         $DiscussionModel = new DiscussionModel();
         $this->_DiscussionData = $DiscussionModel->Get(
            0,
            $Limit,
            array(
               'w.Bookmarked' => '1',
               'w.UserID' => $Session->UserID
            )
         );
      }
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if ($this->_DiscussionData !== FALSE && $this->_DiscussionData->NumRows() > 0)
         return parent::ToString();

      return '';
   }
}