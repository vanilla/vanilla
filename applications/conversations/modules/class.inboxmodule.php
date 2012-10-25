<?php if (!defined('APPLICATION')) exit();

class InboxModule extends Gdn_Module {
   public $Limit = 10;
   public $UserID = NULL;
   
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'conversations';
      $this->UserID = Gdn::Session()->UserID;
   }
   
   public function GetData() {
      // Fetch from model.
      $Model = new ConversationModel();
      $Result = $Model->Get(
         $this->UserID,
         0,
         $this->Limit,
         array()
      );
      
      // Join in the participants.
      $Model->JoinParticipants($Result);
      $this->SetData('Conversations', $Result);
   }
   
   public function ToString() {
      if (!Gdn::Session()->IsValid())
         return '';
      
      if (!$this->Data('Conversations'))
         $this->GetData();
      
      return parent::ToString();
   }
}