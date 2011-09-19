<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if ($Session->IsValid() && CheckPermission('Garden.Profiles.Edit')) {
   $this->FireEvent('BeforeStatusForm');      
   $ButtonText = $Session->UserID == $this->User->UserID ? 'Share' : 'Add Comment';
   echo $this->Form->Open(array('action' => Url('/profile/activity/'.$this->User->UserID.'/'.$this->User->Name), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo Wrap($this->Form->TextBox('Comment', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
   echo $this->Form->Button($ButtonText);
   echo $this->Form->Close();
}

// Include the activities
include($this->FetchViewLocation('index', 'activity', 'dashboard'));